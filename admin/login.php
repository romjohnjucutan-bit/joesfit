<?php
require_once '../includes/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: /joesfit/admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $staff = $stmt->fetch();

        if ($staff && password_verify($password, $staff['password'])) {
            $_SESSION['admin_id'] = $staff['id'];
            $_SESSION['admin']    = [
                'id'    => $staff['id'],
                'name'  => $staff['name'],
                'email' => $staff['email'],
                'role'  => $staff['role'],
            ];
            $pdo->prepare("UPDATE staff SET last_login = NOW() WHERE id = ?")->execute([$staff['id']]);
            header('Location: /joesfit/admin/index.php');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    } else {
        $error = 'Please enter both email and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Joe's Fit</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@400;600;700&display=swap">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'DM Sans', sans-serif;
      background: #0f0f12;
      color: #f0f0f5;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    body::before {
      content: '';
      position: fixed; inset: 0;
      background: radial-gradient(ellipse at 30% 50%, rgba(232,50,26,0.08) 0%, transparent 60%),
                  radial-gradient(ellipse at 70% 80%, rgba(59,130,246,0.05) 0%, transparent 50%);
    }
    .login-wrap {
      display: grid;
      grid-template-columns: 1fr 1fr;
      max-width: 900px;
      width: 100%;
      margin: 1rem;
      background: #16161c;
      border: 1px solid #2a2a38;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 20px 80px rgba(0,0,0,0.5);
      position: relative; z-index: 1;
    }
    .login-brand {
      background: linear-gradient(135deg, #1a0a08 0%, #2a1208 50%, #111 100%);
      padding: 3rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: flex-start;
      border-right: 1px solid #2a2a38;
      position: relative;
      overflow: hidden;
    }
    .login-brand::before {
      content: 'ADMIN';
      position: absolute;
      right: -2rem;
      top: 50%;
      transform: translateY(-50%) rotate(90deg);
      font-family: 'Bebas Neue', sans-serif;
      font-size: 8rem;
      letter-spacing: 10px;
      color: rgba(232,50,26,0.06);
      white-space: nowrap;
    }
    .brand-logo {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 3.5rem;
      letter-spacing: 3px;
      line-height: 1;
      margin-bottom: 1rem;
    }
    .brand-logo span { color: #e8321a; }
    .brand-tagline {
      font-size: 0.8rem;
      font-weight: 600;
      letter-spacing: 4px;
      text-transform: uppercase;
      color: rgba(255,255,255,0.4);
      margin-bottom: 2rem;
    }
    .brand-desc {
      font-size: 0.88rem;
      line-height: 1.7;
      color: rgba(255,255,255,0.5);
    }
    .login-form-wrap {
      padding: 3rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .form-title {
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 3px;
      text-transform: uppercase;
      color: #e8321a;
      margin-bottom: 0.5rem;
    }
    .form-heading {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 2.2rem;
      letter-spacing: 2px;
      margin-bottom: 2rem;
    }
    .form-group { margin-bottom: 1.2rem; }
    .form-label {
      display: block;
      font-size: 0.72rem;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: #8888aa;
      margin-bottom: 0.4rem;
    }
    .form-control {
      width: 100%;
      padding: 0.8rem 1rem;
      background: #1e1e27;
      border: 1.5px solid #2a2a38;
      border-radius: 8px;
      color: #f0f0f5;
      font-size: 0.95rem;
      font-family: inherit;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus {
      outline: none;
      border-color: #e8321a;
      box-shadow: 0 0 0 3px rgba(232,50,26,0.2);
    }
    .btn-login {
      width: 100%;
      padding: 0.9rem;
      background: #e8321a;
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 700;
      letter-spacing: 1px;
      text-transform: uppercase;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 0.5rem;
      font-family: inherit;
    }
    .btn-login:hover {
      background: #c42a14;
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(232,50,26,0.4);
    }
    .error-msg {
      background: rgba(239,68,68,0.1);
      border: 1px solid #ef4444;
      border-radius: 8px;
      padding: 0.8rem 1rem;
      font-size: 0.85rem;
      color: #ef4444;
      margin-bottom: 1.5rem;
    }
    .hint {
      text-align: center;
      font-size: 0.78rem;
      color: #555575;
      margin-top: 1.5rem;
    }
    .hint a { color: #e8321a; }
    @media (max-width: 640px) {
      .login-wrap { grid-template-columns: 1fr; }
      .login-brand { display: none; }
    }
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-brand">
    <div class="brand-logo">JOE'S<br><span>FIT</span></div>
    <div class="brand-tagline">Admin Portal</div>
    <div class="brand-desc">
      Manage your store, track orders, and grow your business from one powerful dashboard.
    </div>
  </div>
  <div class="login-form-wrap">
    <div class="form-title">// Secure Access</div>
    <div class="form-heading">SIGN IN</div>

    <?php if ($error): ?>
      <div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" name="email" class="form-control" placeholder="admin@joesfit.com"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn-login">Sign In →</button>
    </form>

    <div class="hint">
      Default: admin@joesfit.com / <strong>password</strong><br>
      <a href="/joesfit/">← Back to Store</a>
    </div>
  </div>
</div>
</body>
</html>
