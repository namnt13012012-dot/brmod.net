<?php
session_start();
require_once '../config.php';

// === KIỂM TRA ĐĂNG NHẬP ===
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
        $u = trim($_POST['username'] ?? '');
        $p = trim($_POST['password'] ?? '');
        
        if ($u === ADMIN_USER && hash('sha256', $p) === ADMIN_PASS_HASH) {
            $_SESSION['admin_logged_in'] = true;
            header('Location: index.php');
            exit;
        }
        $error = '❌ Sai tên đăng nhập hoặc mật khẩu!';
    }
    
    // === FORM ĐĂNG NHẬP ===
    ?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — BRMOD</title>
<style>
:root { --cyan:#00f0ff; --gold:#ffd000; --bg:#02040f; }
* { margin:0; padding:0; box-sizing:border-box; font-family:system-ui; }
body { background: radial-gradient(ellipse at top, #1a1042 0%, var(--bg) 60%); min-height:100vh; display:flex; align-items:center; justify-content:center; color:#fff; }
.login-box { background:rgba(0,0,0,0.5); border:1px solid rgba(0,240,255,0.25); border-radius:20px; padding:2.5rem; width:100%; max-width:420px; backdrop-filter:blur(12px); box-shadow:0 0 30px rgba(0,240,255,0.2); }
h1 { text-align:center; margin-bottom:2rem; color:var(--cyan); }
.error { color:#ff4466; padding:0.8rem; background:rgba(255,68,102,0.1); border-radius:8px; margin-bottom:1rem; text-align:center; }
input { width:100%; padding:14px; margin:0.5rem 0; background:rgba(0,0,0,0.4); border:1px solid rgba(0,240,255,0.3); border-radius:10px; color:#fff; font-size:1rem; }
input:focus { outline:none; border-color:var(--cyan); box-shadow:0 0 10px rgba(0,240,255,0.4); }
button { width:100%; padding:14px; margin-top:0.5rem; background:linear-gradient(135deg, var(--cyan), #9d4edd); border:none; border-radius:10px; color:#fff; font-weight:bold; font-size:1rem; cursor:pointer; transition:transform 0.2s; }
button:hover { transform:scale(1.02); }
.note { margin-top:1.5rem; text-align:center; color:#666; font-size:0.85rem; }
</style>
</head>
<body>
<div class="login-box">
  <h1>🔐 ADMIN BRMOD</h1>
  <?php if (isset($error)) echo "<div class='error'>$error</div>"; ?>
  <form method="post">
    <input type="text" name="username" placeholder="Tên đăng nhập" required>
    <input type="password" name="password" placeholder="Mật khẩu" required>
    <button type="submit" name="login">Đăng Nhập</button>
  </form>
  <p class="note">Tài khoản: admin<br>Mật khẩu: nguyenthanhnam@1301</p>
</div>
</body>
</html>
    <?php
    exit;
}

// === ĐÃ ĐĂNG NHẬP ===
// Tạo key mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_key'])) {
    $days = max(1, (int)($_POST['days'] ?? 30));
    $note = trim($_POST['note'] ?? '');
    
    // Tạo key định dạng: XXXX-XXXX-XXXX
    $keyCode = strtoupper(bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)));
    $expiresAt = date('Y-m-d H:i:s', time() + $days * 86400);
    
    $stmt = $db->prepare("INSERT INTO `keys` (`key_code`, `expires_at`, `note`) VALUES (?, ?, ?)");
    $stmt->execute([$keyCode, $expiresAt, $note]);
    
    $success = "✅ Key đã tạo: <code style='color:#00f0ff'>$keyCode</code> (Hạn: $days ngày)";
}

// Thu hồi key
if (isset($_GET['revoke'])) {
    $id = (int)$_GET['revoke'];
    $db->prepare("UPDATE `keys` SET `status`='revoked' WHERE `id`=?")->execute([$id]);
    header('Location: index.php');
    exit;
}

// Lấy danh sách key
$keys = $db->query("SELECT * FROM `keys` ORDER BY `created_at` DESC LIMIT 100")->fetchAll();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bảng Điều Khiển — BRMOD</title>
<style>
:root { --cyan:#00f0ff; --green:#00ff88; --red:#ff4466; --yellow:#ffd000; --bg:#02040f; }
* { margin:0; padding:0; box-sizing:border-box; font-family:system-ui; }
body { background:var(--bg); color:#fff; display:flex; min-height:100vh; }
.sidebar { width:260px; background:#000a1a; border-right:1px solid rgba(0,240,255,0.15); padding:2rem 1.5rem; position:fixed; height:100vh; }
.sidebar h2 { color:var(--cyan); margin-bottom:2rem; font-size:1.3rem; }
.sidebar a { display:block; color:#aaa; text-decoration:none; padding:0.8rem 1rem; margin:0.3rem 0; border-radius:8px; transition:all 0.2s; }
.sidebar a:hover, .sidebar a.active { background:rgba(0,240,255,0.1); color:var(--cyan); }
.sidebar a.logout { color:var(--red); margin-top:2rem; }
.main { flex:1; margin-left:260px; padding:2rem; }
h1 { margin-bottom:2rem; color:#fff; }
.card { background:#001026; border:1px solid rgba(0,240,255,0.15); border-radius:16px; padding:1.8rem; margin-bottom:2rem; }
h3 { margin-bottom:1rem; color:var(--cyan); }
.form-row { display:flex; gap:0.8rem; flex-wrap:wrap; }
input, button { padding:10px 14px; border-radius:8px; border:1px solid rgba(0,240,255,0.3); background:#001530; color:#fff; font-size:0.95rem; }
button.primary { background:linear-gradient(135deg, var(--cyan), #9d4edd); border:none; cursor:pointer; font-weight:600; }
table { width:100%; border-collapse:collapse; margin-top:1rem; }
th, td { padding:12px 14px; text-align:left; border-bottom:1px solid rgba(0,240,255,0.1); }
th { color:var(--cyan); font-weight:600; }
.status { padding:4px 10px; border-radius:20px; font-size:0.8rem; font-weight:600; }
.s-active { background:rgba(0,255,136,0.15); color:var(--green); }
.s-claimed { background:rgba(255,208,0,0.15); color:var(--yellow); }
.s-revoked { background:rgba(255,68,102,0.15); color:var(--red); }
code { color:var(--cyan); background:rgba(0,240,255,0.1); padding:2px 6px; border-radius:4px; }
.success-msg { background:rgba(0,255,136,0.1); border:1px solid var(--green); padding:1rem; border-radius:8px; margin-bottom:1rem; }
a.revoke-link { color:var(--red); text-decoration:none; }
.stats { display:grid; grid-template-columns:repeat(4, 1fr); gap:1rem; margin-bottom:2rem; }
.stat-card { background:#001026; border:1px solid rgba(0,240,255,0.15); border-radius:12px; padding:1.2rem; text-align:center; }
.stat-num { font-size:1.8rem; font-weight:bold; color:var(--cyan); }
.stat-label { font-size:0.85rem; color:#888; margin-top:0.3rem; }
@media (max-width:768px) {
  .sidebar { position:static; width:100%; height:auto; }
  .main { margin-left:0; }
  .stats { grid-template-columns:repeat(2, 1fr); }
}
</style>
</head>
<body>

<div class="sidebar">
  <h2>⚙️ BRMOD ADMIN</h2>
  <a href="index.php" class="active">📋 Quản Lý Key</a>
  <a href="logout.php" class="logout">🚪 Đăng Xuất</a>
</div>

<div class="main">
  <h1>Bảng Điều Khiển Quản Lý Key</h1>
  
  <?php if (isset($success)) echo "<div class='success-msg'>$success</div>"; ?>
  
  <!-- Thống kê -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-num"><?=count($keys)?></div>
      <div class="stat-label">Tổng Key</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:#00ff88"><?=count(array_filter($keys, fn($k)=>$k['status']==='active'))?></div>
      <div class="stat-label">Chưa Dùng</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:#ffd000"><?=count(array_filter($keys, fn($k)=>$k['status']==='claimed'))?></div>
      <div class="stat-label">Đã Phát</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:#ff4466"><?=count(array_filter($keys, fn($k)=>$k['status']==='revoked' || (strtotime($k['expires_at'])<time() && $k['status']==='active')))?></div>
      <div class="stat-label">Hết/Thu Hồi</div>
    </div>
  </div>

  <!-- Tạo Key Mới -->
  <div class="card">
    <h3>➕ Tạo Key Mới</h3>
    <form method="post">
      <div class="form-row">
        <input type="number" name="days" placeholder="Số ngày hiệu lực" min="1" value="30" required>
        <input type="text" name="note" placeholder="Ghi chú (tùy chọn)">
        <button type="submit" name="create_key" class="primary">Tạo Ngay</button>
      </div>
    </form>
  </div>

  <!-- Danh sách Key -->
  <div class="card">
    <h3>📄 Danh Sách Key</h3>
    <table>
      <thead>
        <tr>
          <th>Key Code</th>
          <th>Trạng Thái</th>
          <th>Hạn Dùng</th>
          <th>Ghi Chú</th>
          <th>Hành Động</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($keys as $k): 
          $isExpired = strtotime($k['expires_at']) < time();
          $statusClass = match(true) {
            $k['status']==='revoked' => 's-revoked',
            $k['status']==='claimed' => 's-claimed',
            $isExpired => 's-revoked',
            default => 's-active'
          };
          $statusText = match(true) {
            $k['status']==='revoked' => 'Đã thu hồi',
            $k['status']==='claimed' => 'Đã phát',
            $isExpired => 'Hết hạn',
            default => 'Chưa dùng'
          };
        ?>
        <tr>
          <td><code><?=htmlspecialchars($k['key_code'])?></code></td>
          <td><span class="status <?=$statusClass?>"><?=$statusText?></span></td>
          <td><?=date('d/m/Y H:i', strtotime($k['expires_at']))?></td>
          <td><?=htmlspecialchars($k['note']??'-')?></td>
          <td>
            <?php if ($k['status']==='active' && !$isExpired): ?>
              <a href="?revoke=<?=$k['id']?>" class="revoke-link" onclick="return confirm('Xác nhận thu hồi key này?')">Thu hồi</a>
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
