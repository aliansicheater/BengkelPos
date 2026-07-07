<?php
$pageTitle = 'Data Barang';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/auth.php';
requireRole(['owner','admin','gudang']);
?>

<!-- Page Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 animate-fade-in-up">
    <div>
        <h1 class="h4 font-bold" style="color:#f1f5f9"><i class="fas fa-boxes mr-2" style="color:#0ea5e9"></i>Data Barang</h1>
        <small style="color:#64748b">Kelola stok barang dan sparepart bengkel</small>
    </div>
    <button class="btn btn-primary mt-2 mt-md-0" onclick="openForm()">
        <i class="fas fa-plus mr-1"></i> Tambah Barang
    </button>
</div>

<!-- Search & Filter -->
<div class="card mb-4 animate-fade-in-up" style="animation-delay:0.1s;opacity:0">
    <div class="card-body p-3">
        <div class="row align-items-center">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem;color:#64748b"><i class="fas fa-search"></i></span>
                    </div>
                    <input type="text" class="form-control" id="search-input" placeholder="Cari kode, nama, atau barcode..." oninput="loadBarang(1, this.value)" style="border-radius:0 0.75rem 0.75rem 0">
                </div>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <select class="form-control" id="filter-kategori" onchange="loadBarang(1, document.getElementById('search-input').value)">
                    <option value="">Semua Kategori</option>
                </select>
            </div>
            <div class="col-md-3 text-md-right">
                <button class="btn btn-secondary btn-sm" onclick="loadBarang(1)"><i class="fas fa-sync mr-1"></i> Refresh</button>
            </div>
        </div>
    </div>
</div>

<!-- Data Table -->
<div class="card animate-fade-in-up" style="animation-delay:0.2s;opacity:0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="tabel-barang">
                <thead>
                    <tr>
                        <th style="width:50px">No</th>
                        <th>Kode / Barcode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        <th>Harga Jual</th>
                        <th>Stok</th>
                        <th>Status</th>
                        <th style="width:120px">Aksi</th>
                    </tr>
                </thead>
                <tbody id="data-container">
                    <tr><td colspan="8" class="text-center py-5" style="color:#64748b"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat data...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" id="pagination-container" style="background:transparent;border-top:1px solid rgba(255,255,255,0.05)">
    </div>
</div>

