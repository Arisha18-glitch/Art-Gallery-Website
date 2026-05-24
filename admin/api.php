<?php
require_once 'auth.php';
require_once 'send_email.php';

// Force authentication for API
requireLogin();

header('Content-Type: application/json');
$conn = getDBConnection();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'dashboard':
        $stats = [];
        
        // Total artworks
        $res = $conn->query("SELECT COUNT(*) as total FROM artworks");
        $stats['total_artworks'] = $res->fetch_assoc()['total'];
        
        // Total value
        $res = $conn->query("SELECT SUM(price) as total_value FROM artworks");
        $stats['total_value'] = $res->fetch_assoc()['total_value'] ?? 0;
        
        // Recent artworks
        $res = $conn->query("SELECT id, title, price, image_path, created_at FROM artworks ORDER BY created_at DESC LIMIT 5");
        $recent_artworks = [];
        while($row = $res->fetch_assoc()) {
            $recent_artworks[] = $row;
        }
        $stats['recent_artworks'] = $recent_artworks;
        
        // Recent emails
        $res = $conn->query("SELECT * FROM email_log ORDER BY sent_at DESC LIMIT 5");
        $recent_emails = [];
        while($row = $res->fetch_assoc()) {
            $recent_emails[] = $row;
        }
        $stats['recent_emails'] = $recent_emails;
        
        echo json_encode(['success' => true, 'data' => $stats]);
        break;
        
    case 'list':
        $res = $conn->query("SELECT * FROM artworks ORDER BY sort_order ASC, created_at DESC");
        $artworks = [];
        while($row = $res->fetch_assoc()) {
            $artworks[] = $row;
        }
        echo json_encode(['success' => true, 'data' => $artworks]);
        break;
        
    case 'get':
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("SELECT * FROM artworks WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Artwork not found']);
        }
        break;
        
    case 'create':
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        
        $id = $action === 'update' ? (int)($_GET['id'] ?? 0) : 0;
        
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $style = trim($_POST['style'] ?? '');
        $size = trim($_POST['size'] ?? '');
        $medium = trim($_POST['medium'] ?? '');
        $frame_info = trim($_POST['frame_info'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $badge_text = trim($_POST['badge_text'] ?? '');
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $sort_order = (int)($_POST['sort_order'] ?? 0);
        
        if (empty($title) || empty($price)) {
            echo json_encode(['success' => false, 'message' => 'Title and price are required']);
            exit;
        }
        
        // Handle image upload
        $image_path = '';
        if ($action === 'update') {
            // Get existing image path first
            $stmt = $conn->prepare("SELECT image_path FROM artworks WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $image_path = $row['image_path'];
            }
        }
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/artworks/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
            
            $file_info = pathinfo($_FILES['image']['name']);
            $extension = strtolower($file_info['extension']);
            
            // Validate extension
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($extension, $allowed)) {
                echo json_encode(['success' => false, 'message' => 'Invalid image format']);
                exit;
            }
            
            $new_filename = uniqid('art_') . '.' . $extension;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                $image_path = 'uploads/artworks/' . $new_filename;
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save uploaded image']);
                exit;
            }
        }
        
        if ($action === 'create' && empty($image_path)) {
            echo json_encode(['success' => false, 'message' => 'Image is required for new artworks']);
            exit;
        }
        
        if ($action === 'create') {
            $stmt = $conn->prepare("INSERT INTO artworks (title, description, style, size, medium, frame_info, price, image_path, badge_text, is_featured, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssdssii", $title, $description, $style, $size, $medium, $frame_info, $price, $image_path, $badge_text, $is_featured, $sort_order);
        } else {
            $stmt = $conn->prepare("UPDATE artworks SET title=?, description=?, style=?, size=?, medium=?, frame_info=?, price=?, image_path=?, badge_text=?, is_featured=?, sort_order=? WHERE id=?");
            $stmt->bind_param("ssssssdssiii", $title, $description, $style, $size, $medium, $frame_info, $price, $image_path, $badge_text, $is_featured, $sort_order, $id);
        }
        
        if ($stmt->execute()) {
            $artwork_id = $action === 'create' ? $stmt->insert_id : $id;
            
            // Prepare artwork data for email
            $artwork_data = [
                'id' => $artwork_id,
                'title' => $title,
                'description' => $description,
                'price' => $price,
                'style' => $style,
                'size' => $size,
                'medium' => $medium,
                'image_path' => $image_path
            ];
            
            // Send email notification asynchronously
            sendArtworkNotification($artwork_data, $action === 'create' ? 'Added' : 'Updated');
            
            echo json_encode(['success' => true, 'message' => "Artwork $action successfully", 'id' => $artwork_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        }
        break;
        
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        
        $id = (int)($_GET['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM artworks WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Artwork deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
