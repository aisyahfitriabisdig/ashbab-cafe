<?php
/**
 * ASHBAB CAFE - Login Page
 * File: login.php
 */
require_once __DIR__ . '/includes/auth.php';

// Already logged in? redirect
if (isLoggedIn()) {
    redirect(APP_URL . '/' . ($_SESSION['user_role'] === 'admin' ? 'admin' : 'cashier') . '/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // FORCE BYPASS CSRF
    if (false && !verifyCSRF($_POST['csrf_token'] ?? '')) {
        $error = 'Token keamanan tidak valid.';
    } else {
        $username = clean($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (login($username, $password)) {
            $role = $_SESSION['user_role'];
            redirect(APP_URL . '/' . ($role === 'admin' ? 'admin' : 'cashier') . '/dashboard.php');
        } else {
            $error = 'Username atau password salah.';
        }
    }
}

$settings = getSettings();
$cafeName = $settings['cafe_name'] ?? 'ASHBAB CAFE';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= e($cafeName) ?></title>
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/uploads/logo/favicon_round.png?v=2">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}
        body{font-family:'Poppins',sans-serif;background:linear-gradient(135deg,#2C1810 0%,#4a2510 40%,#8B4513 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
        .login-card{background:white;border-radius:24px;padding:48px 40px;width:100%;max-width:420px;box-shadow:0 24px 64px rgba(0,0,0,.25)}
        .login-logo{font-size:3.5rem;margin-bottom:8px}
        .login-title{font-size:1.6rem;font-weight:800;color:#2C1810;margin-bottom:4px}
        .login-sub{color:#888;font-size:.9rem;margin-bottom:32px}
        .form-label{font-weight:600;font-size:.85rem;color:#374151;margin-bottom:6px}
        .form-control{border:2px solid #e2e8f0;border-radius:12px;padding:12px 16px;font-family:'Poppins',sans-serif;font-size:.9rem;transition:all .2s}
        .form-control:focus{border-color:#8B4513;box-shadow:0 0 0 3px rgba(139,69,19,.1);outline:none}
        .input-group .form-control{border-right:none;border-radius:12px 0 0 12px}
        .input-group .btn{border:2px solid #e2e8f0;border-left:none;border-radius:0 12px 12px 0;background:white;color:#888;transition:all .2s}
        .input-group .btn:hover{color:#8B4513}
        .input-group .form-control:focus+.btn,.input-group .form-control:focus~.btn{border-color:#8B4513}
        .btn-login{background:linear-gradient(135deg,#8B4513,#D2691E);color:white;border:none;padding:14px;border-radius:12px;font-weight:700;font-size:1rem;width:100%;cursor:pointer;transition:all .3s;font-family:'Poppins',sans-serif}
        .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 24px rgba(139,69,19,.35)}
        .alert-error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);color:#dc2626;border-radius:12px;padding:12px 16px;font-size:.88rem;margin-bottom:24px}
        .back-link{display:block;text-align:center;margin-top:20px;color:#888;font-size:.85rem;text-decoration:none;transition:color .2s}
        .back-link:hover{color:#8B4513}
        .divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:#ccc;font-size:.8rem}
        .divider::before,.divider::after{content:'';flex:1;height:1px;background:#e2e8f0}
    </style>
</head>
<body>
<div class="login-card">
    <div class="text-center">
        <?php if (!empty($settings['logo'])): ?>
            <img src="<?= e(assetUrl($settings['logo'])) ?>" alt="Logo" style="width:70px;height:70px;border-radius:50%;object-fit:cover;margin-bottom:12px">
        <?php else: ?>
            <div class="login-logo">☕</div>
        <?php endif; ?>
        <div class="login-title"><?= e($cafeName) ?></div>
        <div class="login-sub">Masuk ke Panel Manajemen</div>
    </div>

    <?php if ($error): ?>
    <div class="alert-error"><i class="fa-solid fa-circle-xmark me-2"></i><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <?= csrfField() ?>

        <div class="mb-3">
            <label class="form-label">Username</label>
            <input type="text" class="form-control" name="username" value="<?= e($_POST['username'] ?? '') ?>"
                   placeholder="Masukkan username" required autocomplete="username">
        </div>

        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="passwordInput"
                       placeholder="Masukkan password" required autocomplete="current-password">
                <button type="button" class="btn" onclick="togglePassword()">
                    <i class="fa-solid fa-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Masuk
        </button>
    </form>

    <div class="divider">atau</div>
    <div class="text-center text-muted" style="font-size:.82rem">
        <i class="fa-solid fa-circle-info me-1"></i>
        Default: admin / password &nbsp;|&nbsp; kasir / password
    </div>

    <a href="<?= APP_URL ?>/" class="back-link">
        <i class="fa-solid fa-arrow-left me-2"></i>Kembali ke Website
    </a>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}
</script>
</body>
</html>
