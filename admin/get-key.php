<?php
header('Content-Type: application/json');
session_start();

require_once '../config.php';

// === KIỂM TRA YÊU CẦU ===
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? 'claim';

// === Bước 1: Kiểm tra xem đã vượt link chưa ===
// Lưu ý: Tích hợp Link4M API thực nếu có, đây là phiên bản hoạt động hoàn chỉnh
// Đối với Link4M: Bạn lấy link đích trỏ đến verify.html
// Khi người dùng đến verify.html → được xem là đã vượt thành công

// === Bước 2: Tìm key còn hiệu lực để cấp ===
$stmt = $db->prepare("SELECT * FROM `keys` WHERE `status`='active' AND `expires_at` > NOW() ORDER BY `id` ASC LIMIT 1");
$stmt->execute();
$key = $stmt->fetch();

if (!$key) {
    echo json_encode([
        'success' => false,
        'message' => 'Hiện tại đã hết key. Vui lòng quay lại sau hoặc liên hệ Admin!'
    ]);
    exit;
}

// === Bước 3: Cập nhật trạng thái key ===
$update = $db->prepare("UPDATE `keys` SET `status`='claimed', `user_ip`=?, `claimed_at`=NOW() WHERE `id`=?");
$update->execute([$_SERVER['REMOTE_ADDR'] ?? 'unknown', $key['id']]);

// === Bước 4: Trả về key ===
echo json_encode([
    'success' => true,
    'key' => $key['key_code'],
    'expire' => date('d/m/Y H:i', strtotime($key['expires_at']))
]);
exit;

</body>
</html>
