-- ============================================================
-- Bengkel Pro V1 — Database Schema
-- db_bengkelpro
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_bengkelpro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_bengkelpro;

-- ============================================================
-- MASTER TABLES
-- ============================================================

-- Users / Manajemen User
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama VARCHAR(100) NOT NULL,
    role ENUM('owner','admin','kasir','mekanik','gudang') NOT NULL DEFAULT 'admin',
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Kategori Barang
CREATE TABLE IF NOT EXISTS kategori_barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Barang / Sparepart
CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(30) NOT NULL UNIQUE,
    barcode VARCHAR(50),
    nama VARCHAR(200) NOT NULL,
    kategori_id INT,
    merk VARCHAR(100),
    satuan VARCHAR(30) DEFAULT 'Pcs',
    harga_modal DECIMAL(15,2) DEFAULT 0,
    harga_jual DECIMAL(15,2) DEFAULT 0,
    stok INT DEFAULT 0,
    stok_minimum INT DEFAULT 5,
    lokasi_rak VARCHAR(50),
    gambar VARCHAR(255),
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kategori_id) REFERENCES kategori_barang(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Jasa Servis
CREATE TABLE IF NOT EXISTS jasa_servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_jasa VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    harga DECIMAL(15,2) DEFAULT 0,
    persentase_mekanik DECIMAL(5,2) DEFAULT 50,
    estimasi_menit INT DEFAULT 30,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Supplier
CREATE TABLE IF NOT EXISTS supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_supplier VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    alamat TEXT,
    no_hp VARCHAR(20),
    email VARCHAR(100),
    catatan TEXT,
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Pelanggan
CREATE TABLE IF NOT EXISTS pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_pelanggan VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    no_hp VARCHAR(20),
    alamat TEXT,
    plat_nomor VARCHAR(20),
    tipe_motor VARCHAR(100),
    tahun_motor VARCHAR(4),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Mekanik
CREATE TABLE IF NOT EXISTS mekanik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_mekanik VARCHAR(20) NOT NULL UNIQUE,
    nama VARCHAR(150) NOT NULL,
    jabatan VARCHAR(100) DEFAULT 'Mekanik',
    persentase_jasa DECIMAL(5,2) DEFAULT 50,
    no_hp VARCHAR(20),
    status ENUM('aktif','nonaktif') DEFAULT 'aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- TRANSACTION TABLES
-- ============================================================

-- Pembelian Barang
CREATE TABLE IF NOT EXISTS pembelian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_faktur VARCHAR(30) NOT NULL UNIQUE,
    supplier_id INT,
    tanggal DATE NOT NULL,
    total DECIMAL(15,2) DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    pajak DECIMAL(15,2) DEFAULT 0,
    grand_total DECIMAL(15,2) DEFAULT 0,
    status_bayar ENUM('lunas','belum') DEFAULT 'belum',
    jatuh_tempo DATE,
    keterangan TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES supplier(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Detail Pembelian
CREATE TABLE IF NOT EXISTS pembelian_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pembelian_id INT NOT NULL,
    barang_id INT NOT NULL,
    qty INT DEFAULT 0,
    harga DECIMAL(15,2) DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (pembelian_id) REFERENCES pembelian(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Penjualan Sparepart
CREATE TABLE IF NOT EXISTS penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_transaksi VARCHAR(30) NOT NULL UNIQUE,
    tanggal DATE NOT NULL,
    subtotal DECIMAL(15,2) DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    pajak DECIMAL(15,2) DEFAULT 0,
    grand_total DECIMAL(15,2) DEFAULT 0,
    bayar DECIMAL(15,2) DEFAULT 0,
    kembali DECIMAL(15,2) DEFAULT 0,
    metode_bayar ENUM('tunai','transfer','kartu','qris') DEFAULT 'tunai',
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Detail Penjualan
CREATE TABLE IF NOT EXISTS penjualan_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penjualan_id INT NOT NULL,
    barang_id INT NOT NULL,
    qty INT DEFAULT 0,
    harga DECIMAL(15,2) DEFAULT 0,
    diskon DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (penjualan_id) REFERENCES penjualan(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Work Order / Servis Motor
CREATE TABLE IF NOT EXISTS work_order (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_wo VARCHAR(30) NOT NULL UNIQUE,
    pelanggan_id INT,
    plat_nomor VARCHAR(20),
    tipe_motor VARCHAR(100),
    km_sekarang INT DEFAULT 0,
    keluhan TEXT,
    diagnosa TEXT,
    mekanik_id INT,
    status ENUM('proses','selesai','diambil') DEFAULT 'proses',
    total_jasa DECIMAL(15,2) DEFAULT 0,
    total_sparepart DECIMAL(15,2) DEFAULT 0,
    grand_total DECIMAL(15,2) DEFAULT 0,
    status_bayar ENUM('lunas','belum') DEFAULT 'belum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE SET NULL,
    FOREIGN KEY (mekanik_id) REFERENCES mekanik(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Detail Jasa Work Order
CREATE TABLE IF NOT EXISTS work_order_jasa (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wo_id INT NOT NULL,
    jasa_id INT NOT NULL,
    harga DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (wo_id) REFERENCES work_order(id) ON DELETE CASCADE,
    FOREIGN KEY (jasa_id) REFERENCES jasa_servis(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Detail Sparepart Work Order
CREATE TABLE IF NOT EXISTS work_order_sparepart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wo_id INT NOT NULL,
    barang_id INT NOT NULL,
    qty INT DEFAULT 0,
    harga DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (wo_id) REFERENCES work_order(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- FINANCIAL TABLES
-- ============================================================

-- Hutang Supplier
CREATE TABLE IF NOT EXISTS hutang_supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT,
    no_faktur VARCHAR(30),
    jumlah DECIMAL(15,2) DEFAULT 0,
    terbayar DECIMAL(15,2) DEFAULT 0,
    sisa_hutang DECIMAL(15,2) DEFAULT 0,
    jatuh_tempo DATE,
    status ENUM('belum','cicilan','lunas') DEFAULT 'belum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES supplier(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Piutang Pelanggan
CREATE TABLE IF NOT EXISTS piutang_pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pelanggan_id INT,
    ref_type ENUM('wo','penjualan') NOT NULL,
    ref_id INT NOT NULL,
    jumlah DECIMAL(15,2) DEFAULT 0,
    terbayar DECIMAL(15,2) DEFAULT 0,
    sisa_piutang DECIMAL(15,2) DEFAULT 0,
    jatuh_tempo DATE,
    status ENUM('belum','cicilan','lunas') DEFAULT 'belum',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================
-- STOCK TABLES
-- ============================================================

-- Stock Opname
CREATE TABLE IF NOT EXISTS stok_opname (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_opname VARCHAR(30) NOT NULL UNIQUE,
    tanggal DATE NOT NULL,
    keterangan TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS stok_opname_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opname_id INT NOT NULL,
    barang_id INT NOT NULL,
    stok_sistem INT DEFAULT 0,
    stok_fisik INT DEFAULT 0,
    selisih INT DEFAULT 0,
    keterangan TEXT,
    FOREIGN KEY (opname_id) REFERENCES stok_opname(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- Retur
CREATE TABLE IF NOT EXISTS retur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_retur VARCHAR(30) NOT NULL UNIQUE,
    type ENUM('pembelian','penjualan') NOT NULL,
    ref_id INT NOT NULL,
    tanggal DATE NOT NULL,
    keterangan TEXT,
    user_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS retur_detail (
    id INT AUTO_INCREMENT PRIMARY KEY,
    retur_id INT NOT NULL,
    barang_id INT NOT NULL,
    qty INT DEFAULT 0,
    harga DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) DEFAULT 0,
    FOREIGN KEY (retur_id) REFERENCES retur(id) ON DELETE CASCADE,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ============================================================
-- SETTINGS & LOG
-- ============================================================

-- App Config
CREATE TABLE IF NOT EXISTS app_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    config_key VARCHAR(50) NOT NULL UNIQUE,
    config_value JSON,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Activity Log
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    username VARCHAR(50),
    role VARCHAR(30),
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Notifikasi Stok
CREATE TABLE IF NOT EXISTS notif_stok (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barang_id INT,
    tipe ENUM('menipis','habis') DEFAULT 'menipis',
    pesan TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barang_id) REFERENCES barang(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin (password: admin123 → MD5)
INSERT INTO users (username, password, nama, role, status) VALUES
('admin', MD5('admin123'), 'Administrator', 'owner', 'aktif'),
('kasir1', MD5('kasir123'), 'Kasir Utama', 'kasir', 'aktif'),
('mekanik1', MD5('mekanik123'), 'Andi Mekanik', 'mekanik', 'aktif');

-- Kategori Barang
INSERT INTO kategori_barang (nama, deskripsi) VALUES
('Oli & Pelumas', 'Oli mesin, gear, rem, dll'),
('Filter', 'Filter oli, udara, bensin'),
('Busi', 'Busi semua tipe motor'),
('Kampas Rem', 'Kampas rem depan & belakang'),
('Rantai & Gir', 'Rantai, gir, gear set'),
('Aki', 'Accu / Aki semua tipe'),
('Lampu', 'Lampu headlight, sein, rem'),
('Kelistrikan', 'Spull, kabel body, soket'),
('Ban & Velg', 'Ban tubeless, tube, velg'),
('Body & Aksesoris', 'Spion, jok, cover body, dll'),
('Suku Cadang Mesin', 'Piston, gasket, seal, bearing'),
('Chemical', 'Carburator cleaner, chain lube, dll');

-- Jasa Servis
INSERT INTO jasa_servis (kode_jasa, nama, deskripsi, harga, persentase_mekanik, estimasi_menit) VALUES
('JS001', 'Ganti Oli', 'Ganti oli mesin standar', 25000, 40, 15),
('JS002', 'Servis Ringan', 'Ganti oli + cek busi + setel rantai', 50000, 45, 30),
('JS003', 'Servis Berat', 'Servis ringan + ganti filter + setel klep', 120000, 50, 60),
('JS004', 'Tune Up', 'Full tune up engine', 150000, 50, 90),
('JS005', 'Ganti Kampas Rem Depan', 'Ganti kampas rem depan', 30000, 40, 20),
('JS006', 'Ganti Kampas Rem Belakang', 'Ganti kampas rem belakang', 30000, 40, 20),
('JS007', 'Ganti Busi', 'Ganti busi standar', 15000, 30, 10),
('JS008', 'Ganti Filter Oli', 'Ganti filter oli', 20000, 35, 15),
('JS009', 'Setel Rantai', 'Setel dan lumasi rantai', 20000, 40, 15),
('JS010', 'Ganti Aki', 'Ganti aki / accu', 25000, 30, 15),
('JS011', 'Overhaul Mesin', 'Bongkar total mesin', 500000, 60, 240),
('JS012', 'Ganti Bearing Roda', 'Ganti bearing roda depan/belakang', 40000, 45, 30);

-- Default App Config
INSERT INTO app_config (config_key, config_value) VALUES
('nama_bengkel', '"Bengkel Motor Pro"'),
('alamat_bengkel', '"Jl. Contoh No. 1, Kota"'),
('no_telp_bengkel', '"08123456789"'),
('pajak_persen', '11'),
('diskon_default', '0'),
('printer_struk', '"Default"');

-- Contoh Supplier
INSERT INTO supplier (kode_supplier, nama, alamat, no_hp, email) VALUES
('SUP001', 'Toko Sparepart Jaya', 'Jl. Merdeka No. 10', '081234567890', 'jaya@sparepart.com'),
('SUP002', 'Distributor Oli Nusantara', 'Jl. Sudirman No. 25', '082345678901', 'nusantara@oli.com'),
('SUP003', 'Bengkel Center Supply', 'Jl. Gatot Subroto No. 5', '083456789012', 'supply@bengkelcenter.com');
