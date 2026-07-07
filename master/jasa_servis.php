<?php
$pageTitle = 'Data Jasa Servis';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../config/auth.php';
requireRole(['owner','admin']);
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 animate-fade-in-up">
    <div>
        <h1 class="h4 font-bold" style="color:#f1f5f9"><i class="fas fa-tools mr-2" style="color:#22c55e"></i>Data Jasa Servis</h1>
        <small style="color:#64748b">Kelola daftar jasa servis dan harga</small>
    </div>
    <button class="btn btn-success mt-2 mt-md-0" onclick="openForm()"><i class="fas fa-plus mr-1"></i> Tambah Jasa</button>
</div>

<div class="card mb-4 animate-fade-in-up" style="animation-delay:0.1s;opacity:0">
    <div class="card-body p-3">
        <div class="input-group" style="max-width:400px">
            <div class="input-group-prepend"><span class="input-group-text" style="background:rgba(255,255,255,0.05);border-color:rgba(255,255,255,0.1);border-radius:0.75rem 0 0 0.75rem;color:#64748b"><i class="fas fa-search"></i></span></div>
            <input type="text" class="form-control" id="search-input" placeholder="Cari jasa servis..." oninput="loadData()" style="border-radius:0 0.75rem 0.75rem 0">
        </div>
    </div>
</div>

<div class="card animate-fade-in-up" style="animation-delay:0.2s;opacity:0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>No</th><th>Kode</th><th>Nama Jasa</th><th>Harga</th><th>Mekanik %</th><th>Estimasi</th><th>Aksi</th></tr></thead>
                <tbody id="data-container"><tr><td colspan="7" class="text-center py-5" style="color:#64748b"><i class="fas fa-spinner fa-spin mr-2"></i>Memuat...</td></tr></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="formModal" tabindex="-1" role="dialog"><div class="modal-dialog" role="document"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="modal-title">Tambah Jasa Servis</h5><button type="button" class="close" data-dismiss="modal"><span style="color:#94a3b8">&times;</span></button></div>
    <div class="modal-body">
        <form id="data-form" onsubmit="event.preventDefault();save()">
            <input type="hidden" name="id" id="f-id">
            <div class="form-group"><label class="form-label" style="font-size:0.8rem">Nama Jasa <span class="text-danger">*</span></label><input type="text" class="form-control" name="nama" id="f-nama" required placeholder="Contoh: Ganti Oli"></div>
            <div class="form-group"><label class="form-label" style="font-size:0.8rem">Deskripsi</label><textarea class="form-control" name="deskripsi" id="f-desk" rows="2" placeholder="Deskripsi singkat..."></textarea></div>
            <div class="row">
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Harga (Rp) <span class="text-danger">*</span></label><input type="number" class="form-control" name="harga" id="f-harga" min="0" value="0" required></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Persentase Mekanik %</label><input type="number" class="form-control" name="persentase_mekanik" id="f-persen" min="0" max="100" value="50"></div></div>
                <div class="col-md-4"><div class="form-group"><label class="form-label" style="font-size:0.8rem">Estimasi (menit)</label><input type="number" class="form-control" name="estimasi_menit" id="f-estimasi" min="0" value="30"></div></div>
            </div>
        </form>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times mr-1"></i> Batal</button><button class="btn btn-success" onclick="save()"><i class="fas fa-save mr-1"></i> Simpan</button></div>
</div></div></div>

<?php $extraScripts = '
async function loadData(){
    const c=document.getElementById("data-container");
    const q=document.getElementById("search-input").value;
    const res=await apiRequest("getJasaServis",{search:q},"GET");
    if(res.status==="success"&&res.data&&res.data.length>0){
        c.innerHTML=res.data.map((j,i)=>`<tr style="animation:fadeInUp 0.3s ease forwards;opacity:0;animation-delay:${i*0.03}s">
            <td>${i+1}</td><td><span style="color:#22c55e;font-weight:600;font-size:0.8rem">${j.kode_jasa}</span></td>
            <td><div class="font-semibold">${j.nama}</div>${j.deskripsi?`<small style="color:#64748b">${j.deskripsi}</small>`:""}</td>
            <td class="font-bold">${formatRupiah(j.harga)}</td>
            <td>${j.persentase_mekanik||50}%</td>
            <td>${j.estimasi_menit||30} mnt</td>
            <td><div class="btn-group btn-group-sm"><button class="btn btn-secondary" onclick="edit(${j.id})" title="Edit"><i class="fas fa-edit"></i></button><button class="btn btn-secondary" onclick="hapus(${j.id})" title="Hapus" style="color:#ef4444"><i class="fas fa-trash"></i></button></div></td>
        </tr>`).join("");
    } else { c.innerHTML=`<tr><td colspan="7" class="text-center py-5" style="color:#64748b"><i class="fas fa-tools mb-3 d-block" style="font-size:2.5rem;opacity:0.3"></i><p>Belum ada jasa servis</p></td></tr>`; }
}
function openForm(){
    document.getElementById("f-id").value="";
    document.getElementById("f-nama").value="";
    document.getElementById("f-desk").value="";
    document.getElementById("f-harga").value="0";
    document.getElementById("f-persen").value="50";
    document.getElementById("f-estimasi").value="30";
    document.getElementById("modal-title").innerHTML="<i class=\\"fas fa-plus-circle mr-2\\" style=\\"color:#22c55e\\"></i>Tambah Jasa Servis";
    $("#formModal").modal("show");
}
async function edit(id){
    const res=await apiRequest("getJasaServis",{}, "GET");
    if(res.status==="success"&&res.data){
        const j=res.data.find(x=>x.id==id); if(!j)return;
        document.getElementById("f-id").value=j.id;
        document.getElementById("f-nama").value=j.nama;
        document.getElementById("f-desk").value=j.deskripsi||"";
        document.getElementById("f-harga").value=j.harga;
        document.getElementById("f-persen").value=j.persentase_mekanik||50;
        document.getElementById("f-estimasi").value=j.estimasi_menit||30;
        document.getElementById("modal-title").innerHTML="<i class=\\"fas fa-edit mr-2\\" style=\\"color:#f59e0b\\"></i>Edit Jasa Servis";
        $("#formModal").modal("show");
    }
}
async function save(){
    const f=document.getElementById("data-form");
    if(!f.checkValidity()){f.reportValidity();return;}
    const d=Object.fromEntries(new FormData(f).entries());
    const r=await apiRequest(d.id?"updateJasaServis":"addJasaServis",d);
    if(r.status==="success"){showToast(r.message);$("#formModal").modal("hide");loadData();}
    else showToast(r.message||"Gagal","error");
}
async function hapus(id){
    if(!confirm("Yakin hapus jasa ini?"))return;
    const r=await apiRequest("deleteJasaServis",{id});
    if(r.status==="success"){showToast("Dihapus");loadData();}else showToast(r.message||"Gagal","error");
}
loadData();
'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
