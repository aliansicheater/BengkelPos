<?php
/**
 * Bengkel Pro V1 — Laporan PDF Generator
 * Uses DomPDF to generate PDF reports
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/auth.php';

if (!isLoggedIn()) {
    die('Akses ditolak. Silakan login terlebih dahulu.');
}

use Dompdf\Dompdf;
use Dompdf\Options;

$type   = $_GET['type'] ?? 'keuangan';
$start  = $_GET['start'] ?? date('Y-m-01');
$end    = $_GET['end'] ?? date('Y-m-d');

$db     = getDB();
$settings = getAppSettings();

// ── Helper ──
function rp($n) { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
function tgl($d) {
    if (!$d) return '-';
    return date('d/m/Y', strtotime($d));
}
function tglJam($d) {
    if (!$d) return '-';
    return date('d/m/Y H:i', strtotime($d));
}

// ── Header & Footer HTML ──
$htmlHeader = '
<div style="text-align:center;margin-bottom:20px;border-bottom:3px solid #0ea5e9;padding-bottom:15px;">
    <h1 style="margin:0;font-size:22px;color:#0f172a;font-family:sans-serif;">' . htmlspecialchars($settings['nama_bengkel'] ?? 'Bengkel Motor Pro') . '</h1>
    <p style="margin:2px 0;font-size:11px;color:#64748b;">' . htmlspecialchars($settings['alamat_bengkel'] ?? '') . '</p>
    <p style="margin:2px 0;font-size:11px;color:#64748b;">Telp: ' . htmlspecialchars($settings['no_telp'] ?? '') . '</p>
    <hr style="border:0;border-top:1px dashed #cbd5e1;margin:10px 0;">
    <h2 style="margin:0;font-size:16px;color:#0ea5e9;font-family:sans-serif;">LAPORAN ' . strtoupper($type) . '</h2>
    <p style="margin:2px 0;font-size:11px;color:#64748b;">Periode: ' . tgl($start) . ' s/d ' . tgl($end) . '</p>
</div>';

$htmlFooter = '
<div style="text-align:center;margin-top:30px;border-top:2px solid #e2e8f0;padding-top:10px;">
    <p style="font-size:10px;color:#94a3b8;">Dicetak: ' . tglJam(date('Y-m-d H:i:s')) . ' | ' . htmlspecialchars($settings['nama_bengkel'] ?? 'Bengkel Pro') . '</p>
</div>';

// ═══════════════════════════════════════════
// GENERATE HTML BY TYPE
// ═══════════════════════════════════════════
$htmlBody = '';

switch ($type) {

    // ── KEUANGAN ──
    case 'keuangan':
        // Total penjualan
        $stmt = $db->prepare("SELECT COALESCE(SUM(grand_total),0) AS total FROM penjualan WHERE DATE(tanggal) BETWEEN :s AND :e");
        $stmt->execute(['s' => $start, 'e' => $end]);
        $totalPenjualan = $stmt->fetch()['total'];

        // Total servis
        $stmt = $db->prepare("SELECT COALESCE(SUM(grand_total),0) AS total FROM work_orders WHERE DATE(tanggal) BETWEEN :s AND :e");
        $stmt->execute(['s' => $start, 'e' => $end]);
        $totalServis = $stmt->fetch()['total'];

        // Total pembelian
        $stmt = $db->prepare("SELECT COALESCE(SUM(grand_total),0) AS total FROM pembelian WHERE DATE(tanggal) BETWEEN :s AND :e");
        $stmt->execute(['s' => $start, 'e' => $end]);
        $totalPembelian = $stmt->fetch()['total'];

        $labaKotor = $totalPenjualan + $totalServis - $totalPembelian;

        $htmlBody = '
        <table width="100%" cellpadding="8" cellspacing="0" style="border:1px solid #e2e8f0;border-collapse:collapse;margin-bottom:20px;">
            <tr style="background:#f1f5f9;">
                <th style="text-align:left;width:60%;font-size:13px;padding:12px;border:1px solid #e2e8f0;">Keterangan</th>
                <th style="text-align:right;width:40%;font-size:13px;padding:12px;border:1px solid #e2e8f0;">Jumlah</th>
            </tr>
            <tr>
                <td style="padding:10px 12px;font-size:12px;border:1px solid #e2e8f0;">Total Penjualan</td>
                <td style="text-align:right;padding:10px 12px;font-size:12px;font-weight:bold;color:#22c55e;border:1px solid #e2e8f0;">' . rp($totalPenjualan) . '</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;font-size:12px;border:1px solid #e2e8f0;">Total Servis</td>
                <td style="text-align:right;padding:10px 12px;font-size:12px;font-weight:bold;color:#0ea5e9;border:1px solid #e2e8f0;">' . rp($totalServis) . '</td>
            </tr>
            <tr>
                <td style="padding:10px 12px;font-size:12px;border:1px solid #e2e8f0;">Total Pembelian</td>
                <td style="text-align:right;padding:10px 12px;font-size:12px;font-weight:bold;color:#ef4444;border:1px solid #e2e8f0;">' . rp($totalPembelian) . '</td>
            </tr>
            <tr style="background:#f0f9ff;">
                <td style="padding:10px 12px;font-size:13px;font-weight:bold;border:1px solid #e2e8f0;">LABA KOTOR</td>
                <td style="text-align:right;padding:10px 12px;font-size:13px;font-weight:bold;color:#f59e0b;border:1px solid #e2e8f0;">' . rp($labaKotor) . '</td>
            </tr>
        </table>';
        break;

    // ── PENJUALAN ──
    case 'penjualan':
        $stmt = $db->prepare("
            SELECT p.*, u.nama AS user_nama
            FROM penjualan p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE DATE(p.tanggal) BETWEEN :s AND :e
            ORDER BY p.tanggal DESC
        ");
        $stmt->execute(['s' => $start, 'e' => $end]);
        $rows = $stmt->fetchAll();

        $htmlBody = '
        <table width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #e2e8f0;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="text-align:center;padding:8px;border:1px solid #e2e8f0;width:5%;">No</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:18%;">No Transaksi</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:18%;">Tanggal</th>
                    <th style="text-align:right;padding:8px;border:1px solid #e2e8f0;width:22%;">Grand Total</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:20%;">Kasir</th>
                </tr>
            </thead>
            <tbody>';

        $totalAll = 0;
        if (empty($rows)) {
            $htmlBody .= '<tr><td colspan="5" style="text-align:center;padding:20px;border:1px solid #e2e8f0;color:#94a3b8;">Tidak ada data penjualan</td></tr>';
        } else {
            foreach ($rows as $i => $r) {
                $totalAll += $r['grand_total'];
                $bg = ($i % 2 === 0) ? '#ffffff' : '#f8fafc';
                $htmlBody .= '
                <tr style="background:' . $bg . ';">
                    <td style="text-align:center;padding:6px;border:1px solid #e2e8f0;">' . ($i + 1) . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;font-weight:bold;">' . htmlspecialchars($r['no_transaksi']) . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;">' . tgl($r['tanggal']) . '</td>
                    <td style="text-align:right;padding:6px;border:1px solid #e2e8f0;font-weight:bold;">' . rp($r['grand_total']) . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;">' . htmlspecialchars($r['user_nama'] ?? '-') . '</td>
                </tr>';
            }
            $htmlBody .= '
                <tr style="background:#f0f9ff;font-weight:bold;">
                    <td colspan="3" style="text-align:right;padding:8px;border:1px solid #e2e8f0;font-size:12px;">TOTAL</td>
                    <td style="text-align:right;padding:8px;border:1px solid #e2e8f0;color:#22c55e;font-size:12px;">' . rp($totalAll) . '</td>
                    <td style="padding:8px;border:1px solid #e2e8f0;"></td>
                </tr>';
        }

        $htmlBody .= '</tbody></table>';
        break;

    // ── PEMBELIAN ──
    case 'pembelian':
        $stmt = $db->prepare("
            SELECT pb.*, s.nama AS supplier_nama
            FROM pembelian pb
            LEFT JOIN supplier s ON pb.supplier_id = s.id
            WHERE DATE(pb.tanggal) BETWEEN :s AND :e
            ORDER BY pb.tanggal DESC
        ");
        $stmt->execute(['s' => $start, 'e' => $end]);
        $rows = $stmt->fetchAll();

        $htmlBody = '
        <table width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #e2e8f0;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="text-align:center;padding:8px;border:1px solid #e2e8f0;width:5%;">No</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:18%;">No Faktur</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:20%;">Supplier</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:18%;">Tanggal</th>
                    <th style="text-align:right;padding:8px;border:1px solid #e2e8f0;width:20%;">Grand Total</th>
                </tr>
            </thead>
            <tbody>';

        $totalAll = 0;
        if (empty($rows)) {
            $htmlBody .= '<tr><td colspan="5" style="text-align:center;padding:20px;border:1px solid #e2e8f0;color:#94a3b8;">Tidak ada data pembelian</td></tr>';
        } else {
            foreach ($rows as $i => $r) {
                $totalAll += $r['grand_total'];
                $bg = ($i % 2 === 0) ? '#ffffff' : '#f8fafc';
                $htmlBody .= '
                <tr style="background:' . $bg . ';">
                    <td style="text-align:center;padding:6px;border:1px solid #e2e8f0;">' . ($i + 1) . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;font-weight:bold;">' . htmlspecialchars($r['no_faktur']) . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;">' . htmlspecialchars($r['supplier_nama'] ?? '-') . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;">' . tgl($r['tanggal']) . '</td>
                    <td style="text-align:right;padding:6px;border:1px solid #e2e8f0;font-weight:bold;">' . rp($r['grand_total']) . '</td>
                </tr>';
            }
            $htmlBody .= '
                <tr style="background:#fef2f2;font-weight:bold;">
                    <td colspan="4" style="text-align:right;padding:8px;border:1px solid #e2e8f0;font-size:12px;">TOTAL</td>
                    <td style="text-align:right;padding:8px;border:1px solid #e2e8f0;color:#ef4444;font-size:12px;">' . rp($totalAll) . '</td>
                </tr>';
        }

        $htmlBody .= '</tbody></table>';
        break;

    // ── SERVIS ──
    case 'servis':
        $stmt = $db->prepare("
            SELECT wo.*, pl.nama AS pelanggan_nama, mk.nama AS mekanik_nama
            FROM work_orders wo
            LEFT JOIN pelanggan pl ON wo.pelanggan_id = pl.id
            LEFT JOIN mekanik mk ON wo.mekanik_id = mk.id
            WHERE DATE(wo.tanggal) BETWEEN :s AND :e
            ORDER BY wo.tanggal DESC
        ");
        $stmt->execute(['s' => $start, 'e' => $end]);
        $rows = $stmt->fetchAll();

        $htmlBody = '
        <table width="100%" cellpadding="6" cellspacing="0" style="border:1px solid #e2e8f0;border-collapse:collapse;font-size:11px;">
            <thead>
                <tr style="background:#f1f5f9;">
                    <th style="text-align:center;padding:8px;border:1px solid #e2e8f0;width:5%;">No</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:15%;">No WO</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:18%;">Pelanggan</th>
                    <th style="text-align:left;padding:8px;border:1px solid #e2e8f0;width:18%;">Mekanik</th>
                    <th style="text-align:center;padding:8px;border:1px solid #e2e8f0;width:14%;">Status</th>
                    <th style="text-align:right;padding:8px;border:1px solid #e2e8f0;width:18%;">Total</th>
                </tr>
            </thead>
            <tbody>';

        $totalAll = 0;
        if (empty($rows)) {
            $htmlBody .= '<tr><td colspan="6" style="text-align:center;padding:20px;border:1px solid #e2e8f0;color:#94a3b8;">Tidak ada data servis</td></tr>';
        } else {
            foreach ($rows as $i => $r) {
                $totalAll += $r['grand_total'];
                $bg = ($i % 2 === 0) ? '#ffffff' : '#f8fafc';
                $statusLabel = $r['status'] === 'diambil' ? 'Diambil' : ($r['status'] === 'selesai' ? 'Selesai' : 'Proses');
                $statusColor = $r['status'] === 'diambil' ? '#22c55e' : ($r['status'] === 'selesai' ? '#f59e0b' : '#0ea5e9');
                $htmlBody .= '
                <tr style="background:' . $bg . ';">
                    <td style="text-align:center;padding:6px;border:1px solid #e2e8f0;">' . ($i + 1) . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;font-weight:bold;">' . htmlspecialchars($r['no_wo']) . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;">' . htmlspecialchars($r['pelanggan_nama'] ?? '-') . '</td>
                    <td style="padding:6px;border:1px solid #e2e8f0;">' . htmlspecialchars($r['mekanik_nama'] ?? '-') . '</td>
                    <td style="text-align:center;padding:6px;border:1px solid #e2e8f0;color:' . $statusColor . ';font-weight:bold;">' . $statusLabel . '</td>
                    <td style="text-align:right;padding:6px;border:1px solid #e2e8f0;font-weight:bold;">' . rp($r['grand_total']) . '</td>
                </tr>';
            }
            $htmlBody .= '
                <tr style="background:#f0f9ff;font-weight:bold;">
                    <td colspan="5" style="text-align:right;padding:8px;border:1px solid #e2e8f0;font-size:12px;">TOTAL</td>
                    <td style="text-align:right;padding:8px;border:1px solid #e2e8f0;color:#0ea5e9;font-size:12px;">' . rp($totalAll) . '</td>
                </tr>';
        }

        $htmlBody .= '</tbody></table>';
        break;

    default:
        die('Tipe laporan tidak valid.');
}

// ── Full HTML Document ──
$fullHtml = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif;
            font-size: 12px;
            color: #1e293b;
            line-height: 1.5;
            margin: 15px;
        }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; }
        @page {
            margin: 20mm 15mm 25mm 15mm;
            footer: html Footer;
        }
    </style>
</head>
<body>
    ' . $htmlHeader . '
    ' . $htmlBody . '
    ' . $htmlFooter . '
</body>
</html>';

// ── Generate PDF ──
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('isPhpEnabled', false);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($fullHtml);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Output
$filename = 'Laporan_' . ucfirst($type) . '_' . $start . '_sd_' . $end . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
