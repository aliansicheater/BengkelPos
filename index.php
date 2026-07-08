<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /Bengkel POS/dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

// Load settings
$q_setting = mysqli_query($conn, "SELECT * FROM setting WHERE id=1");
$setting = mysqli_fetch_assoc($q_setting);
$app_name = $setting['nama_aplikasi'] ?? 'BengkelPOS';
$app_logo = $setting['logo'] ?? null;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Password salah!';
        }
    } else {
        $error = 'Username tidak ditemukan!';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($app_name) ?> - Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { font-family: 'Inter', sans-serif; }
        body {
            background: #F8FAFC;
            min-height: 100vh;
            position: relative;
        }

        /* SVG Doodle Background — mechanic theme, black & white */
        .doodle-bg {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: 0.6;
        }

        .doodle-svg {
            width: 100%;
            height: 100%;
            background: #F8FAFC;
            background-image:
                /* Wrench icons scattered */
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='60' viewBox='0 0 24 24' fill='none' stroke='%23CBD5E1' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z'/%3E%3C/svg%3E"),
                /* Gear icons */
                url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 24 24' fill='none' stroke='%23CBD5E1' stroke-width='1' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='12' cy='12' r='3'/%3E%3Cpath d='M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42'/%3E%3C/svg%3E"),
                /* Tire/circle outlines */
                radial-gradient(circle at 15% 20%, #E2E8F0 0%, transparent 2px),
                radial-gradient(circle at 85% 15%, #E2E8F0 0%, transparent 3px),
                radial-gradient(circle at 50% 85%, #E2E8F0 0%, transparent 2.5px),
                /* Diagonal line patterns */
                repeating-linear-gradient(-45deg, transparent, transparent 8px, #F1F5F9 8px, #F1F5F9 9px),
                repeating-linear-gradient(45deg, transparent, transparent 20px, #F1F5F9 20px, #F1F5F9 21px);
            background-size:
                80px 80px,
                100px 100px,
                40px 40px,
                40px 40px,
                30px 30px,
                50px 50px;
            background-position:
                5% 10%, 90% 5%, 50% 90%, 10% 80%, 80% 60%, 20% 40%;
            background-repeat: repeat;
        }

        /* Dark overlay */
        .doodle-overlay {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background: radial-gradient(ellipse at 30% 50%, transparent 0%, #F8FAFC 70%);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(24px);
            animation: slideUp 0.6s ease-out;
            border: 1px solid rgba(0,0,0,0.06);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 30px 60px rgba(0,0,0,0.12);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        .input-field {
            transition: all 0.3s ease;
            border: 1.5px solid #E2E8F0;
        }

        .input-field:focus {
            border-color: #1E293B;
            box-shadow: 0 0 0 4px rgba(30, 41, 59, 0.1);
            transform: translateY(-1px);
        }

        .input-field:hover {
            border-color: #94A3B8;
        }

        .btn-login {
            transition: all 0.3s ease;
            background: #1E293B;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(30, 41, 59, 0.3);
            background: #0F172A;
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .float-anim {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        /* Input icon animation */
        .input-group {
            position: relative;
        }

        .input-group i {
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .input-group:focus-within i {
            transform: scale(1.1);
            color: #1E293B;
        }

        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            body { background: #0F172A; }
            .doodle-svg { background: #0F172A; }
            .doodle-overlay { background: radial-gradient(ellipse at 30% 50%, transparent 0%, #0F172A 70%); }
            .login-card { background: rgba(30, 41, 59, 0.92); border-color: rgba(255,255,255,0.06); }
            .login-card h1 { color: #F1F5F9; }
            .login-card p { color: #94A3B8; }
            .login-card label { color: #CBD5E1; }
            .input-field { background: #1E293B; border-color: #334155; color: #F1F5F9; }
            .input-field:focus { border-color: #94A3B8; box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.1); }
            .input-field:hover { border-color: #64748B; }
        }
    </style>
</head>
<body class="flex items-center justify-center p-4 relative overflow-hidden">

    <!-- Doodle Background -->
    <div class="doodle-bg">
        <div class="doodle-svg"></div>
    </div>
    <div class="doodle-overlay"></div>

    <!-- Login Card -->
    <div class="login-card w-full max-w-md rounded-3xl shadow-xl p-8 md:p-10 relative z-10">
        <!-- Logo -->
        <div class="text-center mb-8">
            <?php if ($app_logo): ?>
                <img src="/Bengkel POS/uploads/<?= htmlspecialchars($app_logo) ?>" alt="Logo" class="h-20 w-20 mx-auto object-contain mb-4 float-anim">
            <?php else: ?>
                <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-slate-700 to-slate-900 rounded-2xl shadow-lg mb-4 float-anim">
                    <i class="fas fa-wrench text-white text-3xl"></i>
                </div>
            <?php endif; ?>
            <h1 class="text-3xl font-extrabold text-gray-900">
                <?php
                $parts = preg_split('/(?=[A-Z][a-z]*$)/', $app_name, 2);
                if (count($parts) > 1 && trim($parts[1])): ?>
                    <?= htmlspecialchars($parts[0]) ?><span class="text-slate-600"><?= htmlspecialchars($parts[1]) ?></span>
                <?php else: ?>
                    <?= htmlspecialchars($app_name) ?>
                <?php endif; ?>
            </h1>
            <p class="text-gray-500 mt-1 text-sm">Sistem Manajemen Bengkel Profesional</p>
        </div>

        <!-- Error Alert -->
        <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 text-sm flex items-center gap-3 animate-pulse">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= $error ?></span>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" class="space-y-5">
            <div class="input-group">
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="username">
                    <i class="fas fa-user text-slate-500 mr-1"></i> Username
                </label>
                <input type="text" id="username" name="username" required
                    class="input-field w-full px-4 py-3 rounded-xl text-gray-700 placeholder-gray-400 focus:outline-none"
                    placeholder="Masukkan username">
            </div>
            <div class="input-group">
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="password">
                    <i class="fas fa-lock text-slate-500 mr-1"></i> Password
                </label>
                <input type="password" id="password" name="password" required
                    class="input-field w-full px-4 py-3 rounded-xl text-gray-700 placeholder-gray-400 focus:outline-none"
                    placeholder="Masukkan password">
            </div>
            <button type="submit"
                class="btn-login w-full text-white font-bold py-3 px-6 rounded-xl text-lg flex items-center justify-center gap-2">
                <i class="fas fa-right-to-bracket"></i> Masuk
            </button>
        </form>

        <div class="mt-6 text-center text-xs text-gray-400">
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($app_name) ?>. All rights reserved.</p>
        </div>
    </div>

</body>
</html>
