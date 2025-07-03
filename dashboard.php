<?php
// filepath: c:\xampp\htdocs\rental_mobil\dashboard.php

session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Tambahkan header cache control agar tidak bisa akses kembali setelah logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require 'config.php';

// Pagination setup
$perPageOptions = [5, 10, 20, 50];
$perPage = isset($_GET['perPage']) && in_array((int)$_GET['perPage'], $perPageOptions) ? (int)$_GET['perPage'] : 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$start = ($page - 1) * $perPage;

// Get total summary rows
$total_stmt = $pdo->query("
    SELECT COUNT(*) FROM (
        SELECT p.merk, p.name
        FROM products p
        GROUP BY p.merk, p.name
    ) AS summary
");
$total_summary = $total_stmt->fetchColumn();
$total_pages = ceil($total_summary / $perPage);

// Ambil filter tanggal dari GET, default ke hari ini jika kosong
$fromDate = isset($_GET['from_date']) && $_GET['from_date'] !== '' ? $_GET['from_date'] : date('Y-m-d');
$toDate = isset($_GET['to_date']) && $_GET['to_date'] !== '' ? $_GET['to_date'] : date('Y-m-d');

// Query summary produk with filter tanggal
$summary_sql = "
    SELECT 
        p.merk, 
        p.name, 
        COUNT(*) 
            - COALESCE((
                SELECT COUNT(*) 
                FROM transactions t 
                WHERE t.merk = p.merk AND t.nama = p.name
                " . (
                    ($fromDate && $toDate)
                    ? "AND (
                        (t.tanggal_pinjam <= :toDate AND t.tanggal_kembali >= :fromDate)
                    )"
                    : ""
                ) . "
            ), 0) AS stock
    FROM products p
    GROUP BY p.merk, p.name
    ORDER BY p.merk, p.name
    LIMIT :start, :perPage
";
$summary_stmt = $pdo->prepare($summary_sql);
if ($fromDate && $toDate) {
    $summary_stmt->bindValue(':fromDate', $fromDate);
    $summary_stmt->bindValue(':toDate', $toDate);
}
$summary_stmt->bindValue(':start', $start, PDO::PARAM_INT);
$summary_stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$summary_stmt->execute();
$summary = $summary_stmt->fetchAll(PDO::FETCH_ASSOC);

// Query untuk jumlah mobil terpinjam per produk sesuai filter tanggal
$pinjam_sql = "
    SELECT 
        p.merk, 
        p.name, 
        COALESCE((
            SELECT COUNT(*) 
            FROM transactions t 
            WHERE t.merk = p.merk AND t.nama = p.name
            " . (
                ($fromDate && $toDate)
                ? "AND (
                    (t.tanggal_pinjam <= :toDate AND t.tanggal_kembali >= :fromDate)
                )"
                : ""
            ) . "
        ), 0) AS terpinjam
    FROM products p
    GROUP BY p.merk, p.name
    ORDER BY p.merk, p.name
    LIMIT :start, :perPage
";
$pinjam_stmt = $pdo->prepare($pinjam_sql);
if ($fromDate && $toDate) {
    $pinjam_stmt->bindValue(':fromDate', $fromDate);
    $pinjam_stmt->bindValue(':toDate', $toDate);
}
$pinjam_stmt->bindValue(':start', $start, PDO::PARAM_INT);
$pinjam_stmt->bindValue(':perPage', $perPage, PDO::PARAM_INT);
$pinjam_stmt->execute();
$pinjam_data = $pinjam_stmt->fetchAll(PDO::FETCH_ASSOC);

// Siapkan data untuk chart
$chartLabels = [];
$chartStock = [];
$chartPinjam = [];
$chartPinjamRange = [];

