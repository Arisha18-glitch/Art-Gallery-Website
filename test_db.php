<?php
// test_db.php - Database Connection Tester
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
        .success { color: #27ae60; background: #d5f4e6; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: #e74c3c; background: #fadbd8; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: #3498db; background: #d6eaf8; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .test-result { margin: 20px 0; padding: 15px; border-left: 4px solid #3498db; background: #f8f9fa; }
    </style>
</head>
<body>
    <h1>Database Connection Test</h1>
    
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "calligraphy_db";
    
    echo '<div class="test-result">';
    echo "<h3>Test Configuration:</h3>";
    echo "<p>Server: $servername</p>";
    echo "<p>Username: $username</p>";
    echo "<p>Database: $dbname</p>";
    echo '</div>';
    
    // Test 1: Check if MySQL is running
    echo '<div class="test-result">';
    echo "<h3>Test 1: MySQL Server Connection</h3>";
    $conn = @new mysqli($servername, $username, $password);
    
    if ($conn->connect_error) {
        echo '<div class="error">❌ MySQL Connection Failed: ' . $conn->connect_error . '</div>';
    } else {
        echo '<div class="success">✅ MySQL Server Connected Successfully</div>';
        echo '<p>MySQL Version: ' . $conn->server_version . '</p>';
        
        // Test 2: Check if database exists
        echo '<h3>Test 2: Database Check</h3>';
        $result = $conn->query("SHOW DATABASES LIKE '$dbname'");
        
        if ($result->num_rows > 0) {
            echo '<div class="success">✅ Database "' . $dbname . '" exists</div>';
            
            // Test 3: Connect to specific database
            $conn->select_db($dbname);
            
            // Test 4: Check/create enquiries table
            echo '<h3>Test 3: Table Structure</h3>';
            $tableCheck = $conn->query("SHOW TABLES LIKE 'enquiries'");
            
            if ($tableCheck->num_rows > 0) {
                echo '<div class="success">✅ Table "enquiries" exists</div>';
                
                // Show table structure
                $structure = $conn->query("DESCRIBE enquiries");
                echo '<table border="1" cellpadding="5" cellspacing="0">';
                echo '<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>';
                while($row = $structure->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . $row['Field'] . '</td>';
                    echo '<td>' . $row['Type'] . '</td>';
                    echo '<td>' . $row['Null'] . '</td>';
                    echo '<td>' . $row['Key'] . '</td>';
                    echo '<td>' . $row['Default'] . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
                
                // Count records
                $count = $conn->query("SELECT COUNT(*) as total FROM enquiries");
                $row = $count->fetch_assoc();
                echo '<p>Total records in enquiries: ' . $row['total'] . '</p>';
            } else {
                echo '<div class="info">Table "enquiries" does not exist yet. It will be created automatically.</div>';
            }
        } else {
            echo '<div class="error">❌ Database "' . $dbname . '" does not exist.</div>';
            echo '<p>Create it with: <code>CREATE DATABASE ' . $dbname . ';</code></p>';
        }
        
        $conn->close();
    }
    
    // Test 4: Check file permissions
    echo '<h3>Test 4: File Permissions</h3>';
    $file = 'contact.php';
    if (file_exists($file)) {
        echo '<div class="success">✅ File exists: ' . $file . '</div>';
        echo '<p>Size: ' . filesize($file) . ' bytes</p>';
        echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($file)) . '</p>';
        
        // Check if writable
        if (is_writable($file)) {
            echo '<div class="success">✅ File is writable</div>';
        } else {
            echo '<div class="info">File is not writable (normal for PHP files)</div>';
        }
    } else {
        echo '<div class="error">❌ File not found: ' . $file . '</div>';
    }
    echo '</div>';
    
    // Test 5: PHP Configuration
    echo '<div class="test-result">';
    echo '<h3>Test 5: PHP Configuration</h3>';
    echo '<p>PHP Version: ' . phpversion() . '</p>';
    echo '<p>MySQLi enabled: ' . (extension_loaded('mysqli') ? '✅ Yes' : '❌ No') . '</p>';
    echo '<p>Error reporting: ' . ini_get('error_reporting') . '</p>';
    echo '<p>Display errors: ' . ini_get('display_errors') . '</p>';
    echo '</div>';
    
    // Test 6: Test POST request simulation
    echo '<div class="test-result">';
    echo '<h3>Test 6: Simulate Form Submission</h3>';
    echo '<form action="contact.php" method="POST" target="_blank">';
    echo '<input type="hidden" name="name" value="Test User">';
    echo '<input type="hidden" name="email" value="test@example.com">';
    echo '<input type="hidden" name="subject" value="Test Submission">';
    echo '<input type="hidden" name="message" value="This is a test message">';
    echo '<button type="submit">Test Contact Form</button>';
    echo '</form>';
    echo '</div>';
    ?>
</body>
</html>