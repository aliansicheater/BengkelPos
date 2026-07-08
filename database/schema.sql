-- ============================================================
-- Database: db_bengkelpos
-- Aplikasi Point of Sale Bengkel Motor
-- ============================================================

CREATE DATABASE IF NOT EXISTS db_bengkelpos;
USE db_bengkelpos;

-- ------------------------------------------------------------
-- 1. Tabel Users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    nama_lengkap VARCHAR(100) NOT NULL,
    role ENUM('admin','kasir') NOT NULL DEFAULT 'kasir',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 2. Tabel Kategori
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kategori VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 3. Tabel Barang
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS barang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kode_barang VARCHAR(50) NOT NULL UNIQUE,
    nama_barang VARCHAR(150) NOT NULL,
    id_kategori INT,
    harga_beli DECIMAL(12,2) NOT NULL DEFAULT 0,
    harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0,
    stok INT NOT NULL DEFAULT 0,
    stok_minimal INT NOT NULL DEFAULT 5,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (id_kategori) REFERENCES kategori(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 4. Tabel Pelanggan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pelanggan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 5. Tabel Mekanik
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS mekanik (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_mekanik VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT,
    status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 6. Tabel Jasa Servis
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS jasa_servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_jasa VARCHAR(100) NOT NULL,
    upah_mekanik DECIMAL(12,2) NOT NULL DEFAULT 0,
    harga_jual DECIMAL(12,2) NOT NULL DEFAULT 0,
    keterangan TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 7. Tabel Supplier
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS supplier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_supplier VARCHAR(100) NOT NULL,
    no_telepon VARCHAR(20),
    alamat TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 8. Tabel Penjualan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_invoice VARCHAR(20) NOT NULL UNIQUE,
    tgl DATE NOT NULL,
    id_pelanggan INT,
    id_user INT NOT NULL,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    bayar DECIMAL(12,2) NOT NULL DEFAULT 0,
    kembalian DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id) ON DELETE SET NULL,
    FOREIGN KEY (id_user) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 9. Tabel Detail Penjualan
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detail_penjualan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_penjualan INT NOT NULL,
    id_barang INT,
    qty INT NOT NULL DEFAULT 1,
    harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_penjualan) REFERENCES penjualan(id) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 10. Tabel Servis
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_invoice VARCHAR(20) NOT NULL UNIQUE,
    tgl DATE NOT NULL,
    id_pelanggan INT,
    id_user INT NOT NULL,
    no_plat VARCHAR(20),
    jenis_motor VARCHAR(100),
    keluhan TEXT,
    total_jasa DECIMAL(12,2) NOT NULL DEFAULT 0,
    total_barang DECIMAL(12,2) NOT NULL DEFAULT 0,
    grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
    bayar DECIMAL(12,2) NOT NULL DEFAULT 0,
    kembalian DECIMAL(12,2) NOT NULL DEFAULT 0,
    status ENUM('pending','selesai') NOT NULL DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_pelanggan) REFERENCES pelanggan(id) ON DELETE SET NULL,
    FOREIGN KEY (id_user) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 11. Tabel Detail Servis
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detail_servis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_servis INT NOT NULL,
    id_jasa INT,
    id_barang INT,
    id_mekanik INT,
    qty INT NOT NULL DEFAULT 1,
    harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    tipe ENUM('jasa','barang') NOT NULL,
    FOREIGN KEY (id_servis) REFERENCES servis(id) ON DELETE CASCADE,
    FOREIGN KEY (id_jasa) REFERENCES jasa_servis(id) ON DELETE SET NULL,
    FOREIGN KEY (id_barang) REFERENCES barang(id) ON DELETE SET NULL,
    FOREIGN KEY (id_mekanik) REFERENCES mekanik(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 12. Tabel Pembelian
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pembelian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    no_po VARCHAR(20) NOT NULL UNIQUE,
    tgl DATE NOT NULL,
    id_user INT NOT NULL,
    id_supplier INT,
    total DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id),
    FOREIGN KEY (id_supplier) REFERENCES supplier(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- 13. Tabel Detail Pembelian
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS detail_pembelian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_pembelian INT NOT NULL,
    id_barang INT,
    qty INT NOT NULL DEFAULT 1,
    harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
    FOREIGN KEY (id_pembelian) REFERENCES pembelian(id) ON DELETE CASCADE,
    FOREIGN KEY (id_barang) REFERENCES barang(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Seed Data: Default User & Contoh Data
-- ------------------------------------------------------------
INSERT INTO users (username, password, nama_lengkap, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('kasir', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Kasir', 'kasir');
-- Password default: "password"

INSERT INTO kategori (nama_kategori) VALUES
('Oli & Pelumas'),
('Ban & Aksesoris'),
('Sparepart Mesin'),
('Body & Knalpot'),
('Kelistrikan');

INSERT INTO mekanik (nama_mekanik, no_telepon, status) VALUES
('Budi Santoso', '081234567890', 'aktif'),
('Agus Wijaya', '081234567891', 'aktif'),
('Dedi Kurniawan', '081234567892', 'aktif');

INSERT INTO jasa_servis (nama_jasa, upah_mekanik, harga_jual, keterangan) VALUES
('Ganti Oli Mesin', 15000, 35000, 'Termasuk oli dan jasa'),
('Service Rem Depan', 20000, 50000, 'Bersihkan kanvas, setel rem'),
('Service Rem Belakang', 20000, 50000, 'Bersihkan kanvas, setel rem'),
('Ganti Busi', 10000, 25000, 'Busi standar'),
('Setel Klep', 30000, 75000, 'Semua motor silinder tunggal'),
('Cuci Motor', 0, 25000, 'Cuci bersih + lap'),
('Overhaul Mesin', 150000, 350000, 'Turun mesin lengkap');