<!-- Modal Form -->
<div class="modal fade" id="formModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-title"><i class="fas fa-box mr-2" style="color:#0ea5e9"></i>Tambah Barang</h5>
                <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body">
                <form id="data-form" onsubmit="event.preventDefault(); saveBarang()">
                    <input type="hidden" name="id" id="form-id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Kode Barang</label>
                                <input type="text" class="form-control" name="kode_barang" id="form-kode" readonly style="background:rgba(255,255,255,0.02) !important;color:#64748b !important">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Barcode</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" name="barcode" id="form-barcode" placeholder="Scan atau ketik manual">
                                    <div class="input-group-append">
                                        <button type="button" class="btn btn-secondary" onclick="openCameraScanner()" title="Scan dari kamera">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label font-semibold" style="font-size:0.8rem">Nama Barang <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="nama" id="form-nama" required placeholder="Contoh: Oli MPX 10W-40">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Kategori</label>
                                <select class="form-control form-select" name="kategori_id" id="form-kategori">
                                    <option value="">-- Pilih --</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Merk</label>
                                <input type="text" class="form-control" name="merk" id="form-merk" placeholder="Contoh: AHM, NGK">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Satuan</label>
                                <select class="form-control form-select" name="satuan" id="form-satuan">
                                    <option value="Pcs">Pcs</option>
                                    <option value="Botol">Botol</option>
                                    <option value="Liter">Liter</option>
                                    <option value="Set">Set</option>
                                    <option value="Unit">Unit</option>
                                    <option value="Roll">Roll</option>
                                    <option value="Meter">Meter</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Harga Modal (Rp)</label>
                                <input type="number" class="form-control" name="harga_modal" id="form-harga_modal" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Harga Jual (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="harga_jual" id="form-harga_jual" min="0" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Lokasi Rak</label>
                                <input type="text" class="form-control" name="lokasi_rak" id="form-lokasi_rak" placeholder="Contoh: A-01">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Stok Saat Ini</label>
                                <input type="number" class="form-control" name="stok" id="form-stok" min="0" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Minimum Stok</label>
                                <input type="number" class="form-control" name="stok_minimum" id="form-stok_minimum" min="0" value="5">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label font-semibold" style="font-size:0.8rem">Status</label>
                                <select class="form-control form-select" name="status" id="form-status">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Non Aktif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Batal</button>
                <button type="button" class="btn btn-primary" onclick="saveBarang()"><i class="fas fa-save mr-1"></i> Simpan</button>
            </div>
        </div>
    </div>
</div>

<!-- Scanner Modal -->
<div class="modal fade" id="scannerModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-camera mr-2" style="color:#0ea5e9"></i>Scan Barcode</h5>
                <button type="button" class="close" data-dismiss="modal" onclick="stopScanner()"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body text-center">
                <div id="scanner-reader" style="border-radius:0.75rem;overflow:hidden"></div>
                <p class="mt-3" style="color:#64748b;font-size:0.85rem">Arahkan kamera ke barcode barang</p>
            </div>
        </div>
    </div>
</div>

<!-- Barcode Print Modal -->
<div class="modal fade" id="barcodePrintModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-print mr-2" style="color:#0ea5e9"></i>Cetak Label Barcode</h5>
                <button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button>
            </div>
            <div class="modal-body text-center" id="barcode-preview"></div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button class="btn btn-primary" onclick="printBarcode()"><i class="fas fa-print mr-1"></i> Cetak</button>
            </div>
        </div>
    </div>
</div>

<?php $extraScripts = '
let lastScanCode = "";

async function loadKategori() {
    const res = await apiRequest("getKategoriBarang", {}, "GET");
    if (res.status === "success" && res.data) {
        const sel = document.getElementById("filter-kategori");
        const fsel = document.getElementById("form-kategori");
        res.data.forEach(k => {
            sel.innerHTML += `<option value="${k.id}">${k.nama}</option>`;
            fsel.innerHTML += `<option value="${k.id}">${k.nama}</option>`;
        });
    }
}

async function loadBarang(page = 1, search = "") {
    const c = document.getElementById("data-container");
    c.innerHTML = Array(5).fill("").map(() => `<tr><td colspan="8"><div class="d-flex align-items-center p-3"><div class="skeleton" style="width:40px;height:40px;border-radius:0.5rem"></div><div class="ml-3 flex-grow-1"><div class="skeleton mb-1" style="height:12px;width:30%"></div><div class="skeleton" style="height:10px;width:50%"></div></div></div></td></tr>`).join("");
    
    const res = await apiRequest(`getBarang?page=${page}&limit=10&search=${encodeURIComponent(search)}`, {}, "GET");
    
    if (res.status === "success" && res.data && res.data.length > 0) {
        let html = "";
        res.data.forEach((b, i) => {
            const sk = b.stok <= b.stok_minimum ? "text-warning font-bold" : "";
            const sb = b.stok <= b.stok_minimum ? `<span class="badge badge-warning ml-1" style="font-size:0.65rem"><i class="fas fa-exclamation-triangle mr-1"></i>Min</span>` : "";
            html += `<tr style="animation:fadeInUp 0.3s ease forwards;opacity:0;animation-delay:${i*0.03}s">
                <td>${(res.pagination.page-1)*res.pagination.limit+i+1}</td>
                <td><div class="d-flex align-items-center"><div id="bc-${b.id}" class="mr-2" style="cursor:pointer" onclick="previewBarcode(\\x27${b.kode_barang}\\x27,\\x27${b.nama.replace(/'/g,"\\'")}\\x27)" title="Cetak label"></div><div><span class="font-semibold" style="color:#0ea5e9;font-size:0.8rem">${b.kode_barang}</span>${b.barcode?`<br><small style="color:#64748b">${b.barcode}</small>`:""}</div></div></td>
                <td><div class="font-semibold">${b.nama}</div>${b.merk?`<small style="color:#64748b">${b.merk}</small>`:""}</td>
                <td><span class="badge badge-info">${b.kategori_nama||"-"}</span></td>
                <td class="font-bold">${formatRupiah(b.harga_jual)}</td>
                <td><span class="${sk}">${b.stok} ${b.satuan}</span>${sb}</td>
                <td>${b.status==="aktif"?`<span class="badge badge-success">Aktif</span>`:`<span class="badge badge-danger">Non Aktif</span>`}</td>
                <td><div class="btn-group btn-group-sm"><button class="btn btn-secondary" onclick="editBarang(${b.id})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-secondary" onclick="deleteBarang(${b.id})" title="Hapus" style="color:#ef4444"><i class="fas fa-trash"></i></button></div></td>
            </tr>`;
        });
        c.innerHTML = html;
        res.data.forEach(b => { const el = document.getElementById("bc-"+b.id); if(el&&b.kode_barang) try{JsBarcode(el,b.kode_barang,{format:"CODE128",width:1,height:20,displayValue:false,margin:2});}catch(e){} });
        renderPagination(res.pagination);
    } else {
        c.innerHTML = `<tr><td colspan="8" class="text-center py-5" style="color:#64748b"><i class="fas fa-box-open mb-3 d-block" style="font-size:2.5rem;opacity:0.3"></i><p class="font-medium">Belum ada data barang</p><small>Klik "Tambah Barang" untuk menambah data baru</small></td></tr>`;
        document.getElementById("pagination-container").innerHTML = "";
    }
}

function renderPagination(p) {
    if (!p||p.pages<=1) { document.getElementById("pagination-container").innerHTML=""; return; }
    let h = `<nav><ul class="pagination pagination-sm justify-content-center mb-0">`;
    h += `<li class="page-item ${p.page<=1?"disabled":""}"><a class="page-link" href="#" onclick="event.preventDefault();loadBarang(${p.page-1})"><i class="fas fa-chevron-left"></i></a></li>`;
    for(let i=1;i<=p.pages;i++) { if(Math.abs(i-p.page)<=2||i===1||i===p.pages) h+=`<li class="page-item ${i===p.page?"active":""}"><a class="page-link" href="#" onclick="event.preventDefault();loadBarang(${i})">${i}</a></li>`; else if(Math.abs(i-p.page)===3) h+=`<li class="page-item disabled"><span class="page-link">...</span></li>`; }
    h += `<li class="page-item ${p.page>=p.pages?"disabled":""}"><a class="page-link" href="#" onclick="event.preventDefault();loadBarang(${p.page+1})"><i class="fas fa-chevron-right"></i></a></li></ul></nav>`;
    document.getElementById("pagination-container").innerHTML = h;
}

function openForm() {
    ["form-id","form-nama","form-barcode","form-merk","form-lokasi_rak"].forEach(id => document.getElementById(id).value = "");
    document.getElementById("form-id").value = "";
    document.getElementById("form-kode").value = "(Auto)";
    document.getElementById("form-kategori").value = "";
    document.getElementById("form-satuan").value = "Pcs";
    document.getElementById("form-harga_modal").value = "0";
    document.getElementById("form-harga_jual").value = "0";
    document.getElementById("form-stok").value = "0";
    document.getElementById("form-stok_minimum").value = "5";
    document.getElementById("form-status").value = "aktif";
    document.getElementById("modal-title").innerHTML = "<i class=\\"fas fa-plus-circle mr-2\\" style=\\"color:#22c55e\\"></i>Tambah Barang";
    $("#formModal").modal("show");
}

async function editBarang(id) {
    const res = await apiRequest("getBarang", {id}, "POST");
    if (res.status === "success" && res.data) {
        const b = Array.isArray(res.data) ? res.data[0] : res.data;
        document.getElementById("form-id").value = b.id;
        document.getElementById("form-kode").value = b.kode_barang;
        document.getElementById("form-nama").value = b.nama;
        document.getElementById("form-barcode").value = b.barcode || "";
        document.getElementById("form-merk").value = b.merk || "";
        document.getElementById("form-kategori").value = b.kategori_id || "";
        document.getElementById("form-satuan").value = b.satuan || "Pcs";
        document.getElementById("form-harga_modal").value = b.harga_modal || 0;
        document.getElementById("form-harga_jual").value = b.harga_jual || 0;
        document.getElementById("form-stok").value = b.stok || 0;
        document.getElementById("form-stok_minimum").value = b.stok_minimum || 5;
        document.getElementById("form-lokasi_rak").value = b.lokasi_rak || "";
        document.getElementById("form-status").value = b.status || "aktif";
        document.getElementById("modal-title").innerHTML = "<i class=\\"fas fa-edit mr-2\\" style=\\"color:#f59e0b\\"></i>Edit Barang";
        $("#formModal").modal("show");
    }
}

async function saveBarang() {
    const form = document.getElementById("data-form");
    if (!form.checkValidity()) { form.reportValidity(); return; }
    const data = Object.fromEntries(new FormData(form).entries());
    const res = await apiRequest(data.id ? "updateBarang" : "addBarang", data);
    if (res.status === "success") { showToast(res.message || "Data tersimpan"); $("#formModal").modal("hide"); loadBarang(1, document.getElementById("search-input").value); }
    else { showToast(res.message || "Gagal menyimpan", "error"); }
}

async function deleteBarang(id) {
    if (!confirm("Yakin ingin menghapus barang ini?")) return;
    const res = await apiRequest("deleteBarang", {id});
    if (res.status === "success") { showToast("Barang dihapus"); loadBarang(1, document.getElementById("search-input").value); }
    else showToast(res.message || "Gagal", "error");
}

function openCameraScanner() {
    $("#scannerModal").modal("show");
    setTimeout(() => {
        startScanner("scanner-reader", (code) => {
            document.getElementById("form-barcode").value = code;
            $("#scannerModal").modal("hide");
            stopScanner();
            showToast("Barcode: " + code);
        });
    }, 500);
}

function previewBarcode(kode, nama) {
    document.getElementById("barcode-preview").innerHTML = `<div class="p-3" id="printable-barcode"><svg id="modal-barcode-svg"></svg><p class="mt-2 font-semibold" style="color:#0f172a">${nama}</p><small style="color:#64748b">${kode}</small></div>`;
    $("#barcodePrintModal").modal("show");
    setTimeout(() => { try{JsBarcode("#modal-barcode-svg",kode,{format:"CODE128",width:2,height:60,displayValue:true,fontSize:14,margin:10});}catch(e){} }, 300);
}

function printBarcode() {
    const svg = document.getElementById("modal-barcode-svg");
    const nama = svg.nextElementSibling.textContent;
    const kode = svg.nextElementSibling.nextElementSibling.textContent;
    const win = window.open("","_blank","width=300,height=400");
    win.document.write(`<html><head><title>Cetak Barcode</title><script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"><\\/script></head><body onload="JsBarcode(\\"#bc\\",\\"${kode}\\",{format:\\"CODE128\\",width:2,height:60,displayValue:true,fontSize:14,margin:10});setTimeout(()=>{window.print();window.close()},500)"><div style="text-align:center;padding:10px"><svg id="bc"></svg><p style="font-weight:bold;margin-top:8px">${nama}</p><small>${kode}</small></div></body></html>`);
    win.document.close();
}

loadKategori();
loadBarang();
'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
