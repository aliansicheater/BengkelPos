<?php
$pageTitle = 'Data Supplier';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/auth.php';
requireRole(['owner','admin','gudang']);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 animate-fade-in-up">
    <div>
        <h1 class="h4 font-bold" style="color:#f1f5f9"><i class="fas fa-truck mr-2" style="color:#a78bfa"></i>Data Supplier</h1>
        <small style="color:#64748b">Kelola data supplier dan vendor parts</small>
    </div>
    <button class="btn btn-primary mt-2 mt-md-0" onclick="openForm()"><i class="fas fa-plus mr-1"></i> Tambah Supplier</button>
</div>

<div class="card mb-4 animate-fade-in-up" style="animation-delay:0.1s;opacity:0">
    <div class="card-body p-3">
        <div class="input-group" style="max-width:400px">
            <div class="input-group-prepend"><span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem;color:#64748b"><i class="fas fa-search"></i></span></div>
            <input type="text" class="form-control" id="search-input" placeholder="Cari supplier..." oninput="loadData()" style="border-radius:0 0.75rem 0.75rem 0">
        </div>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay:0.2s;opacity:0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>No</th><th>Kode</th><th>Nama</th><th>Alamat</th><th>No HP</th><th>Email</th><th>Aksi</th></tr></thead>
                <tbody id="data-container"><tr><td colspan="7" class="text-center py-5" style="color:#64748b"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1" role="dialog"><div class="modal-dialog modal-lg" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modal-title">Tambah Supplier</h5><button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button></div>
    <div class="modal-body">
        <form id="data-form" onsubmit="event.preventDefault();save()">
            <input type="hidden" name="id" id="f-id">
            <div class="form-group"><label class="form-label" style="font-size:0.8rem">Nama Supplier <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama" id="f-nama" required placeholder="Nama supplier / toko"></div>
            <div class="form-group"><label class="form-label" style="font-size:0.8rem">Alamat</label><textarea class="form-control" name="alamat" id="f-alamat" rows="2" placeholder="Alamat lengkap..."></textarea></div>
            <div class="row">
                <div class="col-md-6"><div class="form-group"><label class="form-label" style="font-size:0.8rem">No HP</label><input type="text" class="form-control" name="no_hp" id="f-hp" placeholder="08xxx"></div></div>
                <div class="col-md-6"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Email</label><input type="email" class="form-control" name="email" id="f-email" placeholder="supplier@email.com"></div></div>
            </div>
            <div class="form-group"><label class="form-label" style="font-size:0.8rem">Catatan</label><textarea class="form-control" name="catatan" id="f-catatan" rows="2" placeholder="Catatan tentang supplier..."></textarea></div>
        </form>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Batal</button><button class="btn btn-primary" onclick="save()"><i class="fas fa-save mr-1"></i> Simpan</button></div>
</div></div></div>

<?php $extraScripts = '
async function loadData(){
    const c=document.getElementById("data-container");
    const q=document.getElementById("search-input").value.toLowerCase();
    const res=await apiRequest("getSupplier",{},"GET");
    if(res.status==="success"&&res.data){
        let filtered=res.data;
        if(q)filtered=filtered.filter(s=>s.nama.toLowerCase().includes(q)||(s.alamat||"").toLowerCase().includes(q));
        if(filtered.length>0){
            c.innerHTML=filtered.map((s,i)=>`<tr style="animation:fadeInUp 0.3s ease forwards;opacity:0;animation-delay:${i*0.03}s">
                <td>${i+1}</td>
                <td><span style="color:#0ea5e9;font-weight:600;font-size:0.8rem">${s.kode_supplier}</span></td>
                <td class="font-semibold">${s.nama}</td>
                <td><small style="color:#64748b">${s.alamat||"-"}</small></td>
                <td>${s.no_hp||"-"}</td>
                <td><small>${s.email||"-"}</small></td>
                <td><div class="btn-group btn-group-sm"><button class="btn btn-secondary" onclick="edit(${s.id})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-secondary" onclick="hapus(${s.id})" title="Hapus" style="color:#ef4444"><i class="fas fa-trash"></i></button></div></td>
            </tr>`).join("");
        } else { c.innerHTML=`<tr><td colspan="7" class="text-center py-5" style="color:#64748b"><p>Belum ada data supplier</p></td></tr>`; }
    }
}
function openForm(){
    ["f-id","f-nama","f-alamat","f-hp","f-email","f-catatan"].forEach(id=>document.getElementById(id).value="");
    document.getElementById("modal-title").innerHTML="<i class=\\"fas fa-plus-circle mr-2\\" style=\\"color:#22c55e\\"></i>Tambah Supplier";
    $("#formModal").modal("show");
}
async function edit(id){
    const res=await apiRequest("getSupplier",{},"GET");
    if(res.status==="success"&&res.data){
        const s=res.data.find(x=>x.id==id);if(!s)return;
        document.getElementById("f-id").value=s.id;
        document.getElementById("f-nama").value=s.nama;
        document.getElementById("f-alamat").value=s.alamat||"";
        document.getElementById("f-hp").value=s.no_hp||"";
        document.getElementById("f-email").value=s.email||"";
        document.getElementById("f-catatan").value=s.catatan||"";
        document.getElementById("modal-title").innerHTML="<i class=\\"fas fa-edit mr-2\\" style=\\"color:#f59e0b\\"></i>Edit Supplier";
        $("#formModal").modal("show");
    }
}
async function save(){
    const f=document.getElementById("data-form");
    if(!f.checkValidity()){f.reportValidity();return;}
    const d=Object.fromEntries(new FormData(f).entries());
    const r=await apiRequest(d.id?"updateSupplier":"addSupplier",d);
    if(r.status==="success"){showToast(r.message);$("#formModal").modal("hide");loadData();}
    else showToast(r.message||"Gagal","error");
}
async function hapus(id){
    if(!confirm("Yakin hapus supplier ini?"))return;
    const r=await apiRequest("deleteSupplier",{id});
    if(r.status==="success"){showToast("Dihapus");loadData();}else showToast(r.message||"Gagal","error");
}
loadData();
'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
