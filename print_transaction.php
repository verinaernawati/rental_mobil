<?php
require 'config.php';
require_once __DIR__ . '/vendor/dompdf/autoload.inc.php'; // pastikan path dompdf benar

use Dompdf\Dompdf;

if (!isset($_GET['id'])) {
    die('ID transaksi tidak ditemukan.');
}

$id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$stmt->execute([$id]);
$trx = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$trx) {
    die('Transaksi tidak ditemukan.');
}

// Ambil harga dari tabel products
$harga = '-';
$total_harga = '-';
$product_stmt = $pdo->prepare("SELECT price FROM products WHERE merk = ? AND name = ? LIMIT 1");
$product_stmt->execute([$trx['merk'], $trx['nama']]);
$product = $product_stmt->fetch(PDO::FETCH_ASSOC);
if ($product) {
    $harga = $product['price'];
    // Hitung jumlah hari sewa
    $tgl_pinjam = new DateTime($trx['tanggal_pinjam']);
    $tgl_kembali = new DateTime($trx['tanggal_kembali']);
    $interval = $tgl_pinjam->diff($tgl_kembali)->days;
    if ($interval < 1) $interval = 1; // minimal 1 hari
    $total_harga = 'Rp ' . number_format($harga * $interval, 0, ',', '.');
    $harga = 'Rp ' . number_format($harga, 0, ',', '.');
} else {
    $total_harga = '-';
}

$logo_path = __DIR__ . '/img/icon_fave.jpg';
$logo_data = '';
if (file_exists($logo_path)) {
    $logo_data = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logo_path));
}

$html = '
<style>
    body { font-family: Arial, sans-serif; color: #222; }
    .invoice-box {
        max-width: 700px;
        margin: auto;
        padding: 30px 20px;
        border: 1px solid #eee;
        box-shadow: 0 0 10px rgba(0,0,0,.15);
        font-size: 16px;
        line-height: 24px;
        background: #fff;
    }
    .invoice-header-table {
        width: 100%;
        margin-bottom: 15px;
    }
    .invoice-header-table td {
        vertical-align: middle;
    }
    .invoice-header-table .logo-cell {
        text-align: right;
        width: 180px;
    }
    .invoice-header-table .title-cell {
        text-align: left;
    }
    .invoice-header-table img {
        height: 90px; /* sebelumnya 60px, sekarang lebih besar */
    }
    .invoice-title {
        font-size: 28px;
        font-weight: bold;
        color: #2a2a2a;
        margin-bottom: 2px;
    }
    .company-info {
        font-size: 14px;
        color: #888;
        margin-bottom: 20px;
    }
    .invoice-box table {
        width: 100%;
        line-height: inherit;
        text-align: left;
        border-collapse: collapse;
    }
    .invoice-box table td {
        padding: 8px 5px;
        vertical-align: top;
    }
    .invoice-box table tr.heading td {
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
        font-weight: bold;
    }
    .invoice-box table tr.item td{
        border-bottom: 1px solid #eee;
    }
    .total-row td {
        font-weight: bold;
        border-top: 2px solid #222;
        background: #f9f9f9;
    }
</style>
<div class="invoice-box">
    <table class="invoice-header-table">
        <tr>
            <td class="title-cell">
                <div class="invoice-title">INVOICE RENTAL MOBIL</div>
                <div class="company-info">
                    FaVe Rental Mobil<br>
                    Jl. Contoh Alamat No. 123, Kota<br>
                    Telp: 0812-3456-7890
                </div>
            </td>
            <td class="logo-cell">
                <img src="' . $logo_data . '" alt="Logo" />
            </td>
        </tr>
    </table>
    <table>
        <tr>
            <td>
                <b>Nama Peminjam:</b> ' . htmlspecialchars($trx['nama_peminjam']) . '<br>
                <b>No HP:</b> ' . htmlspecialchars($trx['no_hp']) . '<br>
                <b>Alamat:</b> ' . htmlspecialchars($trx['alamat']) . '<br>
            </td>
            <td style="text-align:right;">
                <b>No. Transaksi:</b> #' . $trx['id'] . '<br>
                <b>Tanggal Input:</b> ' . date('d/m/Y H:i', strtotime($trx['created_at'])) . '
            </td>
        </tr>
    </table>
    <br>
    <table>
        <tr class="heading">
            <td>Mobil</td>
            <td>Periode</td>
            <td>Harga/Hari</td>
            <td>Lama</td>
            <td>Total</td>
        </tr>
        <tr class="item">
            <td>
                ' . htmlspecialchars($trx['merk']) . ' - ' . htmlspecialchars($trx['nama']) . '
            </td>
            <td>
                ' . date('d/m/Y', strtotime($trx['tanggal_pinjam'])) . ' - ' . date('d/m/Y', strtotime($trx['tanggal_kembali'])) . '
            </td>
            <td>' . $harga . '</td>
            <td>' . (isset($interval) ? $interval : '-') . ' hari</td>
            <td>' . $total_harga . '</td>
        </tr>
        <tr class="total-row">
            <td colspan="4" style="text-align:right;">Total Bayar</td>
            <td>' . $total_harga . '</td>
        </tr>
    </table>
    <br>
    <div style="font-size:13px;color:#888;">
        Terima kasih telah menggunakan layanan FaVe Rental Mobil.<br>
        Harap simpan invoice ini sebagai bukti transaksi.
    </div>
</div>
';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('transaksi_'.$trx['id'].'.pdf', ['Attachment' => true]);
exit;