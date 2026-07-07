<?php
/**
 * Bengkel Pro V1 — API Router
 * Semua request melalui endpoint ini dengan parameter ?action=...
 */
require_once __DIR__ . '/../config/auth.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// Parse JSON body for POST requests
$data = [];
if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true) ?: $_POST;
}

// Route
switch ($action) {

    // ═══════════════════════════════════════
    // DASHBOARD
    // ═══════════════════════════════════════
    case 'getDashboard':
        requireLogin();
        $db = getDB();
        $today = date('Y-m-d');
        $month = date('Y-m');

        $result = [];

        // Omzet hari ini
        $stmt = $db->query("SELECT COALESCE(SUM(grand_total),0) AS total FROM penjualan WHERE tanggal = '$today'");
        $result['omzet_hari'] = $stmt->fetch()['total'];

        // Total servis hari ini
        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM work_order WHERE DATE(created_at) = '$today'");
        $result['servis_hari'] = $stmt->fetch()['cnt'];

        // Omzet bulanan
        $stmt = $db->query("SELECT COALESCE(SUM(grand_total),0) AS total FROM penjualan WHERE DATE_FORMAT(tanggal,'%Y-%m') = '$month'");
        $result['omzet_bulan'] = $stmt->fetch()['total'];

        // Servis proses
        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM work_order WHERE status = 'proses'");
        $result['servis_proses'] = $stmt->fetch()['cnt'];

        // Stok menipis
        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM barang WHERE stok <= stok_minimum AND status = 'aktif'");
        $result['stok_menipis'] = $stmt->fetch()['cnt'];

        // Piutang
        $stmt = $db->query("SELECT COALESCE(SUM(sisa_piutang),0) AS total FROM piutang_pelanggan WHERE status != 'lunas'");
        $result['piutang'] = $stmt->fetch()['total'];

        // Hutang
        $stmt = $db->query("SELECT COALESCE(SUM(sisa_hutang),0) AS total FROM hutang_supplier WHERE status != 'lunas'");
        $result['hutang'] = $stmt->fetch()['total'];

        // Grafik penjualan 7 hari
        $stmt = $db->query("SELECT tanggal, COALESCE(SUM(grand_total),0) AS total FROM penjualan WHERE tanggal >= DATE_SUB('$today', INTERVAL 7 DAY) GROUP BY tanggal ORDER BY tanggal");
        $result['grafik_penjualan'] = $stmt->fetchAll();

        jsonResponse(['status' => 'success', 'data' => $result]);
        break;

    // ═══════════════════════════════════════
    // BARANG
    // ═══════════════════════════════════════
    case 'getBarang':
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 10;
        $search = $_GET['search'] ?? '';
        $offset = ($page - 1) * $limit;

        $db = getDB();
        $where = $search ? "WHERE (b.nama LIKE '%$search%' OR b.kode_barang LIKE '%$search%' OR b.barcode LIKE '%$search%') AND b.status = 'aktif'" : "WHERE b.status = 'aktif'";

        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM barang b $where");
        $total = $stmt->fetch()['cnt'];

        $stmt = $db->query("SELECT b.*, k.nama AS kategori_nama FROM barang b LEFT JOIN kategori_barang k ON b.kategori_id = k.id $where ORDER BY b.id DESC LIMIT $limit OFFSET $offset");

        jsonResponse([
            'status' => 'success',
            'data' => $stmt->fetchAll(),
            'pagination' => ['page' => (int)$page, 'limit' => (int)$limit, 'total' => (int)$total, 'pages' => ceil($total/$limit)]
        ]);
        break;

    case 'getBarangSingle':
        $db = getDB();
        $id = $_GET['id'] ?? ($data['id'] ?? 0);
        $stmt = $db->prepare("SELECT b.*, k.nama AS kategori_nama FROM barang b LEFT JOIN kategori_barang k ON b.kategori_id = k.id WHERE b.id = :id");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        jsonResponse(['status' => 'success', 'data' => $row ?: null]);
        break;

    case 'addBarang':
        $db = getDB();
        $kode = generateCode('BRG', 'barang', 'kode_barang', 5);
        $stmt = $db->prepare("INSERT INTO barang (kode_barang, barcode, nama, kategori_id, merk, satuan, harga_modal, harga_jual, stok, stok_minimum, lokasi_rak) VALUES (:kode, :barcode, :nama, :kategori_id, :merk, :satuan, :harga_modal, :harga_jual, :stok, :stok_minimum, :lokasi_rak)");
        $stmt->execute([
            'kode' => $kode, 'barcode' => $data['barcode'] ?? '', 'nama' => $data['nama'] ?? '',
            'kategori_id' => $data['kategori_id'] ?: null, 'merk' => $data['merk'] ?? '', 'satuan' => $data['satuan'] ?? 'Pcs',
            'harga_modal' => $data['harga_modal'] ?? 0, 'harga_jual' => $data['harga_jual'] ?? 0,
            'stok' => $data['stok'] ?? 0, 'stok_minimum' => $data['stok_minimum'] ?? 5, 'lokasi_rak' => $data['lokasi_rak'] ?? '',
        ]);
        jsonResponse(['status' => 'success', 'message' => 'Barang berhasil ditambahkan']);
        break;

    case 'updateBarang':
        $db = getDB();
        $stmt = $db->prepare("UPDATE barang SET barcode=:barcode, nama=:nama, kategori_id=:kategori_id, merk=:merk, satuan=:satuan, harga_modal=:harga_modal, harga_jual=:harga_jual, stok_minimum=:stok_minimum, lokasi_rak=:lokasi_rak WHERE id=:id");
        $stmt->execute([
            'id' => $data['id'], 'barcode' => $data['barcode'] ?? '', 'nama' => $data['nama'] ?? '',
            'kategori_id' => $data['kategori_id'] ?: null, 'merk' => $data['merk'] ?? '', 'satuan' => $data['satuan'] ?? 'Pcs',
            'harga_modal' => $data['harga_modal'] ?? 0, 'harga_jual' => $data['harga_jual'] ?? 0,
            'stok_minimum' => $data['stok_minimum'] ?? 5, 'lokasi_rak' => $data['lokasi_rak'] ?? '',
        ]);
        jsonResponse(['status' => 'success', 'message' => 'Barang berhasil diupdate']);
        break;

    case 'deleteBarang':
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM barang WHERE id = :id");
        $stmt->execute(['id' => $data['id']]);
        jsonResponse(['status' => 'success', 'message' => 'Barang berhasil dihapus']);
        break;

    // ═══════════════════════════════════════
    // JASA SERVIS
    // ═══════════════════════════════════════
    case 'getJasaServis':
        $db = getDB();
        $search = $_GET['search'] ?? ($data['search'] ?? '');
        $sql = "SELECT * FROM jasa_servis WHERE status = 'aktif'";
        $params = [];
        if ($search) { $sql .= " AND (nama LIKE :s OR kode_jasa LIKE :s)"; $params['s'] = "%$search%"; }
        $sql .= " ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['status' => 'success', 'data' => $stmt->fetchAll()]);
        break;

    case 'addJasaServis':
        $db = getDB();
        $kode = generateCode('JS', 'jasa_servis', 'kode_jasa', 3);
        $stmt = $db->prepare("INSERT INTO jasa_servis (kode_jasa, nama, deskripsi, harga, persentase_mekanik, estimasi_menit) VALUES (:kode, :nama, :deskripsi, :harga, :persentase, :estimasi)");
        $stmt->execute([
            'kode'=>$kode, 'nama'=>$data['nama']??'', 'deskripsi'=>$data['deskripsi']??'',
            'harga'=>$data['harga']??0, 'persentase'=>$data['persentase_mekanik']??50, 'estimasi'=>$data['estimasi_menit']??30
        ]);
        jsonResponse(['status'=>'success','message'=>'Jasa servis berhasil ditambahkan']);
        break;

    case 'updateJasaServis':
        $db = getDB();
        $stmt = $db->prepare("UPDATE jasa_servis SET nama=:nama, deskripsi=:deskripsi, harga=:harga, persentase_mekanik=:persentase, estimasi_menit=:estimasi WHERE id=:id");
        $stmt->execute([
            'id'=>$data['id'], 'nama'=>$data['nama']??'', 'deskripsi'=>$data['deskripsi']??'',
            'harga'=>$data['harga']??0, 'persentase'=>$data['persentase_mekanik']??50, 'estimasi'=>$data['estimasi_menit']??30
        ]);
        jsonResponse(['status'=>'success','message'=>'Jasa servis berhasil diupdate']);
        break;

    case 'deleteJasaServis':
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM jasa_servis WHERE id = :id");
        $stmt->execute(['id' => $data['id']]);
        jsonResponse(['status'=>'success','message'=>'Jasa servis berhasil dihapus']);
        break;

    // ═══════════════════════════════════════
    // SUPPLIER
    // ═══════════════════════════════════════
    case 'getSupplier':
        $db = getDB();
        $search = $_GET['search'] ?? ($data['search'] ?? '');
        $sql = "SELECT * FROM supplier WHERE status = 'aktif'";
        $params = [];
        if ($search) { $sql .= " AND (nama LIKE :s OR alamat LIKE :s)"; $params['s'] = "%$search%"; }
        $sql .= " ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    case 'addSupplier':
        $db = getDB();
        $kode = generateCode('SUP', 'supplier', 'kode_supplier', 3);
        $stmt = $db->prepare("INSERT INTO supplier (kode_supplier, nama, alamat, no_hp, email, catatan) VALUES (:kode,:nama,:alamat,:no_hp,:email,:catatan)");
        $stmt->execute([
            'kode'=>$kode,'nama'=>$data['nama']??'','alamat'=>$data['alamat']??'',
            'no_hp'=>$data['no_hp']??'','email'=>$data['email']??'','catatan'=>$data['catatan']??''
        ]);
        jsonResponse(['status'=>'success','message'=>'Supplier berhasil ditambahkan']);
        break;

    case 'updateSupplier':
        $db = getDB();
        $stmt = $db->prepare("UPDATE supplier SET nama=:nama, alamat=:alamat, no_hp=:no_hp, email=:email, catatan=:catatan WHERE id=:id");
        $stmt->execute([
            'id'=>$data['id'],'nama'=>$data['nama']??'','alamat'=>$data['alamat']??'',
            'no_hp'=>$data['no_hp']??'','email'=>$data['email']??'','catatan'=>$data['catatan']??''
        ]);
        jsonResponse(['status'=>'success','message'=>'Supplier berhasil diupdate']);
        break;

    case 'deleteSupplier':
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM supplier WHERE id = :id");
        $stmt->execute(['id'=>$data['id']]);
        jsonResponse(['status'=>'success','message'=>'Supplier berhasil dihapus']);
        break;

    // ═══════════════════════════════════════
    // PELANGGAN
    // ═══════════════════════════════════════
    case 'getPelanggan':
        $db = getDB();
        $search = $_GET['search'] ?? ($data['search'] ?? '');
        $sql = "SELECT * FROM pelanggan";
        $params = [];
        if ($search) { $sql .= " WHERE nama LIKE :s OR no_hp LIKE :s OR plat_nomor LIKE :s"; $params['s'] = "%$search%"; }
        $sql .= " ORDER BY id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    case 'addPelanggan':
        $db = getDB();
        $kode = generateCode('PLG', 'pelanggan', 'kode_pelanggan', 4);
        $stmt = $db->prepare("INSERT INTO pelanggan (kode_pelanggan, nama, no_hp, alamat, plat_nomor, tipe_motor, tahun_motor) VALUES (:kode,:nama,:no_hp,:alamat,:plat,:tipe,:tahun)");
        $stmt->execute([
            'kode'=>$kode,'nama'=>$data['nama']??'','no_hp'=>$data['no_hp']??'',
            'alamat'=>$data['alamat']??'','plat'=>$data['plat_nomor']??'',
            'tipe'=>$data['tipe_motor']??'','tahun'=>$data['tahun_motor']??''
        ]);
        jsonResponse(['status'=>'success','message'=>'Pelanggan berhasil ditambahkan']);
        break;

    case 'updatePelanggan':
        $db = getDB();
        $stmt = $db->prepare("UPDATE pelanggan SET nama=:nama, no_hp=:no_hp, alamat=:alamat, plat_nomor=:plat, tipe_motor=:tipe, tahun_motor=:tahun WHERE id=:id");
        $stmt->execute([
            'id'=>$data['id'],'nama'=>$data['nama']??'','no_hp'=>$data['no_hp']??'',
            'alamat'=>$data['alamat']??'','plat'=>$data['plat_nomor']??'',
            'tipe'=>$data['tipe_motor']??'','tahun'=>$data['tahun_motor']??''
        ]);
        jsonResponse(['status'=>'success','message'=>'Pelanggan berhasil diupdate']);
        break;

    case 'deletePelanggan':
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM pelanggan WHERE id = :id");
        $stmt->execute(['id'=>$data['id']]);
        jsonResponse(['status'=>'success','message'=>'Pelanggan berhasil dihapus']);
        break;

    // ═══════════════════════════════════════
    // MEKANIK
    // ═══════════════════════════════════════
    case 'getMekanik':
        $db = getDB();
        $stmt = $db->query("SELECT * FROM mekanik WHERE status = 'aktif' ORDER BY id DESC");
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    case 'addMekanik':
        $db = getDB();
        $kode = generateCode('MKK', 'mekanik', 'kode_mekanik', 3);
        $stmt = $db->prepare("INSERT INTO mekanik (kode_mekanik, nama, jabatan, persentase_jasa, no_hp) VALUES (:kode,:nama,:jabatan,:persentase,:no_hp)");
        $stmt->execute([
            'kode'=>$kode,'nama'=>$data['nama']??'','jabatan'=>$data['jabatan']??'Mekanik',
            'persentase'=>$data['persentase_jasa']??50,'no_hp'=>$data['no_hp']??''
        ]);
        jsonResponse(['status'=>'success','message'=>'Mekanik berhasil ditambahkan']);
        break;

    case 'updateMekanik':
        $db = getDB();
        $stmt = $db->prepare("UPDATE mekanik SET nama=:nama, jabatan=:jabatan, persentase_jasa=:persentase, no_hp=:no_hp WHERE id=:id");
        $stmt->execute([
            'id'=>$data['id'],'nama'=>$data['nama']??'','jabatan'=>$data['jabatan']??'',
            'persentase'=>$data['persentase_jasa']??50,'no_hp'=>$data['no_hp']??''
        ]);
        jsonResponse(['status'=>'success','message'=>'Mekanik berhasil diupdate']);
        break;

    case 'deleteMekanik':
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM mekanik WHERE id = :id");
        $stmt->execute(['id'=>$data['id']]);
        jsonResponse(['status'=>'success','message'=>'Mekanik berhasil dihapus']);
        break;

    // ═══════════════════════════════════════
    // KATEGORI BARANG (Helper)
    // ═══════════════════════════════════════
    case 'getKategoriBarang':
        $db = getDB();
        $stmt = $db->query("SELECT * FROM kategori_barang ORDER BY nama");
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    // ═══════════════════════════════════════
    // PENJUALAN (Fase 2)
    // ═══════════════════════════════════════
    case 'addPenjualan':
        requireRole(['owner','admin','kasir']);
        $db = getDB();
        $db->beginTransaction();
        try {
            $noTrx = generateCode('PJ', 'penjualan', 'no_transaksi', 6);
            $subtotal = $data['subtotal'] ?? 0;
            $diskon = $data['diskon'] ?? 0;
            $pajak = $data['pajak'] ?? 0;
            $grandTotal = $subtotal - $diskon + $pajak;
            $bayar = $data['bayar'] ?? 0;
            $kembali = max(0, $bayar - $grandTotal);
            $metodeBayar = $data['metode_bayar'] ?? 'tunai';
            
            $stmt = $db->prepare("INSERT INTO penjualan (no_transaksi, tanggal, subtotal, diskon, pajak, grand_total, bayar, kembali, metode_bayar, user_id) VALUES (:no, CURDATE(), :sub, :disk, :pajak, :gt, :bayar, :kembali, :metode, :uid)");
            $stmt->execute([
                'no'=>$noTrx, 'sub'=>$subtotal, 'disk'=>$diskon, 'pajak'=>$pajak,
                'gt'=>$grandTotal, 'bayar'=>$bayar, 'kembali'=>$kembali,
                'metode'=>$metodeBayar, 'uid'=>getCurrentUser()['id']
            ]);
            $penjualanId = $db->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $dtl = $db->prepare("INSERT INTO penjualan_detail (penjualan_id, barang_id, qty, harga, diskon, subtotal) VALUES (:pj, :brg, :qty, :hrg, :disk, :sub)");
                foreach ($data['items'] as $item) {
                    $dtl->execute([
                        'pj'=>$penjualanId, 'brg'=>$item['barang_id'], 'qty'=>$item['qty'],
                        'hrg'=>$item['harga'], 'disk'=>$item['diskon']??0, 'sub'=>$item['subtotal']
                    ]);
                    $db->prepare("UPDATE barang SET stok = stok - :qty WHERE id = :id")->execute(['qty'=>$item['qty'], 'id'=>$item['barang_id']]);
                }
            }

            $db->commit();
            jsonResponse(['status'=>'success','message'=>'Penjualan berhasil','data'=>['id'=>$penjualanId,'no_transaksi'=>$noTrx,'grand_total'=>$grandTotal,'bayar'=>$bayar,'kembali'=>$kembali,'metode_bayar'=>$metodeBayar]]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['status'=>'error','message'=>'Gagal: '.$e->getMessage()], 500);
        }
        break;

    case 'getPenjualan':
        requireRole(['owner','admin','kasir']);
        $db = getDB();
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;

        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM penjualan");
        $total = $stmt->fetch()['cnt'];

        $stmt = $db->query("SELECT p.*, plg.nama AS pelanggan_nama FROM penjualan p LEFT JOIN pelanggan plg ON p.pelanggan_id = plg.id ORDER BY p.id DESC LIMIT $limit OFFSET $offset");
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll(),'pagination'=>['page'=>(int)$page,'limit'=>(int)$limit,'total'=>(int)$total,'pages'=>ceil($total/$limit)]]);
        break;

    case 'getPenjualanDetail':
        $db = getDB();
        $id = $_GET['id'] ?? ($data['id'] ?? 0);
        $stmt = $db->prepare("SELECT pd.*, b.nama AS barang_nama, b.kode_barang FROM penjualan_detail pd LEFT JOIN barang b ON pd.barang_id = b.id WHERE pd.penjualan_id = :id");
        $stmt->execute(['id'=>$id]);
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    // ═══════════════════════════════════════
    // PEMBELIAN / STOK MASUK (Fase 2)
    // ═══════════════════════════════════════
    case 'addPembelian':
        requireRole(['owner','admin','gudang']);
        $db = getDB();
        $db->beginTransaction();
        try {
            $noFaktur = generateCode('PB', 'pembelian', 'no_faktur', 6);
            $subtotal = $data['subtotal'] ?? 0;
            $diskon = $data['diskon'] ?? 0;
            $pajak = $data['pajak'] ?? 0;
            $grandTotal = $subtotal - $diskon + $pajak;
            $metodeBayar = $data['metode_bayar'] ?? 'tunai';
            $statusBayar = ($metodeBayar === 'kredit') ? 'belum' : 'lunas';
            
            $stmt = $db->prepare("INSERT INTO pembelian (no_faktur, supplier_id, tanggal, total, diskon, pajak, grand_total, status_bayar, jatuh_tempo, keterangan, user_id) VALUES (:no, :sup, CURDATE(), :tot, :disk, :pajak, :gt, :sb, :jt, :ket, :uid)");
            $stmt->execute([
                'no'=>$noFaktur, 'sup'=>$data['supplier_id']??null, 'tot'=>$subtotal,
                'disk'=>$diskon, 'pajak'=>$pajak, 'gt'=>$grandTotal, 'sb'=>$statusBayar,
                'jt'=>$data['jatuh_tempo']??null, 'ket'=>$data['keterangan']??'',
                'uid'=>getCurrentUser()['id']
            ]);
            $pembelianId = $db->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $dtl = $db->prepare("INSERT INTO pembelian_detail (pembelian_id, barang_id, qty, harga, diskon, subtotal) VALUES (:pb, :brg, :qty, :hrg, :disk, :sub)");
                foreach ($data['items'] as $item) {
                    $dtl->execute([
                        'pb'=>$pembelianId, 'brg'=>$item['barang_id'], 'qty'=>$item['qty'],
                        'hrg'=>$item['harga'], 'disk'=>$item['diskon']??0, 'sub'=>$item['subtotal']
                    ]);
                    $db->prepare("UPDATE barang SET stok = stok + :qty WHERE id = :id")->execute(['qty'=>$item['qty'], 'id'=>$item['barang_id']]);
                }
            }

            if ($metodeBayar === 'kredit' && $grandTotal > 0) {
                $db->prepare("INSERT INTO hutang_supplier (supplier_id, no_faktur, jumlah, terbayar, sisa_hutang, jatuh_tempo, status) VALUES (:sp, :nf, :jml, 0, :sisa, :jt, 'belum')")
                    ->execute(['sp'=>$data['supplier_id']??null, 'nf'=>$noFaktur, 'jml'=>$grandTotal, 'sisa'=>$grandTotal, 'jt'=>$data['jatuh_tempo']??null]);
            }

            $db->commit();
            jsonResponse(['status'=>'success','message'=>'Pembelian berhasil','data'=>['id'=>$pembelianId,'no_faktur'=>$noFaktur,'grand_total'=>$grandTotal]]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['status'=>'error','message'=>'Gagal: '.$e->getMessage()], 500);
        }
        break;

    case 'getPembelian':
        requireRole(['owner','admin','gudang']);
        $db = getDB();
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;

        $stmt = $db->query("SELECT COUNT(*) AS cnt FROM pembelian");
        $total = $stmt->fetch()['cnt'];

        $stmt = $db->query("SELECT pb.*, sp.nama AS supplier_nama FROM pembelian pb LEFT JOIN supplier sp ON pb.supplier_id = sp.id ORDER BY pb.id DESC LIMIT $limit OFFSET $offset");
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll(),'pagination'=>['page'=>(int)$page,'limit'=>(int)$limit,'total'=>(int)$total,'pages'=>ceil($total/$limit)]]);
        break;

    // ═══════════════════════════════════════
    // WORK ORDER / SERVIS (Fase 2)
    // ═══════════════════════════════════════
    case 'addWorkOrder':
        requireRole(['owner','admin','kasir','mekanik']);
        $db = getDB();
        $db->beginTransaction();
        try {
            $noWo = generateCode('WO', 'work_order', 'no_wo', 6);
            $totalJasa = $data['total_jasa'] ?? 0;
            $totalSparepart = $data['total_sparepart'] ?? 0;
            $grandTotal = $totalJasa + $totalSparepart;
            
            $stmt = $db->prepare("INSERT INTO work_order (no_wo, pelanggan_id, plat_nomor, tipe_motor, km_sekarang, keluhan, diagnosa, mekanik_id, status, total_jasa, total_sparepart, grand_total, status_bayar) VALUES (:no, :plg, :plat, :tipe, :km, :kel, :diag, :mek, 'proses', :tj, :ts, :gt, 'belum')");
            $stmt->execute([
                'no'=>$noWo, 'plg'=>$data['pelanggan_id']??null, 'plat'=>$data['plat_nomor']??'',
                'tipe'=>$data['tipe_motor']??'', 'km'=>$data['km_sekarang']??0,
                'kel'=>$data['keluhan']??'', 'diag'=>$data['diagnosa']??'',
                'mek'=>$data['mekanik_id']??null, 'tj'=>$totalJasa, 'ts'=>$totalSparepart, 'gt'=>$grandTotal
            ]);
            $woId = $db->lastInsertId();

            if (!empty($data['jasa']) && is_array($data['jasa'])) {
                $js = $db->prepare("INSERT INTO work_order_jasa (wo_id, jasa_id, harga, subtotal) VALUES (:wo, :jasa, :hrg, :sub)");
                foreach ($data['jasa'] as $j) {
                    $js->execute(['wo'=>$woId, 'jasa'=>$j['jasa_id'], 'hrg'=>$j['harga'], 'sub'=>$j['subtotal']??$j['harga']]);
                }
            }

            if (!empty($data['sparepart']) && is_array($data['sparepart'])) {
                $sp = $db->prepare("INSERT INTO work_order_sparepart (wo_id, barang_id, qty, harga, subtotal) VALUES (:wo, :brg, :qty, :hrg, :sub)");
                foreach ($data['sparepart'] as $s) {
                    $sp->execute(['wo'=>$woId, 'brg'=>$s['barang_id'], 'qty'=>$s['qty'], 'hrg'=>$s['harga'], 'sub'=>$s['subtotal']]);
                    $db->prepare("UPDATE barang SET stok = stok - :qty WHERE id = :id")->execute(['qty'=>$s['qty'], 'id'=>$s['barang_id']]);
                }
            }

            $db->commit();
            jsonResponse(['status'=>'success','message'=>'Work order dibuat','data'=>['id'=>$woId,'no_wo'=>$noWo,'grand_total'=>$grandTotal]]);
        } catch (Exception $e) {
            $db->rollBack();
            jsonResponse(['status'=>'error','message'=>'Gagal: '.$e->getMessage()], 500);
        }
        break;

    case 'getWorkOrder':
        $db = getDB();
        $page = $_GET['page'] ?? 1;
        $limit = $_GET['limit'] ?? 20;
        $offset = ($page - 1) * $limit;
        $status = $_GET['status'] ?? '';

        $where = $status ? "WHERE wo.status = :status" : "";
        $params = $status ? ['status'=>$status] : [];

        $cnt = $db->prepare("SELECT COUNT(*) AS cnt FROM work_order wo $where");
        $cnt->execute($params);
        $total = $cnt->fetch()['cnt'];

        $sql = "SELECT wo.*, plg.nama AS pelanggan_nama, mk.nama AS mekanik_nama FROM work_order wo LEFT JOIN pelanggan plg ON wo.pelanggan_id = plg.id LEFT JOIN mekanik mk ON wo.mekanik_id = mk.id $where ORDER BY wo.id DESC LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll(),'pagination'=>['page'=>(int)$page,'limit'=>(int)$limit,'total'=>(int)$total,'pages'=>ceil($total/$limit)]]);
        break;

    case 'updateWorkOrderStatus':
        $db = getDB();
        $stmt = $db->prepare("UPDATE work_order SET status = :status WHERE id = :id");
        $stmt->execute(['id'=>$data['id'], 'status'=>$data['status']]);
        jsonResponse(['status'=>'success','message'=>'Status work order diupdate']);
        break;

    case 'updateWorkOrderBayar':
        $db = getDB();
        $stmt = $db->prepare("UPDATE work_order SET status_bayar = :status WHERE id = :id");
        $stmt->execute(['id'=>$data['id'], 'status'=>$data['status'] ?? 'lunas']);
        jsonResponse(['status'=>'success','message'=>'Status pembayaran diupdate']);
        break;

    // ═══════════════════════════════════════
    // RIWAYAT SERVIS
    // ═══════════════════════════════════════
    case 'getRiwayatServis':
        $db = getDB();
        $pelanggan_id = $_GET['pelanggan_id'] ?? 0;
        if ($pelanggan_id) {
            $stmt = $db->prepare("SELECT wo.*, mk.nama AS mekanik_nama FROM work_order wo LEFT JOIN mekanik mk ON wo.mekanik_id = mk.id WHERE wo.pelanggan_id = :id ORDER BY wo.created_at DESC");
            $stmt->execute(['id'=>$pelanggan_id]);
        } else {
            $stmt = $db->query("SELECT wo.*, plg.nama AS pelanggan_nama, mk.nama AS mekanik_nama FROM work_order wo LEFT JOIN pelanggan plg ON wo.pelanggan_id = plg.id LEFT JOIN mekanik mk ON wo.mekanik_id = mk.id ORDER BY wo.created_at DESC LIMIT 100");
        }
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    // ═══════════════════════════════════════
    // KEUANGAN (Fase 3)
    // ═══════════════════════════════════════
    case 'getHutang':
        $db = getDB();
        $stmt = $db->query("SELECT hs.*, sp.nama AS supplier_nama FROM hutang_supplier hs LEFT JOIN supplier sp ON hs.supplier_id = sp.id ORDER BY hs.id DESC");
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    case 'bayarHutang':
        $db = getDB();
        $id = $data['id'];
        $bayar = $data['jumlah'] ?? 0;
        $stmt = $db->prepare("UPDATE hutang_supplier SET terbayar = terbayar + :bayar, sisa_hutang = sisa_hutang - :bayar, status = IF(sisa_hutang - :bayar <= 0, 'lunas', IF(terbayar + :bayar > 0, 'cicilan', 'belum')) WHERE id = :id AND sisa_hutang >= :bayar");
        $stmt->execute(['id'=>$id, 'bayar'=>$bayar]);
        jsonResponse(['status'=>'success','message'=>'Pembayaran hutang tercatat']);
        break;

    case 'getPiutang':
        $db = getDB();
        $stmt = $db->query("SELECT pp.*, plg.nama AS pelanggan_nama FROM piutang_pelanggan pp LEFT JOIN pelanggan plg ON pp.pelanggan_id = plg.id ORDER BY pp.id DESC");
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    case 'bayarPiutang':
        $db = getDB();
        $id = $data['id'];
        $bayar = $data['jumlah'] ?? 0;
        $stmt = $db->prepare("UPDATE piutang_pelanggan SET terbayar = terbayar + :bayar, sisa_piutang = sisa_piutang - :bayar, status = IF(sisa_piutang - :bayar <= 0, 'lunas', IF(terbayar + :bayar > 0, 'cicilan', 'belum')) WHERE id = :id AND sisa_piutang >= :bayar");
        $stmt->execute(['id'=>$id, 'bayar'=>$bayar]);
        jsonResponse(['status'=>'success','message'=>'Pembayaran piutang tercatat']);
        break;

    // ═══════════════════════════════════════
    // USERS / SETTINGS (Fase 4)
    // ═══════════════════════════════════════
    case 'getUsers':
        requireRole(['owner','admin']);
        $db = getDB();
        $stmt = $db->query("SELECT id, username, nama, role, status FROM users ORDER BY id");
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    case 'getAppConfig':
        $db = getDB();
        $stmt = $db->query("SELECT * FROM app_config");
        $configs = [];
        foreach ($stmt->fetchAll() as $row) { $configs[$row['config_key']] = $row['config_value']; }
        jsonResponse(['status'=>'success','data'=>$configs]);
        break;

    // ═══════════════════════════════════════
    // SEARCH BARANG (flat result, for POS/Pembelian)
    // ═══════════════════════════════════════
    case 'searchBarang':
        $db = getDB();
        $search = $_GET['search'] ?? '';
        $sql = "SELECT b.*, k.nama AS kategori_nama FROM barang b LEFT JOIN kategori_barang k ON b.kategori_id = k.id WHERE b.status = 'aktif'";
        $params = [];
        if ($search) {
            $sql .= " AND (b.nama LIKE :s OR b.kode_barang LIKE :s OR b.barcode LIKE :s)";
            $params['s'] = "%$search%";
        }
        $sql .= " ORDER BY b.nama ASC LIMIT 30";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    // ═══════════════════════════════════════
    // WORK ORDER DETAIL (with jasa + sparepart)
    // ═══════════════════════════════════════
    case 'getWorkOrderDetail':
        $db = getDB();
        $id = $_GET['id'] ?? ($data['id'] ?? 0);
        
        $stmt = $db->prepare("SELECT wo.*, plg.nama AS pelanggan_nama, plg.no_hp AS pelanggan_hp, mk.nama AS mekanik_nama FROM work_order wo LEFT JOIN pelanggan plg ON wo.pelanggan_id = plg.id LEFT JOIN mekanik mk ON wo.mekanik_id = mk.id WHERE wo.id = :id");
        $stmt->execute(['id'=>$id]);
        $wo = $stmt->fetch();
        if (!$wo) { jsonResponse(['status'=>'error','message'=>'Work order tidak ditemukan'], 404); break; }
        
        // Get jasa list
        $stmt = $db->prepare("SELECT wj.*, js.nama AS jasa_nama FROM work_order_jasa wj LEFT JOIN jasa_servis js ON wj.jasa_id = js.id WHERE wj.wo_id = :id");
        $stmt->execute(['id'=>$id]);
        $wo['jasa_list'] = $stmt->fetchAll();
        
        // Get sparepart list
        $stmt = $db->prepare("SELECT ws.*, b.nama AS barang_nama, b.kode_barang FROM work_order_sparepart ws LEFT JOIN barang b ON ws.barang_id = b.id WHERE ws.wo_id = :id");
        $stmt->execute(['id'=>$id]);
        $wo['sparepart_list'] = $stmt->fetchAll();
        
        jsonResponse(['status'=>'success','data'=>$wo]);
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Action tidak valid'], 400);
}
