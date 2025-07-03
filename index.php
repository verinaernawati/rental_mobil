<?php
// filepath: c:\xampp\htdocs\rental_mobil\login.php

require 'config.php';
session_start();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $message = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body style="background: linear-gradient(135deg,rgb(255, 255, 255) 0%,rgb(255, 255, 255) 100%) !important;">
    <div class="text-center mb-3" style="margin-top:40px;">
        <img src="img/Simarem2.jpg" alt="Logo FaVe" style="max-width:500px;max-height:300px; border:4px solid #fff; padding:8px; background:#fff;">
    </div>
    <div class="container mt-3">
        <div class="row justify-content-center">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-body">
                        <h3 class="card-title mb-4 text-center">Login</h3>
                        <?php if ($message): ?>
                            <div class="alert alert-danger"><?php echo $message; ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        
                    </div>
                    <label class="form-label text-center">User Admin : verina </label>
                    <label class="form-label text-center">password : 123</label>
                </div>
            </div>
        </div>
    </div>
</body>
</html>