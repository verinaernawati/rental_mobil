<?php
// filepath: c:\xampp\htdocs\rental_mobil\transaction.php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
require 'config.php';

$message = '';

// Fetch merk-nama pairs for dropdown
$merk_nama_stmt = $pdo->query("SELECT merk, nama FROM merks ORDER BY merk ASC, nama ASC");
$merk_nama_list = $merk_nama_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all products for dropdown
$product_stmt = $pdo->query("SELECT merk, name, description FROM products ORDER BY merk ASC, name ASC");
$product_list = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nama_peminjam'], $_POST['no_hp'], $_POST['alamat'], $_POST['merk'], $_POST['nama'], $_POST['tanggal_pinjam'], $_POST['tanggal_kembali'])) {
    $nama_peminjam = trim($_POST['nama_peminjam']);
    $no_hp = trim($_POST['no_hp']);
    $alamat = trim($_POST['alamat']);
    $merk = trim($_POST['merk']);
    $nama = trim($_POST['nama']);
    $tanggal_pinjam = $_POST['tanggal_pinjam'];
    $tanggal_kembali = $_POST['tanggal_kembali'];

    if ($nama_peminjam && $no_hp && $alamat && $merk && $nama && $tanggal_pinjam && $tanggal_kembali) {
        $stmt = $pdo->prepare("INSERT INTO transactions (nama_peminjam, no_hp, alamat, merk, nama, tanggal_pinjam, tanggal_kembali) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$nama_peminjam, $no_hp, $alamat, $merk, $nama, $tanggal_pinjam, $tanggal_kembali])) {
            // Insert juga ke history_transactions
            $history_stmt = $pdo->prepare("INSERT INTO history_transactions (nama_peminjam, no_hp, alamat, merk, nama, tanggal_pinjam, tanggal_kembali, status_peminjaman) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $history_stmt->execute([$nama_peminjam, $no_hp, $alamat, $merk, $nama, $tanggal_pinjam, $tanggal_kembali, 'dipinjam']);

            $message = '<div class="alert alert-success">Transaksi berhasil disimpan!</div>';
        } else {
            $message = '<div class="alert alert-danger">Gagal menyimpan transaksi.</div>';
        }
    } else {
        $message = '<div class="alert alert-warning">Semua field wajib diisi.</div>';
    }
}

