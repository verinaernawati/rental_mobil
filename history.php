<?php
session_start();
require 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Filter bulan & tahun
$bulan = isset($_GET['bulan']) ? (int)$_GET['bulan'] : (int)date('m');
$tahun = isset($_GET['tahun']) ? (int)$_GET['tahun'] : (int)date('Y');

// Hitung awal dan akhir bulan
$fromDate = sprintf('%04d-%02d-01', $tahun, $bulan);
$toDate = date('Y-m-t', strtotime($fromDate));

// Pagination
$perPage = isset($_GET['perPage']) ? (int)$_GET['perPage'] : 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$start = ($page - 1) * $perPage;

// Filter nama
$filter_nama = isset($_GET['nama_peminjam']) ? trim($_GET['nama_peminjam']) : '';

// Query total
$where = "WHERE h.tanggal_pinjam BETWEEN :fromDate AND :toDate";
$params = [
    ':fromDate' => $fromDate,
    ':toDate' => $toDate
];
if ($filter_nama) {
    $where .= " AND h.nama_peminjam LIKE :nama_peminjam";
    $params[':nama_peminjam'] = "%$filter_nama%";
}
$total_sql = "SELECT COUNT(*) FROM history_transactions h $where";
$total_stmt = $pdo->prepare($total_sql);
$total_stmt->execute($params);
$total = $total_stmt->fetchColumn();
$total_pages = ceil($total / $perPage);

// Query data
$data_sql = "SELECT h.*, p.price 
    FROM history_transactions h
    INNER JOIN products p 
        ON h.merk = p.merk AND h.nama = p.name
    $where
    GROUP BY h.id
    ORDER BY h.tanggal_pinjam ASC LIMIT :start, :perPage";
$data_stmt = $pdo->prepare($data_sql);
foreach ($params as $key => $val) {
    $data_stmt->bindValue($key, $val);
}
$data_stmt->bindValue(':start', $start, PDO::PARAM_INT);
$data_stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$data_stmt->execute();
$rows = $data_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>History Peminjaman Mobil</title>
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
<div class="container">
    <h3 class="mb-4">History Peminjaman Mobil</h3>
    <form class="row g-2 mb-3 align-items-end" method="get">
        <div class="col-auto">
            <label class="form-label mb-0">Bulan</label>
            <select name="bulan" class="form-select">
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    $selected = $i == $bulan ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>" . date('F', mktime(0,0,0,$i,1)) . "</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">Tahun</label>
            <select name="tahun" class="form-select">
                <?php
                $yearNow = date('Y');
                for ($y = $yearNow - 5; $y <= $yearNow + 2; $y++) {
                    $selected = $y == $tahun ? 'selected' : '';
                    echo "<option value=\"$y\" $selected>$y</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">&nbsp;</label>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        <div class="col-auto">
            <input type="text" name="nama_peminjam" class="form-control" placeholder="Cari Nama Peminjam" value="<?php echo htmlspecialchars($filter_nama); ?>">
        </div>
        <div class="col-auto ms-auto">
            <select name="perPage" class="form-select w-auto" onchange="this.form.submit()">
                <?php foreach ([5,10,20,50] as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php if ($perPage == $opt) echo 'selected'; ?>><?php echo $opt; ?> data</option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-primary">
                <tr>
                    <th>No</th>
                    <th>Nama Peminjam</th>
                    <th>Merk</th>
                    <th>Nama Mobil</th>
                    <th>Tgl Pinjam</th>
                    <th>Tgl Kembali</th>
                    <th>Tgl Aktual Kembali</th>
                    <th>Status</th>
                    <th>Total Bayar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($rows) > 0): $no = $start + 1; ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($row['nama_peminjam']); ?></td>
                            <td><?php echo htmlspecialchars($row['merk']); ?></td>
                            <td><?php echo htmlspecialchars($row['nama']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_pinjam'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($row['tanggal_kembali'])); ?></td>
                            <td>
                                <?php echo $row['tanggal_aktual_kembali'] ? date('d/m/Y', strtotime($row['tanggal_aktual_kembali'])) : '<span class="text-danger">Belum Kembali</span>'; ?>
                            </td>
                            <td>
                                <?php
                                if ($row['status_peminjaman'] == 'kembali'): ?>
                                    <span class="badge bg-success">Kembali</span>
                                <?php elseif ($row['status_peminjaman'] == 'batal'): ?>
                                    <span class="badge bg-danger">Batal Pinjam</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Dipinjam</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                // Hitung total bayar jika tanggal_aktual_kembali sudah ada
                                if ($row['tanggal_aktual_kembali']) {
                                    $tgl_pinjam = new DateTime($row['tanggal_pinjam']);
                                    $tgl_kembali = new DateTime($row['tanggal_aktual_kembali']);
                                    $lama_pinjam = $tgl_pinjam->diff($tgl_kembali)->days + 1;
                                    $harga = isset($row['price']) ? $row['price'] : 0;
                                    echo 'Rp ' . number_format($lama_pinjam * $harga, 0, ',', '.');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">Tidak ada data history.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?>&bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?><?php if($filter_nama) echo '&nama_peminjam='.urlencode($filter_nama); ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
</body>
</html>