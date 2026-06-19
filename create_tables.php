<?php
require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();

    $sql1 = "CREATE TABLE IF NOT EXISTS user_devices (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        device_token VARCHAR(255) NOT NULL,
        platform ENUM('android', 'ios', 'web') DEFAULT 'android',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY user_device_unique (user_id, device_token)
    )";

    $sql2 = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        action_link VARCHAR(255) DEFAULT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";

    if ($conn->query($sql1) && $conn->query($sql2)) {
        echo "Tables created successfully.";
    } else {
        echo "Error creating tables: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
