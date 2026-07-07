<?php
$pageTitle = 'Data Pelanggan';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/auth.php';
requireRole(['owner','admin','kasir']);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 animate-fade-in-up">
    <div>
        <h1 class="h4 font-bold" style="color:#f1f5f9"><i class="fas fa-users mr-2" style="color:#38bdf8"></i>Data Pelanggan</h1>
        <small style="color:#64748b">Kelola data pelanggan dan riwayat servis</small>
    </div>
    <button class="btn btn-primary mt-2 mt-md-0" onclick="openForm()"><i class="fas fa-plus mr-1"></i> Tambah Pelanggan</button>
</div>

<div class="card mb-4 animate-fade-in-up" style="animation-delay:0.1s;opacity:0">
    <div class="card-body p-3">
        <div class="input-group" style="max-width:400px">
            <div class="input-group-prepend"><span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem;color:#64748b"><i class="fas fa-search"></i></span></div>
            <input type="text" class="form-control" id="search-input" placeholder="Cari nama, no HP, atau plat..." oninput="loadData()" style="border-radius:0 0.75rem 0.75rem 0">
        </div>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay:0.2s;opacity:0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>No</th><th>Kode</th><th>Nama</th><th>No HP</th><th>Plat</th><th>Tipe Motor</th><th>Aksi</th></tr></thead>
                <tbody id="data-container"><tr><td colspan="7" class="text-center py-5" style="color:#64748b"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1" role="dialog"><div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modal-title">Tambah Pelanggan</h5><button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button></div>
    <div class="modal-body">
        <form id="data-form" onsubmit="event.preventDefault();save()">
            <input type="hidden" name="id" id="f-id">
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Nama <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama" id="f-nama" required placeholder="Nama pelanggan"></div></div>
                <div class="col-md-6"><div class="form-group"><label class="form-label" style="font-size:0.8rem">No HP</label><input type="text" class="form-control" name="no_hp" id="f-hp" placeholder="08xxx"></div></div>
            </div>
            <div class="form-group"><label class="form-label" style="font-size:0.8rem">Alamat</label><textarea class="form-control" name="alamat" id="f-alamat" rows="2" placeholder="Alamat lengkap..."></textarea></div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Plat Nomor</label><input type="text" class="form-control" name="plat_nomor" id="f-plat" placeholder="B 1234 XX" style="text-transform:uppercase"></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Tipe Motor</label><input type="text" class="form-control" name="tipe_motor" id="f-tipe" placeholder="Contoh: Beat, Vario, Mio"></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Tahun</label><input type="number" class="form-control" name="tahun_motor" id="f-tahun" min="1990" max="2030" placeholder="2024"></div></div>
            </div>
        </form>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Batal</button><button class="btn btn-primary" onclick="save()"><i class="fas fa-save mr-1"></i> Simpan</button></div>
</div></div></div>

<?php $extraScripts = '
async function loadData(){
    const c=document.getElementById("data-container");
    const q=document.getElementById("search-input").value;
    const res=await apiRequest("getPelanggan",{search:q},"GET");
    if(res.status==="success"&&res.data&&res.data.length>0){
        c.innerHTML=res.data.map((p,i)=>`<tr style="animation:fadeInUp 0.3s ease forwards;opacity:0;animation-delay:${i*0.03}s">
            <td>${i+1}</td>
            <td><span style="color:#0ea5e9;font-weight:600;font-size:0.8rem">${p.kode_pelanggan}</span></td>
            <td><div class="font-semibold">${p.nama}</div>${p.alamat?`<small style="color:#64748b">${p.alamat.substring(0,40)}</small>`:""}</td>
            <td>${p.no_hp||"-"}</td>
            <td><span class="badge badge-info">${p.plat_nomor||"-"}</span></td>
            <td>${p.tipe_motor||"-"} ${p.tahun_motor?"("+p.tahun_motor+")":""}</td>
            <td><div class="btn-group btn-group-sm"><button class="btn btn-secondary" onclick="edit(${p.id})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-secondary" onclick="hapus(${p.id})" title="Hapus" style="color:#ef4444"><i class="fas fa-trash"></i></button></div></td>
        </tr>`).join("");
    } else { c.innerHTML=`<tr><td colspan="7" class="text-center py-5" style="color:#64748b"><i class="fas fa-users mb-3 d-block" style="font-size:2.5rem;opacity:0.3"></i><p>Belum ada data pelanggan</p></td></tr>`; }
}
function openForm(){
    ["f-id","f-nama","f-hp","f-alamat","f-plat","f-tipe","f-tahun"].forEach(id=>document.getElementById(id).value="");
    document.getElementById("modal-title").innerHTML="<i class=\\"fas fa-plus-circle mr-2\\" style=\\"color:#22c55e\\"></i>Tambah Pelanggan";
    $("#formModal").modal("show");
}
async function edit(id){
    const res=await apiRequest("getPelanggan",{},"GET");
    if(res.status==="success"&&res.data){
        const p=res.data.find(x=>x.id==id);if(!p)return;
        document.getElementById("f-id").value=p.id;
        document.getElementById("f-nama").value=p.nama;
        document.getElementById("f-hp").value=p.no_hp||"";
        document.getElementById("f-alamat").value=p.alamat||"";
        document.getElementById("f-plat").value=p.plat_nomor||"";
        document.getElementById("f-tipe").value=p.tipe_motor||"";
        document.getElementById("f-tahun").value=p.tahun_motor||"";
        document.getElementById("modal-title").innerHTML="<i class=\\"fas fa-edit mr-2\\" style=\\"color:#f59e0b\\"></i>Edit Pelanggan";
        $("#formModal").modal("show");
    }
}
async function save(){
    const f=document.getElementById("data-form");
    if(!f.checkValidity()){f.reportValidity();return;}
    const d=Object.fromEntries(new FormData(f).entries());
    const r=await apiRequest(d.id?"updatePelanggan":"addPelanggan",d);
    if(r.status==="success"){showToast(r.message);$("#formModal").modal("hide");loadData();}
    else showToast(r.message||"Gagal","error");
}
async function hapus(id){
    if(!confirm("Yakin hapus pelanggan ini?"))return;
    const r=await apiRequest("deletePelanggan",{id});
    if(r.status==="success"){showToast("Dihapus");loadData();}else showToast(r.message||"Gagal","error");
}
loadData();
'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
