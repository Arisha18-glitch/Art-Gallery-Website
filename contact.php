<?php
// contact.php - Public endpoint for contact form submissions
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

require_once 'admin/db_config.php';
$conn = getDBConnection();

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if (empty($name) || empty($email) || empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
    exit();
}

// Extract artwork ID if any
$artwork_id = 0;
if (preg_match('/Artwork ID:\s*(\d+)/i', $message, $matches)) {
    $artwork_id = (int)$matches[1];
}

// Ensure contacts table exists
$createTableSQL = "CREATE TABLE IF NOT EXISTS contacts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($createTableSQL);

$sql = "INSERT INTO contacts (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);

if ($stmt->execute()) {
    $contact_id = $stmt->insert_id;
    
    // Send email notification using PHPMailer
    @include_once 'admin/mailer/Exception.php';
    @include_once 'admin/mailer/PHPMailer.php';
    @include_once 'admin/mailer/SMTP.php';
    
    $to_email = 'anisartsgallery0@gmail.com';
    $email_subject = "New Artwork Enquiry: $subject";
    
    $html_message = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            .header { border-bottom: 2px solid #D4AF37; padding-bottom: 20px; margin-bottom: 20px; text-align: center; }
            .header h2 { color: #1a1a2e; margin: 0; }
            .content { margin-bottom: 30px; }
            .details { background: #f8f9fa; padding: 15px; border-radius: 6px; }
            .details p { margin: 10px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Sania's Calligraphy</h2>
            </div>
            <div class='content'>
                <h3>New Enquiry Received</h3>
                <div class='details'>
                    <p><strong>Name:</strong> $name</p>
                    <p><strong>Email:</strong> $email</p>
                    <p><strong>Phone:</strong> $phone</p>
                    <p><strong>Subject:</strong> $subject</p>
                </div>
                <p><strong>Message:</strong><br/>" . nl2br(htmlspecialchars($message)) . "</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $email_sent = false;
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer(true);
        try {
            // Uncomment to use actual SMTP instead of local testing sendmail
            /*
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'anisartsgallery0@gmail.com'; 
            $mail->Password   = 'YOUR_APP_PASSWORD'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            */
            
            // For now, if no SMTP configured, we just set sender and send it using PHP mail() fallback or internal
            $mail->setFrom($email, $name);
            $mail->addAddress($to_email);
            $mail->addReplyTo($email, $name);
            $mail->isHTML(true);
            $mail->Subject = $email_subject;
            $mail->Body    = $html_message;
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>'], "\n", $html_message));
            $mail->send();
            $email_sent = true;
        } catch (Exception $e) {
            error_log("Contact form email failed: {$mail->ErrorInfo}");
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your enquiry has been submitted successfully.' . ($email_sent ? '' : ' (Email notification skipped locally)'),
        'contact_id' => $contact_id
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to save enquiry. Please try again.'
    ]);
}

$conn->close();
?>