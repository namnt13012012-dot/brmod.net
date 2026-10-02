<?php
// === CẤU HÌNH ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'brmod_keys');
define('DB_USER', 'root');
define('DB_PASS', '');
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', hash('sha256', 'mat_khau_manh_cua_ban')); // Đổi mật khẩu!
define('SALT', 'thay-doi-thanh-chuoi-ngau-nhien-123456');

// === Kết nối CSDL ===
try {
  $db = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
} catch(PDOException $e) {
  die("Kết nối thất bại: " . $e->getMessage());
}

// === Tạo bảng nếu chưa có ===
$db->exec("CREATE TABLE IF NOT EXISTS `keys` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `key_code` VARCHAR(32) NOT NULL UNIQUE,
  `user_input` VARCHAR(255) DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `status` ENUM('active','used','revoked','expired') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `used_at` DATETIME DEFAULT NULL,
  `note` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