// Handle delete (batal) action
if (isset($_GET['batal']) && is_numeric($_GET['batal'])) {
    $id_batal = (int)$_GET['batal'];

    // Ambil data transaksi sebelum dihapus
    $trx_stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
    $trx_stmt->execute([$id_batal]);
    $trx = $trx_stmt->fetch(PDO::FETCH_ASSOC);

    // Hapus dari tabel transactions
    $del_stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
    if ($del_stmt->execute([$id_batal])) {
        // Update status di history_transactions jika data transaksi ditemukan
        if ($trx) {
            $update_history = $pdo->prepare("UPDATE history_transactions SET status_peminjaman = 'batal' WHERE 
                nama_peminjam = ? AND no_hp = ? AND alamat = ? AND merk = ? AND nama = ? AND tanggal_pinjam = ? AND tanggal_kembali = ? AND status_peminjaman = 'dipinjam' LIMIT 1");
            $update_history->execute([
                $trx['nama_peminjam'],
                $trx['no_hp'],
                $trx['alamat'],
                $trx['merk'],
                $trx['nama'],
                $trx['tanggal_pinjam'],
                $trx['tanggal_kembali']
            ]);
        }
        $message = '<div class="alert alert-success">Transaksi berhasil dibatalkan.</div>';
    } else {
        $message = '<div class="alert alert-danger">Gagal membatalkan transaksi.</div>';
    }
}

// Handle kembali (return) action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi_kembali'], $_POST['id_kembali'], $_POST['tanggal_aktual_kembali'])) {
    $id_kembali = (int)$_POST['id_kembali'];
    $tanggal_aktual_kembali = $_POST['tanggal_aktual_kembali'];

    // Ambil data transaksi
    $trx_stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
    $trx_stmt->execute([$id_kembali]);
    $trx = $trx_stmt->fetch(PDO::FETCH_ASSOC);

    if ($trx) {
        // Update status di history_transactions dan set tanggal aktual kembali
        $update_history = $pdo->prepare("UPDATE history_transactions SET status_peminjaman = 'kembali', tanggal_aktual_kembali = ? WHERE 
            nama_peminjam = ? AND no_hp = ? AND alamat = ? AND merk = ? AND nama = ? AND tanggal_pinjam = ? AND tanggal_kembali = ? AND status_peminjaman = 'dipinjam' LIMIT 1");
        $update_history->execute([
            $tanggal_aktual_kembali,
            $trx['nama_peminjam'],
            $trx['no_hp'],
            $trx['alamat'],
            $trx['merk'],
            $trx['nama'],
            $trx['tanggal_pinjam'],
            $trx['tanggal_kembali']
        ]);

        // Hapus dari tabel transactions
        $del_stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        $del_stmt->execute([$id_kembali]);

        $message = '<div class="alert alert-success">Transaksi berhasil dikembalikan.</div>';
    } else {
        $message = '<div class="alert alert-danger">Transaksi tidak ditemukan.</div>';
    }
}

// Pagination setup
$perPageOptions = [5, 10, 20, 50];
$perPage = isset($_GET['perPage']) && in_array((int)$_GET['perPage'], $perPageOptions) ? (int)$_GET['perPage'] : 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

// Get total transactions
$total_stmt = $pdo->query("SELECT COUNT(*) FROM transactions");
$total_transactions = $total_stmt->fetchColumn();
$total_pages = ceil($total_transactions / $perPage);

// Fetch transactions for current page
$trans_stmt = $pdo->prepare("SELECT * FROM transactions ORDER BY created_at DESC LIMIT :start, :perPage");
$trans_stmt->bindValue(':start', $start, PDO::PARAM_INT);
$trans_stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$trans_stmt->execute();
$transactions = $trans_stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rental Transaction</title>
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
        <!-- Form Transaksi -->
        <div class="col-md-5">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <h3 class="mb-4 text-center">Transaksi Rental</h3>
                    <?php echo $message; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Nama Peminjam</label>
                            <input type="text" name="nama_peminjam" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">No HP</label>
                            <input type="text" name="no_hp" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Merk</label>
                            <select name="merk" id="merk" class="form-control" required>
                                <option value="">-- Pilih Merk --</option>
                                <?php
                                $merk_options = [];
                                foreach ($product_list as $item) {
                                    if (!in_array($item['merk'], $merk_options)) {
                                        $merk_options[] = $item['merk'];
                                        echo '<option value="'.htmlspecialchars($item['merk']).'">'.htmlspecialchars($item['merk']).'</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>
                            <select name="nama" id="nama" class="form-control" required>
                                <option value="">-- Pilih Nama Produk --</option>
                                <!-- Akan diisi oleh JS -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Pinjam</label>
                            <input type="date" name="tanggal_pinjam" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tanggal Kembali</label>
                            <input type="date" name="tanggal_kembali" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Simpan Transaksi</button>
                    </form>
                </div>
            </div>
        </div>
        <!-- Tabel Transaksi -->
        <div class="col-md-7">
            <h4 class="mb-3 text-center">Daftar Transaksi yang Masih Berjalan</h4>
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
                            <th>Nama Peminjam</th>
                            <th>No HP</th>
                            <th>Alamat</th>
                            <th>Merk</th>
                            <th>Nama Produk</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Kembali</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $trx): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trx['nama_peminjam']); ?></td>
                                    <td><?php echo htmlspecialchars($trx['no_hp']); ?></td>
                                    <td><?php echo htmlspecialchars($trx['alamat']); ?></td>
                                    <td><?php echo htmlspecialchars($trx['merk']); ?></td>
                                    <td><?php echo htmlspecialchars($trx['nama']); ?></td>
                                    <td><?php echo htmlspecialchars($trx['tanggal_pinjam']); ?></td>
                                    <td><?php echo htmlspecialchars($trx['tanggal_kembali']); ?></td>
                                    <td>
                                        <a href="print_transaction.php?id=<?php echo $trx['id']; ?>" target="_blank" class="btn btn-outline-secondary btn-xs me-1 mb-1 px-2 py-1" style="font-size:0.85em;">Invoice</a>
                                        <a href="transaction.php?batal=<?php echo $trx['id']; ?>" class="btn btn-outline-danger btn-xs me-1 mb-1 px-2 py-1" style="font-size:0.85em;" onclick="return confirm('Yakin ingin membatalkan transaksi ini?')">Batal</a>
                                        <button type="button" class="btn btn-outline-success btn-xs mb-1 px-2 py-1" style="font-size:0.85em;" data-bs-toggle="modal" data-bs-target="#modalKembali<?php echo $trx['id']; ?>">Kembali</button>
                                    </td>
                                </tr>

                                <!-- Modal Kembali -->
                                <div class="modal fade" id="modalKembali<?php echo $trx['id']; ?>" tabindex="-1" aria-labelledby="modalKembaliLabel<?php echo $trx['id']; ?>" aria-hidden="true">
                                  <div class="modal-dialog">
                                    <form method="post" action="transaction.php">
                                      <input type="hidden" name="aksi_kembali" value="1">
                                      <input type="hidden" name="id_kembali" value="<?php echo $trx['id']; ?>">
                                      <div class="modal-content">
                                        <div class="modal-header">
                                          <h5 class="modal-title" id="modalKembaliLabel<?php echo $trx['id']; ?>">Input Tanggal Kembali Aktual</h5>
                                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                          <div class="mb-3">
                                            <label class="form-label">Tanggal Kembali Aktual</label>
                                            <input type="date" name="tanggal_aktual_kembali" class="form-control" required>
                                          </div>
                                        </div>
                                        <div class="modal-footer">
                                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                          <button type="submit" class="btn btn-success">Simpan</button>
                                        </div>
                                      </div>
                                    </form>
                                  </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">Belum ada transaksi.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <nav aria-label="Page navigation example">
                <ul class="pagination justify-content-center">
                    <li class="page-item<?php if($page == 1) echo ' disabled'; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&perPage=<?php echo $perPage; ?>" tabindex="-1">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item<?php if($page == $i) echo ' active'; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item<?php if($page == $total_pages) echo ' disabled'; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&perPage=<?php echo $perPage; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const productData = <?php echo json_encode($product_list); ?>;
document.getElementById('merk').addEventListener('change', function() {
    const merk = this.value;
    const namaSelect = document.getElementById('nama');
    namaSelect.innerHTML = '<option value="">-- Pilih Nama Produk --</option>';
    productData.forEach(function(item) {
        if (item.merk === merk) {
            const opt = document.createElement('option');
            opt.value = item.name; // only name will be submitted
            opt.textContent = item.name + ' | ' + (item.description ? item.description : '');
            namaSelect.appendChild(opt);
        }
    });
});
</script>
</body>
</html>