foreach ($summary as $i => $row) {
    $chartLabels[] = $row['merk'] . ' - ' . $row['name'];
    $chartStock[] = (int)$row['stock'];
    $chartPinjam[] = isset($pinjam_data[$i]) ? (int)$pinjam_data[$i]['terpinjam'] : 0;

    // Ambil tanggal pinjam & kembali dari transaksi untuk produk ini
    $range_stmt = $pdo->prepare("SELECT MIN(tanggal_pinjam) as min_pinjam, MAX(tanggal_kembali) as max_kembali FROM transactions WHERE merk = ? AND nama = ? AND (tanggal_pinjam <= ? AND tanggal_kembali >= ?)");
    $range_stmt->execute([$row['merk'], $row['name'], $toDate, $fromDate]);
    $range = $range_stmt->fetch(PDO::FETCH_ASSOC);
    if ($range && $range['min_pinjam'] && $range['max_kembali']) {
        $chartPinjamRange[] = date('d-m-Y', strtotime($range['min_pinjam'])) . ' s/d ' . date('d-m-Y', strtotime($range['max_kembali']));
    } else {
        $chartPinjamRange[] = '-';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const chartLabels = <?php echo json_encode($chartLabels); ?>;
    const chartStock = <?php echo json_encode($chartStock); ?>;
    const chartPinjam = <?php echo json_encode($chartPinjam); ?>;
    const chartPinjamRange = <?php echo json_encode($chartPinjamRange); ?>;
    </script>
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
    <!-- Filter tanggal -->
    <form method="get" class="row g-2 mb-3 align-items-end">
        <div class="col-auto">
            <label class="form-label mb-0">From Date</label>
            <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($fromDate); ?>">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">To Date</label>
            <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($toDate); ?>">
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">&nbsp;</label>
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
        <div class="col-auto">
            <label class="form-label mb-0">&nbsp;</label>
            <a href="dashboard.php" class="btn btn-secondary">Reset</a>
        </div>
        <div class="col-auto ms-auto">
            <label class="form-label mb-0">Tampilkan</label>
            <select name="perPage" class="form-select w-auto d-inline" onchange="this.form.submit()">
                <?php foreach ($perPageOptions as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php if ($perPage == $opt) echo 'selected'; ?>><?php echo $opt; ?></option>
                <?php endforeach; ?>
            </select>
            <span>data per halaman</span>
            <?php if (isset($_GET['page'])): ?>
                <input type="hidden" name="page" value="<?php echo (int)$_GET['page']; ?>">
            <?php endif; ?>
        </div>
    </form>
    <!-- Summary Table -->
    <h4 class="mb-3">Stock Mobil Tersedia</h4>
    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-primary">
                <tr>
                    <th>Merk</th>
                    <th>Nama</th>
                    <th>Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($summary) > 0): ?>
                    <?php foreach ($summary as $row): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['merk']); ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo $row['stock']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center">Belum ada data produk.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <nav>
        <ul class="pagination justify-content-center">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item<?php if ($i == $page) echo ' active'; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&perPage=<?php echo $perPage; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <div class="row">
        <div class="col-lg-7">
            <!-- Chart -->
            <h4 class="mb-3">Summary Stock Mobil (Chart)</h4>
            <div class="d-flex justify-content-start">
                <div style="width: 700px; max-width: 100%;">
                    <canvas id="summaryChart" height="280"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <!-- Mobil Dipinjam Belum Kembali -->
            <div class="card mb-4">
                <div class="card-body pb-2">
                    <?php
                    // Hitung jumlah mobil belum kembali per hari ini
                    $today = date('Y-m-d');
                    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM history_transactions WHERE tanggal_aktual_kembali IS NULL AND status_peminjaman = 'dipinjam'");
                    $stmt_count->execute();
                    $jumlah_belum_kembali = $stmt_count->fetchColumn();
                    ?>
                    <div class="mb-2 fw-bold">
                        Transaksi Masih Berjalan : <span class="text-danger"><?php echo $jumlah_belum_kembali; ?></span>
                    </div>
                </div>
                <ul class="list-group list-group-flush" style="max-height:350px;overflow:auto;">
                    <?php
                    // Ambil data mobil yang belum kembali dari history_transactions
                    $today = date('Y-m-d');
                    $stmt = $pdo->prepare("SELECT nama_peminjam, merk, nama, tanggal_pinjam, tanggal_kembali 
                        FROM history_transactions 
                        WHERE tanggal_aktual_kembali IS NULL 
                        AND status_peminjaman = 'dipinjam'
                        ORDER BY tanggal_kembali ASC");
                    $stmt->execute();
                    $belum_kembali = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (count($belum_kembali) > 0):
                        foreach ($belum_kembali as $row):
                            $isOverdue = strtotime($row['tanggal_kembali']) < strtotime($today);
                    ?>
                    <li class="list-group-item small">
                        <b><?php echo htmlspecialchars($row['merk']); ?> - <?php echo htmlspecialchars($row['nama']); ?></b>
                        <?php if ($isOverdue): ?>
                            <span class="badge bg-danger ms-2">Over DueDate</span>
                        <?php endif; ?><br>
                        Peminjam: <?php echo htmlspecialchars($row['nama_peminjam']); ?><br>
                        Pinjam: <?php echo date('d/m/Y', strtotime($row['tanggal_pinjam'])); ?>,
                        Harus Kembali: <span class="text-danger"><?php echo date('d/m/Y', strtotime($row['tanggal_kembali'])); ?></span>
                    </li>
                    <?php
                        endforeach;
                    else:
                    ?>
                    <li class="list-group-item text-center text-muted">Tidak ada mobil yang belum kembali.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<script>
const ctx = document.getElementById('summaryChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Stock Tersedia',
                data: chartStock,
                backgroundColor: 'rgba(54, 162, 235, 0.7)'
            },
            {
                label: 'Terpinjam',
                data: chartPinjam,
                backgroundColor: 'rgba(255, 99, 132, 0.7)'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true },
            tooltip: {
                callbacks: {
                    afterBody: function(context) {
                        if (context[0].dataset.label === 'Terpinjam') {
                            const idx = context[0].dataIndex;
                            return 'Rentang Pinjam: ' + chartPinjamRange[idx];
                        }
                        return '';
                    }
                }
            }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>