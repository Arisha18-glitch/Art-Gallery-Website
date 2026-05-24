<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Allow public access

require_once '../admin/db_config.php';

$conn = getDBConnection();

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    // Only fetch featured artworks for the public gallery
    $res = $conn->query("SELECT id, title, description, style, size, medium, frame_info, price, image_path, badge_text FROM artworks WHERE is_featured = 1 ORDER BY sort_order ASC, created_at DESC");
    
    $artworks = [];
    while($row = $res->fetch_assoc()) {
        $artworks[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $artworks]);
} else if ($action === 'get') {
    $id = (int)($_GET['id'] ?? 0);
    $stmt = $conn->prepare("SELECT id, title, description, style, size, medium, frame_info, price, image_path, badge_text FROM artworks WHERE id = ? AND is_featured = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode(['success' => true, 'data' => $result->fetch_assoc()]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Artwork not found']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$conn->close();
?>
