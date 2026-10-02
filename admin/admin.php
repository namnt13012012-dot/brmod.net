<?php
session_start();
require_once '../config.php';

// Xử lý đăng nhập
if($_POST['admin_login'] ?? ''){
  $u = $_POST['username'];
  $p = $_POST['password'];
  if($u === ADMIN_USER && hash('sha256',$p) === ADMIN_PASS_HASH){
    $_SESSION['admin_auth'] = true;
    header('Location: index.php'); exit;
  }
  $error = 'Sai thông tin đăng nhập';
}
if(!$_SESSION['admin_auth']){
?>
<!DOCTYPE html>
<html><body style="background:#020617; color:#fff; font-family:system-ui;">
<div style="max-width:400px; margin:100px auto; padding:2rem; border:1px solid #00e5ff40; border-radius:16px; background:#0008;">
  <h2 style="color:#00e5ff; text-align:center;">🔐 Admin — BRMOD</h2>
  <?=$error?'<p style="color:#f55;">'.$error.'</p>':''?>
  <form method="post">
    <input type="text" name="username" placeholder="Tên đăng nhập" required
      style="width:100%; padding:12px; margin:0.5rem 0; background:#111; border:1px solid #00e5ff40; color:#fff; border-radius:8px;">
    <input type="password" name="password" placeholder="Mật khẩu" required
      style="width:100%; padding:12px; margin:0.5rem 0; background:#111; border:1px solid #00e5ff40; color:#fff; border-radius:8px;">
    <button type="submit" name="admin_login" style="width:100%; padding:12px; background:linear-gradient(135deg,#00e5ff,#8b5cf6); border:none; border-radius:8px; color:#fff; font-weight:bold; margin-top:0.5rem;">Đăng Nhập</button>
  </form>
</div>
</body></html>
<?php exit; }

// === Đã đăng nhập ===
// Tạo key mới
if($_POST['create_key'] ?? ''){
  $duration = (int)$_POST['duration']; // ngày
  $note = $_POST['note'] ?? '';
  $key = strtoupper(bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)));
  $expire = date('Y-m-d H:i:s', time() + $duration*86400);
  
  $db->prepare("INSERT INTO `keys` (`key_code`, `expires_at`, `note`) VALUES (?,?,?)")
    ->execute([$key, $expire, $note]);
  header('Location: index.php'); exit;
}

// Vô hiệu hóa key
if($_GET['action'] === 'revoke'){
  $stmt = $db->prepare("UPDATE `keys` SET `status`='revoked' WHERE `id`=?");
  $stmt->execute([$_GET['id']]);
  header('Location: index.php'); exit;
}

// Lấy danh sách
$keys = $db->query("SELECT * FROM `keys` ORDER BY `created_at` DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<title>Admin — BRMOD Key System</title>
<style>
*{margin:0; padding:0; box-sizing:border-box;}
body {
  background: #020617;
  color: #eef;
  font-family: system-ui, sans-serif;
  display: flex; min-height: 100vh;
}
.sidebar {
  width: 240px; background: #000a1a;
  border-right: 1px solid #00e5ff30;
  padding: 2rem 1rem;
}
.sidebar h2 { color: #00e5ff; margin-bottom: 2rem; }
.sidebar a {
  display: block; color: #aaf; text-decoration: none;
  padding: 0.75rem 1rem; margin: 0.25rem 0; border-radius: 8px;
}
.sidebar a:hover, .sidebar a.active { background: #00e5ff15; color: #00e5ff; }
.main { flex: 1; padding: 2rem; overflow-y: auto; }
h1 { color: #fff; margin-bottom: 2rem; }
.card {
  background: #001026; border: 1px solid #00e5ff25;
  border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;
}
input, select, button {
  background: #001530; border: 1px solid #00e5ff40;
  color: #fff; padding: 10px 14px; border-radius: 6px; margin: 0.25rem;
}
button.primary {
  background: linear-gradient(135deg, #00e5ff, #8b5cf6);
  border: none; font-weight: 600; cursor: pointer;
}
table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
th, td { padding: 12px; text-align: left; border-bottom: 1px solid #00e5ff20; }
th { color: #00e5ff; }
.status-active { color: #0f8; }
.status-expired { color: #f80; }
.status-revoked { color: #f44; }
.badge {
  display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;
}
.logout { margin-top: 2rem; color: #f66 !important; }
</style>
</head>
<body>

<div class="sidebar">
  <h2>⚙️ BRMOD</h2>
  <a href="index.php" class="active">📋 Quản lý Key</a>
  <a href="settings.php">⚙️ Cấu hình</a>
  <a href="logout.php" class="logout">🚪 Đăng Xuất</a>
</div>

<div class="main">
  <h1>Bảng Điều Khiển Quản Lý Key</h1>

  <div class="card">
    <h3>➕ Tạo Key Mới</h3>
    <form method="post" style="margin-top:1rem;">
      <input type="number" name="duration" placeholder="Hạn dùng (ngày)" min="1" value="30" required>
      <input type="text" name="note" placeholder="Ghi chú / Mô tả">
      <button type="submit" name="create_key" class="primary">Tạo Key</button>
    </form>
  </div>

  <div class="card">
    <h3>📄 Danh Sách Key (<?=count($keys)?>)</h3>
    <table>
      <thead>
        <tr><th>Key</th><th>Trạng thái</th><th>Hết hạn</th><th>Ghi chú</th><th>Hành động</th></tr>
      </thead>
      <tbody>
      <?php foreach($keys as $k):
        $isExpired = strtotime($k['expires_at']) < time();
        $status = $k['status'] === 'revoked' ? ['text'=>'Đã thu hồi','class'=>'status-revoked'] :
                  ($isExpired ? ['text'=>'Hết hạn','class'=>'status-expired'] : ['text'=>'Hoạt động','class'=>'status-active']);
      ?>
        <tr>
          <td><code style="color:#00e5ff;"><?=htmlspecialchars($k['key_code'])?></code></td>
          <td><span class="badge <?=$status['class']?>"><?=$status['text']?></span></td>
          <td><?=htmlspecialchars($k['expires_at'])?></td>
          <td><?=htmlspecialchars($k['note']??'-')?></td>
          <td>
            <?php if($k['status'] !== 'revoked' && !$isExpired): ?>
              <a href="?action=revoke&id=<?=$k['id']?>" style="color:#f55; text-decoration:none;" onclick="return confirm('Xác nhận thu hồi?')">Thu hồi</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

</body>
</html>
