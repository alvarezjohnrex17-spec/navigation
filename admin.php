<?php
session_start();
require_once __DIR__ . '/config.php';
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $pass = $_POST['password'] ?? '';
  if ($pass === ADMIN_PASSWORD) { $_SESSION['is_admin']=true; header('Location: dashboard.php'); exit; }
  else $error='Wrong password! Try again.';
}
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login</title>
<link rel="stylesheet" href="assets/style.css"></head>
<body>
<div class="container small">
  <a href="index.php" class="back">← Back</a>
  <div class="card form-card">
    <h2>Admin Login</h2><p class="sub">For maintenance only</p>
    <?php if($error): ?><div class="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
      <label>Admin Password *</label><input type="password" name="password" required placeholder="Enter admin password">
      <button class="btn btn-dark full">Login to Dashboard →</button>
</div>
</body></html>
