<?php
// === CẤU HÌNH KẾT NỐI CSDL ===
define('DB_HOST', 'localhost');
define('DB_NAME', 'brmod_keys');
define('DB_USER', 'root');
define('DB_PASS', '');

// === TÀI KHOẢN ADMIN ===
define('ADMIN_USER', 'admin');
define('ADMIN_PASS_HASH', hash('sha256', 'nguyenthanhnam@1301'));
define('SALT', 'brmod_universe_2026_secure_random_string_abc123xyz');

// === Kết nối CSDL ===
try {
    $db = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("❌ Kết nối CSDL thất bại: " . $e->getMessage() . "<br>Vui lòng tạo CSDL tên: <b>brmod_keys</b> trước!");
}

// === Tạo bảng nếu chưa có ===
$db->exec("CREATE TABLE IF NOT EXISTS `keys` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `key_code` VARCHAR(32) NOT NULL UNIQUE,
  `user_ip` VARCHAR(45) DEFAULT NULL,
  `claimed_at` DATETIME DEFAULT NULL,
  `expires_at` DATETIME NOT NULL,
  `status` ENUM('active', 'claimed', 'revoked', 'expired') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `note` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
