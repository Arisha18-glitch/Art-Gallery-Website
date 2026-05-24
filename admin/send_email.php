<?php
// Include PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Try to include PHPMailer, gracefully fail if not downloaded yet
@include_once 'mailer/Exception.php';
@include_once 'mailer/PHPMailer.php';
@include_once 'mailer/SMTP.php';

function sendArtworkNotification($artwork, $action_type = 'Added') {
    $conn = getDBConnection();
    $to_email = 'anisartsgallery0@gmail.com';
    $subject = "Artwork $action_type: " . $artwork['title'];
    
    // Create HTML template
    $html_message = "
    <html>
    <head>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; color: #333; background-color: #f9f9f9; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            .header { border-bottom: 2px solid #D4AF37; padding-bottom: 20px; margin-bottom: 20px; text-align: center; }
            .header h2 { color: #1a1a2e; margin: 0; }
            .badge { display: inline-block; padding: 5px 10px; background-color: #D4AF37; color: #fff; border-radius: 4px; font-weight: bold; font-size: 14px; margin-bottom: 15px;}
            .content { margin-bottom: 30px; }
            .artwork-image { max-width: 100%; height: auto; border-radius: 6px; border: 1px solid #ddd; margin-bottom: 20px; }
            .details { background: #f8f9fa; padding: 15px; border-radius: 6px; }
            .details p { margin: 10px 0; }
            .footer { text-align: center; font-size: 12px; color: #777; margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>Sania's Calligraphy Admin</h2>
            </div>
            <div class='content'>
                <span class='badge'>Artwork $action_type</span>
                <h3>{$artwork['title']}</h3>
                <p><strong>Description:</strong><br>{$artwork['description']}</p>
                
                <div class='details'>
                    <p><strong>Price:</strong> $" . number_format($artwork['price'], 2) . "</p>
                    <p><strong>Style:</strong> {$artwork['style']}</p>
                    <p><strong>Size:</strong> {$artwork['size']}</p>
                    <p><strong>Medium:</strong> {$artwork['medium']}</p>
                </div>
            </div>
            <div class='footer'>
                <p>This is an automated notification from your website's admin panel.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $status = 'Failed';
    
    // Check if PHPMailer exists
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $mail = new PHPMailer(true);
        try {
            // Uncomment and configure these when ready to use SMTP
            /*
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'anisartsgallery0@gmail.com'; // Your email
            $mail->Password   = 'YOUR_APP_PASSWORD'; // App password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            */
            
            // For now, use PHP mail() as fallback if SMTP isn't configured
            
            $mail->setFrom('noreply@calligraphyart.com', 'Calligraphy Gallery');
            $mail->addAddress($to_email);
            
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $html_message;
            $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $html_message));
            
            $mail->send();
            $status = 'Sent';
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
    } else {
        // Fallback to basic PHP mail if PHPMailer not available
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8\r\n";
        $headers .= "From: noreply@calligraphyart.com\r\n";
        
        if (@mail($to_email, $subject, $html_message, $headers)) {
            $status = 'Sent (Basic)';
        }
    }
    
    // Log the email
    $artwork_id = isset($artwork['id']) ? $artwork['id'] : null;
    $stmt = $conn->prepare("INSERT INTO email_log (action_type, artwork_id, recipient_email, subject, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $action_type, $artwork_id, $to_email, $subject, $status);
    $stmt->execute();
    $stmt->close();
    
    return $status === 'Sent' || $status === 'Sent (Basic)';
}
?>
