<?php
// filepath: c:\xampp\htdocs\rental_mobil\product.php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require 'config.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['merk'], $_POST['name'], $_POST['price'], $_POST['description'])) {
    $merk = trim($_POST['merk']);
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);

    if ($merk && $name && $price) {
        $stmt = $pdo->prepare("INSERT INTO products (merk, name, price, description) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$merk, $name, $price, $description])) {
            $message = '<div class="alert alert-success">Produk berhasil didaftarkan!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal mendaftarkan produk.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Semua field wajib diisi.</div>';
    }
}

// Delete product
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$delete_id]);
    $message = '<div class="alert alert-success">Produk berhasil dihapus.</div>';
}

// Pagination setup
$perPageOptions = [5, 10, 20, 50];
$perPage = isset($_GET['perPage']) && in_array((int)$_GET['perPage'], $perPageOptions) ? (int)$_GET['perPage'] : 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

// Get total products
$total_stmt = $pdo->query("SELECT COUNT(*) FROM products");
$total_products = $total_stmt->fetchColumn();
$total_pages = ceil($total_products / $perPage);

// Fetch products for current page
$stmt = $pdo->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT :start, :perPage");
$stmt->bindValue(':start', $start, PDO::PARAM_INT);
$stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch merk options
$merk_stmt = $pdo->query("SELECT merk FROM merks GROUP BY merk ORDER BY merk ASC");
$merk_list = $merk_stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch nama options
$nama_stmt = $pdo->query("SELECT nama FROM merks ORDER BY nama ASC");
$nama_list = $nama_stmt->fetchAll(PDO::FETCH_COLUMN);

// Fetch all merk-nama pairs
$merk_nama_stmt = $pdo->query("SELECT merk, nama FROM merks ORDER BY merk ASC, nama ASC");
$merk_nama_list = $merk_nama_stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="row">
        <!-- Form Registrasi Produk -->
        <div class="col-md-5">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h3 class="mb-4 text-center">Register Produk</h3>
                    <?php echo $message; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Merk</label>
                            <select name="merk" id="merk" class="form-control" required>
                                <option value="">-- Pilih Merk --</option>
                                <?php foreach ($merk_list as $merk): ?>
                                    <option value="<?php echo htmlspecialchars($merk); ?>"><?php echo htmlspecialchars($merk); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <select name="name" id="nama" class="form-control" required>
                                <option value="">-- Pilih Nama Produk --</option>
                                <!-- Options will be filled by JS -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Harga</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" name="price_display" id="price_display" class="form-control" required>
                                <input type="hidden" name="price" id="price">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea name="description" class="form-control"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Daftarkan Produk</button>
                    </form>
                    <a href="dashboard.php" class="btn btn-link mt-3">Kembali ke Dashboard</a>
                </div>
            </div>
        </div>
        <!-- Tabel Produk -->
        <div class="col-md-7">
            <h4 class="mb-3 text-center">Daftar Produk</h4>
            <!-- Pilih jumlah data per halaman -->
            <form method="get" class="mb-3 d-flex justify-content-end align-items-center">
                <label class="me-2">Tampilkan</label>
                <select name="perPage" class="form-select w-auto me-2" onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?php echo $opt; ?>" <?php if ($perPage == $opt) echo 'selected'; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
                <label>data per halaman</label>
                <?php if (isset($_GET['page'])): ?>
                    <input type="hidden" name="page" value="<?php echo (int)$_GET['page']; ?>">
                <?php endif; ?>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-primary">
                        <tr>
                            <!--<th>ID</th>-->
                            <th>Merk</th>
                            <th>Nama</th>
                            <th>Harga</th>
                            <th>Deskripsi</th>
                            <!--<th>Created At</th>-->
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($products) > 0): ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <!--<td><?php echo $product['id']; ?></td>-->
                                    <td><?php echo htmlspecialchars($product['merk']); ?></td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>Rp <?php echo number_format($product['price'], 2, ',', '.'); ?></td>
                                    <td><?php echo htmlspecialchars($product['description']); ?></td>
                                    <!--<td><?php echo $product['created_at']; ?></td>-->
                                    <td>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Yakin hapus produk ini?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">Belum ada produk.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <nav>
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>
<script>
const merkNamaData = <?php echo json_encode($merk_nama_list); ?>;

document.getElementById('merk').addEventListener('change', function() {
    const merk = this.value;
    const namaSelect = document.getElementById('nama');
    namaSelect.innerHTML = '<option value="">-- Pilih Nama Produk --</option>';
    merkNamaData.forEach(function(item) {
        if (item.merk === merk) {
            const opt = document.createElement('option');
            opt.value = item.nama;
            opt.textContent = item.nama;
            namaSelect.appendChild(opt);
        }
    });
});

// Format harga ke Rupiah
const priceDisplay = document.getElementById('price_display');
const priceHidden = document.getElementById('price');
priceDisplay.addEventListener('input', function(e) {
    let value = this.value.replace(/[^0-9]/g, '');
    if (value) {
        this.value = parseInt(value, 10).toLocaleString('id-ID');
        priceHidden.value = value;
    } else {
        this.value = '';
        priceHidden.value = '';
    }
});
</script>
</body>
</html>