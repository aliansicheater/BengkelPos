        </div><!-- /.container-fluid -->
    </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<!-- Footer -->
<footer class="main-footer text-center no-print" style="background:#0f172a;border-top:1px solid rgba(255,255,255,0.05)">
    <div class="d-none d-sm-inline-block">
        <small style="color:#475569"><?= $settings['nama_bengkel'] ?> &copy; <?= date('Y') ?>. Bengkel Pro V1</small>
    </div>
</footer>
</div><!-- /.wrapper -->

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE 3 -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- JsBarcode -->
<script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.6/dist/JsBarcode.all.min.js"></script>
<!-- html5-qrcode -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<!-- Custom App JS -->
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>

<script>
// Theme Toggle
function toggleTheme() {
    const current = document.documentElement.getAttribute('data-theme') || 'dark';
    const next = current === 'dark' ? 'light' : 'dark';
    document.cookie = `theme=${next};path=/;max-age=31536000`;
    if (next === 'dark') {
        document.body.classList.add('dark-mode');
        document.getElementById('theme-icon-dark').style.display = '';
        document.getElementById('theme-icon-light').style.display = 'none';
    } else {
        document.body.classList.remove('dark-mode');
        document.getElementById('theme-icon-dark').style.display = 'none';
        document.getElementById('theme-icon-light').style.display = '';
    }
    document.documentElement.setAttribute('data-theme', next);
}
</script>

<?php if (isset($extraScripts)): ?>
<script><?= $extraScripts ?></script>
<?php endif; ?>
</body>
</html>
