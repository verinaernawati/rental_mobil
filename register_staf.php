<?php
// filepath: c:\xampp\htdocs\rental_mobil\product.php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require 'config.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $message = "Username sudah digunakan.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, password, level) VALUES (?, ?, ?)");
        if ($stmt->execute([$username, $hashed, "staf"])) {
            $message = "Registrasi berhasil. <a href='dashboard.php'>Kembali ke dashboard</a>.";
        } else {
            $message = "Registrasi gagal.";
        }
    }
}


?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Product</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="#">FaVe Rental Mobil</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <?php 
        // Ambil data user atau staf
           // Lebih aman:
                $range_stmt = $pdo->prepare("SELECT level FROM users WHERE username = :user");
                $range_stmt->execute(['user' => $_SESSION['user']]);
                $range = $range_stmt->fetch(PDO::FETCH_ASSOC);
                $level = $range['level'];

            if ($level == "admin") {
                ?>
                <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="product.php">Register Product</a></li>
                <li class="nav-item"><a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'transaction.php') echo ' active'; ?>" href="transaction.php">Rental Transaction</a></li>
                <li class="nav-item"><a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'history.php') echo ' active'; ?>" href="history.php">History</a></li>
                <li class="nav-item"><a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'register_staf.php') echo ' active'; ?>" href="register_staf.php">Register Staff</a></li>
            </ul>
            <span class="navbar-text text-white me-3">
                Halo, <?php echo htmlspecialchars($_SESSION['user']); ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
                <?php
            } else {
                ?>
                <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'transaction.php') echo ' active'; ?>" href="transaction.php">Rental Transaction</a></li>
                <li class="nav-item"><a class="nav-link<?php if(basename($_SERVER['PHP_SELF']) == 'history.php') echo ' active'; ?>" href="history.php">History</a></li>
                
            </ul>
            <span class="navbar-text text-white me-3">
                Halo, <?php echo htmlspecialchars($_SESSION['user']); ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
        </div>
                <?php
            }
        ?>
        
    </div>
</nav>
<div class="container mt-5">
    <div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title mb-4 text-center">Register Staff</h3>
                    <?php if ($message): ?>
                        <div class="alert alert-info"><?php echo $message; ?></div>
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
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    <p class="mt-3 text-center">
                         <a href="dashboard.php">Kembali</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

</body>
</html>