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
        $where = $search ? "WHERE (b.nama LIKE '%$search%' OR b.kode_barang LIKE '%$search%' OR b.barcode LIKE '%$search%')" : '';

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
        $sql = "SELECT * FROM jasa_servis";
        $params = [];
        if ($search) { $sql .= " WHERE nama LIKE :s OR kode_jasa LIKE :s"; $params['s'] = "%$search%"; }
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
        $sql = "SELECT * FROM supplier";
        $params = [];
        if ($search) { $sql .= " WHERE nama LIKE :s OR alamat LIKE :s"; $params['s'] = "%$search%"; }
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
        $stmt = $db->query("SELECT * FROM mekanik ORDER BY id DESC");
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
            $kode = generateCode('PJ', 'penjualan', 'kode_penjualan', 6);
            $stmt = $db->prepare("INSERT INTO penjualan (kode_penjualan, pelanggan_id, user_id, tanggal, subtotal, diskon, pajak, grand_total, metode_bayar, keterangan, status) VALUES (:kode, :pelanggan, :user, NOW(), :subtotal, :diskon, :pajak, :total, :bayar, :ket, :status)");
            $stmt->execute([
                'kode'=>$kode, 'pelanggan'=>$data['pelanggan_id']??null, 'user'=>getCurrentUser()['id'],
                'subtotal'=>$data['subtotal']??0, 'diskon'=>$data['diskon']??0, 'pajak'=>$data['pajak']??0,
                'total'=>$data['grand_total']??0, 'bayar'=>$data['metode_bayar']??'tunai',
                'ket'=>$data['keterangan']??'', 'status'=>$data['status']??'selesai'
            ]);
            $penjualanId = $db->lastInsertId();

            // Insert detail
            if (!empty($data['items']) && is_array($data['items'])) {
                $dtl = $db->prepare("INSERT INTO penjualan_detail (penjualan_id, barang_id, qty, harga_satuan, subtotal) VALUES (:penjualan, :barang, :qty, :harga, :sub)");
                foreach ($data['items'] as $item) {
                    $dtl->execute([
                        'penjualan'=>$penjualanId, 'barang'=>$item['barang_id'], 'qty'=>$item['qty'],
                        'harga'=>$item['harga_satuan'], 'sub'=>$item['subtotal']
                    ]);
                    // Kurangi stok
                    $db->prepare("UPDATE barang SET stok = stok - :qty WHERE id = :id")->execute(['qty'=>$item['qty'], 'id'=>$item['barang_id']]);
                }
            }

            $db->commit();
            jsonResponse(['status'=>'success','message'=>'Penjualan berhasil','data'=>['id'=>$penjualanId,'kode'=>$kode]]);
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
            $kode = generateCode('PB', 'pembelian', 'kode_pembelian', 6);
            $stmt = $db->prepare("INSERT INTO pembelian (kode_pembelian, supplier_id, user_id, tanggal, subtotal, diskon, pajak, grand_total, metode_bayar, keterangan, status) VALUES (:kode, :supplier, :user, NOW(), :subtotal, :diskon, :pajak, :total, :bayar, :ket, :status)");
            $stmt->execute([
                'kode'=>$kode, 'supplier'=>$data['supplier_id']??null, 'user'=>getCurrentUser()['id'],
                'subtotal'=>$data['subtotal']??0, 'diskon'=>$data['diskon']??0, 'pajak'=>$data['pajak']??0,
                'total'=>$data['grand_total']??0, 'bayar'=>$data['metode_bayar']??'tunai',
                'ket'=>$data['keterangan']??'', 'status'=>$data['status']??'diterima'
            ]);
            $pembelianId = $db->lastInsertId();

            if (!empty($data['items']) && is_array($data['items'])) {
                $dtl = $db->prepare("INSERT INTO pembelian_detail (pembelian_id, barang_id, qty, harga_satuan, subtotal) VALUES (:pembelian, :barang, :qty, :harga, :sub)");
                foreach ($data['items'] as $item) {
                    $dtl->execute([
                        'pembelian'=>$pembelianId, 'barang'=>$item['barang_id'], 'qty'=>$item['qty'],
                        'harga'=>$item['harga_satuan'], 'sub'=>$item['subtotal']
                    ]);
                    $db->prepare("UPDATE barang SET stok = stok + :qty WHERE id = :id")->execute(['qty'=>$item['qty'], 'id'=>$item['barang_id']]);
                }
            }

            // Buat hutang jika kredit
            if (($data['metode_bayar']??'') === 'kredit' && ($data['grand_total']??0) > 0) {
                $db->prepare("INSERT INTO hutang_supplier (pembelian_id, supplier_id, total_hutang, sisa_hutang, status) VALUES (:pb,:sp,:total,:sisa,'belum_lunas')")
                    ->execute(['pb'=>$pembelianId,'sp'=>$data['supplier_id']??null,'total'=>$data['grand_total'],'sisa'=>$data['grand_total']]);
            }

            $db->commit();
            jsonResponse(['status'=>'success','message'=>'Pembelian berhasil','data'=>['id'=>$pembelianId,'kode'=>$kode]]);
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
            $kode = generateCode('WO', 'work_order', 'kode_wo', 6);
            $stmt = $db->prepare("INSERT INTO work_order (kode_wo, pelanggan_id, mekanik_id, plat_nomor, tipe_motor, keluhan, subtotal, diskon, grand_total, status, estimasi_selesai) VALUES (:kode,:pelanggan,:mekanik,:plat,:tipe,:keluhan,:subtotal,:diskon,:total,:status,:estimasi)");
            $stmt->execute([
                'kode'=>$kode, 'pelanggan'=>$data['pelanggan_id']??null, 'mekanik'=>$data['mekanik_id']??null,
                'plat'=>$data['plat_nomor']??'', 'tipe'=>$data['tipe_motor']??'',
                'keluhan'=>$data['keluhan']??'', 'subtotal'=>$data['subtotal']??0, 'diskon'=>$data['diskon']??0,
                'total'=>$data['grand_total']??0, 'status'=>'proses', 'estimasi'=>$data['estimasi_selesai']??''
            ]);
            $woId = $db->lastInsertId();

            // Jasa servis
            if (!empty($data['jasa']) && is_array($data['jasa'])) {
                $js = $db->prepare("INSERT INTO work_order_jasa (work_order_id, jasa_servis_id, harga, subtotal) VALUES (:wo,:jasa,:harga,:sub)");
                foreach ($data['jasa'] as $j) {
                    $js->execute(['wo'=>$woId, 'jasa'=>$j['jasa_servis_id'], 'harga'=>$j['harga'], 'sub'=>$j['subtotal']??$j['harga']]);
                }
            }

            // Sparepart
            if (!empty($data['sparepart']) && is_array($data['sparepart'])) {
                $sp = $db->prepare("INSERT INTO work_order_sparepart (work_order_id, barang_id, qty, harga_satuan, subtotal) VALUES (:wo,:barang,:qty,:harga,:sub)");
                foreach ($data['sparepart'] as $s) {
                    $sp->execute(['wo'=>$woId, 'barang'=>$s['barang_id'], 'qty'=>$s['qty'], 'harga'=>$s['harga_satuan'], 'sub'=>$s['subtotal']]);
                    $db->prepare("UPDATE barang SET stok = stok - :qty WHERE id = :id")->execute(['qty'=>$s['qty'], 'id'=>$s['barang_id']]);
                }
            }

            $db->commit();
            jsonResponse(['status'=>'success','message'=>'Work order dibuat','data'=>['id'=>$woId,'kode'=>$kode]]);
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
        $stmt = $db->prepare("UPDATE hutang_supplier SET sisa_hutang = sisa_hutang - :bayar, status = IF(sisa_hutang - :bayar <= 0, 'lunas', 'belum_lunas') WHERE id = :id AND sisa_hutang >= :bayar");
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
        $stmt = $db->prepare("UPDATE piutang_pelanggan SET sisa_piutang = sisa_piutang - :bayar, status = IF(sisa_piutang - :bayar <= 0, 'lunas', 'belum_lunas') WHERE id = :id AND sisa_piutang >= :bayar");
        $stmt->execute(['id'=>$id, 'bayar'=>$bayar]);
        jsonResponse(['status'=>'success','message'=>'Pembayaran piutang tercatat']);
        break;

    // ═══════════════════════════════════════
    // USERS / SETTINGS (Fase 4)
    // ═══════════════════════════════════════
    case 'getUsers':
        requireRole(['owner','admin']);
        $db = getDB();
        $stmt = $db->query("SELECT id, username, nama_lengkap, role, status FROM users ORDER BY id");
        jsonResponse(['status'=>'success','data'=>$stmt->fetchAll()]);
        break;

    case 'getAppConfig':
        $db = getDB();
        $stmt = $db->query("SELECT * FROM app_config");
        $configs = [];
        foreach ($stmt->fetchAll() as $row) { $configs[$row['config_key']] = $row['config_value']; }
        jsonResponse(['status'=>'success','data'=>$configs]);
        break;

    default:
        jsonResponse(['status' => 'error', 'message' => 'Action tidak valid'], 400);
}
