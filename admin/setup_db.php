<?php
require_once 'db_config.php';

header('Content-Type: text/html; charset=utf-8');

echo "<h2>Database Setup</h2>";

$conn = getDBConnection();

// Create artworks table
$sql_artworks = "CREATE TABLE IF NOT EXISTS artworks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    style VARCHAR(100) NOT NULL,
    size VARCHAR(100) NOT NULL,
    medium VARCHAR(100) NOT NULL,
    frame_info VARCHAR(100) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    badge_text VARCHAR(100) DEFAULT NULL,
    is_featured BOOLEAN DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql_artworks) === TRUE) {
    echo "Table 'artworks' created or already exists.<br>";
} else {
    echo "Error creating table 'artworks': " . $conn->error . "<br>";
}

// Create admin_users table
$sql_admin = "CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_admin) === TRUE) {
    echo "Table 'admin_users' created or already exists.<br>";
} else {
    echo "Error creating table 'admin_users': " . $conn->error . "<br>";
}

// Create email_log table
$sql_email_log = "CREATE TABLE IF NOT EXISTS email_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action_type VARCHAR(50) NOT NULL,
    artwork_id INT DEFAULT NULL,
    recipient_email VARCHAR(100) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql_email_log) === TRUE) {
    echo "Table 'email_log' created or already exists.<br>";
} else {
    echo "Error creating table 'email_log': " . $conn->error . "<br>";
}

// Check if admin user exists, if not, create default
$check_admin = "SELECT id FROM admin_users WHERE username = 'admin'";
$result = $conn->query($check_admin);
if ($result->num_rows == 0) {
    $default_password = password_hash("admin123", PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO admin_users (username, password_hash, email) VALUES ('admin', '$default_password', 'anisartsgallery0@gmail.com')";
    if ($conn->query($insert_admin) === TRUE) {
        echo "Default admin user created (admin / admin123).<br>";
    } else {
        echo "Error creating admin user: " . $conn->error . "<br>";
    }
}

// Seed artworks if empty
$check_artworks = "SELECT COUNT(*) as count FROM artworks";
$result = $conn->query($check_artworks);
$row = $result->fetch_assoc();

if ($row['count'] == 0) {
    echo "Seeding default artworks...<br>";
    
    $default_artworks = [
        [
            'title' => 'Kalma Tayyab',
            'description' => 'The declaration of faith (La ilaha illallah Muhammadur Rasulullah) beautifully rendered in elegant Diwani script with gold leaf accents.',
            'style' => 'Diwani Script',
            'size' => '3000×3000 pixels',
            'medium' => 'Ink on handmade paper with gold leaf',
            'frame_info' => 'Hand-carved wooden frame available',
            'price' => 450,
            'image_path' => 'banner1.jpg',
            'badge_text' => 'High Resolution'
        ],
        [
            'title' => 'Bismillah Ar-Rahman',
            'description' => 'The opening phrase \'In the name of Allah, the Most Gracious, the Most Merciful\' in intricate Thuluth style with traditional Islamic patterns.',
            'style' => 'Thuluth Script',
            'size' => '3000×3000 pixels',
            'medium' => 'Traditional ink and watercolor',
            'frame_info' => 'Gold-plated metal frame available',
            'price' => 380,
            'image_path' => 'banner2.jpg',
            'badge_text' => 'Premium Quality'
        ],
        [
            'title' => 'Surah Al-Fatiha',
            'description' => 'The opening chapter of the Holy Quran, beautifully written in Naskh script. This masterpiece captures every stroke with museum-quality detail.',
            'style' => 'Naskh Script',
            'size' => '3000×3000 pixels',
            'medium' => 'Acrylic on canvas',
            'frame_info' => 'Floating frame with museum glass',
            'price' => 520,
            'image_path' => 'banner3.jpg',
            'badge_text' => 'Museum Quality'
        ],
        [
            'title' => 'Asma-ul-Husna',
            'description' => 'The 99 Names of Allah arranged in a circular composition representing divine unity.',
            'style' => 'Composite Style',
            'size' => '3000×3000 pixels',
            'medium' => 'Mixed media on wooden panel',
            'frame_info' => 'Shadow box frame available',
            'price' => 650,
            'image_path' => 'banner4.jpg',
            'badge_text' => 'Limited Edition'
        ],
        [
            'title' => 'Ayat-ul-Kursi',
            'description' => 'The Throne Verse in traditional Kufic geometric design.',
            'style' => 'Kufic Script',
            'size' => '3000×3000 pixels',
            'medium' => 'Gold leaf and ink on blue paper',
            'frame_info' => 'Traditional Islamic frame available',
            'price' => 420,
            'image_path' => 'banner5.jpg',
            'badge_text' => 'Gold Leaf Accents'
        ],
        [
            'title' => 'Name of Prophet Muhammad (SAW)',
            'description' => 'The sacred name of Prophet Muhammad (peace be upon him) in decorative Ottoman style calligraphy.',
            'style' => 'Ottoman Script',
            'size' => '3000×3000 pixels',
            'medium' => 'Ink and gold on marbled paper',
            'frame_info' => 'Ornate wooden frame available',
            'price' => 480,
            'image_path' => 'banner6.jpg',
            'badge_text' => 'Handcrafted'
        ],
        [
            'title' => 'Geometric Harmony',
            'description' => 'Traditional Islamic geometric patterns integrated with Quranic verses.',
            'style' => 'Geometric Kufic',
            'size' => '3000×3000 pixels',
            'medium' => 'Digital art print on archival paper',
            'frame_info' => 'Simple black frame included',
            'price' => 350,
            'image_path' => 'banner7.jpg',
            'badge_text' => 'Digital Print'
        ],
        [
            'title' => 'Morning Adhkar',
            'description' => 'Daily morning supplications beautifully arranged in elegant Riqa script.',
            'style' => 'Riqa Script',
            'size' => '3000×3000 pixels',
            'medium' => 'Watercolor and ink',
            'frame_info' => 'Silver metal frame available',
            'price' => 290,
            'image_path' => 'banner8.jpg',
            'badge_text' => 'Watercolor'
        ]
    ];
    
    $stmt = $conn->prepare("INSERT INTO artworks (title, description, style, size, medium, frame_info, price, image_path, badge_text, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($default_artworks as $index => $art) {
        $sort_order = $index + 1;
        $stmt->bind_param("ssssssdssi", 
            $art['title'], $art['description'], $art['style'], $art['size'], 
            $art['medium'], $art['frame_info'], $art['price'], $art['image_path'], 
            $art['badge_text'], $sort_order
        );
        $stmt->execute();
    }
    
    echo "Inserted 8 default artworks.<br>";
} else {
    echo "Artworks table already contains data. Skipped seeding.<br>";
}

$conn->close();

echo "<h3>Setup complete!</h3>";
echo "<a href='index.html'>Go to Admin Panel</a>";
?>
