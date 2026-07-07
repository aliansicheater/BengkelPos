<?php
$pageTitle = 'Data Mekanik';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/auth.php';
requireRole(['owner','admin']);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 animate-fade-in-up">
    <div>
        <h1 class="h4 font-bold" style="color:#f1f5f9"><i class="fas fa-user-cog mr-2" style="color:#f59e0b"></i>Data Mekanik</h1>
        <small style="color:#64748b">Kelola data mekanik dan teknisi</small>
    </div>
    <button class="btn btn-warning mt-2 mt-md-0" onclick="openForm()"><i class="fas fa-plus mr-1"></i> Tambah Mekanik</button>
</div>

<div class="card mb-4 animate-fade-in-up" style="animation-delay:0.1s;opacity:0">
    <div class="card-body p-3">
        <div class="input-group" style="max-width:400px">
            <div class="input-group-prepend"><span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem;color:#64748b"><i class="fas fa-search"></i></span></div>
            <input type="text" class="form-control" id="search-input" placeholder="Cari nama mekanik..." oninput="loadData()" style="border-radius:0 0.75rem 0.75rem 0">
        </div>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay:0.2s;opacity:0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>No</th><th>Kode</th><th>Nama</th><th>Jabatan</th><th>Persentase Jasa</th><th>No HP</th><th>Aksi</th></tr></thead>
                <tbody id="data-container"><tr><td colspan="7" class="text-center py-5" style="color:#64748b"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modal-title">Tambah Mekanik</h5><button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button></div>
    <div class="modal-body">
        <form id="data-form" onsubmit="event.preventDefault();save()">
            <input type="hidden" name="id" id="f-id">
            <div class="form-group"><label class="form-label" style="font-size:0.8rem">Nama <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama" id="f-nama" required placeholder="Nama mekanik"></div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Jabatan</label>
                    <select class="form-control form-select" name="jabatan" id="f-jab">
                        <option value="Mekanik">Mekanik</option>
                        <option value="Senior Mekanik">Senior Mekanik</option>
                        <option value="Kepala Bengkel">Kepala Bengkel</option>
                        <option value="Teknisi">Teknisi</option>
                    </select></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Persentase Jasa %</label><input type="number" class="form-control" name="persentase_jasa" id="f-persen" min="0" max="100" value="50"></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">No HP</label><input type="text" class="form-control" name="no_hp" id="f-hp" placeholder="08xxx"></div></div>
            </div>
        </form>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Batal</button><button class="btn btn-warning" onclick="save()"><i class="fas fa-save mr-1"></i> Simpan</button></div>
</div></div></div>

<?php $extraScripts = '
async function loadData(){
    const c=document.getElementById("data-container");
    const q=document.getElementById("search-input").value.toLowerCase();
    const res=await apiRequest("getMekanik",{},"GET");
    if(res.status==="success"&&res.data){
        let filtered=res.data;
        if(q)filtered=filtered.filter(m=>m.nama.toLowerCase().includes(q));
        if(filtered.length>0){
            c.innerHTML=filtered.map((m,i)=>`<tr style="animation:fadeInUp 0.3s ease forwards;opacity:0;animation-delay:${i*0.03}s">
                <td>${i+1}</td>
                <td><span style="color:#f59e0b;font-weight:600;font-size:0.8rem">${m.kode_mekanik}</span></td>
                <td class="font-semibold">${m.nama}</td>
                <td><span class="badge badge-info">${m.jabatan||"Mekanik"}</span></td>
                <td>${m.persentase_jasa||50}%</td>
                <td>${m.no_hp||"-"}</td>
                <td><div class="btn-group btn-group-sm"><button class="btn btn-secondary" onclick="edit(${m.id})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-secondary" onclick="hapus(${m.id})" title="Hapus" style="color:#ef4444"><i class="fas fa-trash"></i></button></div></td>
            </tr>`).join("");
        } else { c.innerHTML=`<tr><td colspan="7" class="text-center py-5" style="color:#64748b"><p>Belum ada data mekanik</p></td></tr>`; }
    }
}
function openForm(){
    ["f-id","f-nama","f-hp"].forEach(id=>document.getElementById(id).value="");
    document.getElementById("f-jab").value="Mekanik";
    document.getElementById("f-persen").value="50";
    document.getElementById("modal-title").innerHTML="<i class=\\"fas fa-plus-circle mr-2\\" style=\\"color:#22c55e\\"></i>Tambah Mekanik";
    $("#formModal").modal("show");
}
async function edit(id){
    const res=await apiRequest("getMekanik",{},"GET");
    if(res.status==="success"&&res.data){
        const m=res.data.find(x=>x.id==id);if(!m)return;
        document.getElementById("f-id").value=m.id;
        document.getElementById("f-nama").value=m.nama;
        document.getElementById("f-jab").value=m.jabatan||"Mekanik";
        document.getElementById("f-persen").value=m.persentase_jasa||50;
        document.getElementById("f-hp").value=m.no_hp||"";
        document.getElementById("modal-title").innerHTML="<i class=\\"fas fa-edit mr-2\\" style=\\"color:#f59e0b\\"></i>Edit Mekanik";
        $("#formModal").modal("show");
    }
}
async function save(){
    const f=document.getElementById("data-form");
    if(!f.checkValidity()){f.reportValidity();return;}
    const d=Object.fromEntries(new FormData(f).entries());
    const r=await apiRequest(d.id?"updateMekanik":"addMekanik",d);
    if(r.status==="success"){showToast(r.message);$("#formModal").modal("hide");loadData();}
    else showToast(r.message||"Gagal","error");
}
async function hapus(id){
    if(!confirm("Yakin hapus mekanik ini?"))return;
    const r=await apiRequest("deleteMekanik",{id});
    if(r.status==="success"){showToast("Dihapus");loadData();}else showToast(r.message||"Gagal","error");
}
loadData();
'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
