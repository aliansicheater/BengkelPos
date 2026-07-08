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
            background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #312E81 100%);
            min-height: 100vh;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            animation: slideUp 0.6s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .input-field {
            transition: all 0.3s ease;
        }
        .input-field:focus {
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        }
        .btn-login {
            transition: all 0.3s ease;
            background: linear-gradient(135deg, #4F46E5, #7C3AED);
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(79, 70, 229, 0.4);
        }
        .btn-login:active {
            transform: translateY(0);
        }
        .bg-pattern {
            position: fixed;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(79, 70, 229, 0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .bg-pattern-2 {
            position: fixed;
            bottom: -50%;
            left: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(245, 158, 11, 0.1) 0%, transparent 70%);
            pointer-events: none;
        }
        .float-anim {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="flex items-center justify-center p-4 relative overflow-hidden">
    <div class="bg-pattern"></div>
    <div class="bg-pattern-2"></div>

    <div class="login-card w-full max-w-md rounded-3xl shadow-2xl p-8 md:p-10 relative z-10">
        <!-- Logo -->
            <div class="text-center mb-8">
                <?php if ($app_logo): ?>
                    <img src="/Bengkel POS/uploads/<?= htmlspecialchars($app_logo) ?>" alt="Logo" class="h-20 w-20 mx-auto object-contain mb-4 float-anim">
                <?php else: ?>
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-2xl shadow-lg mb-4 float-anim">
                        <i class="fas fa-wrench text-white text-3xl"></i>
                    </div>
                <?php endif; ?>
                <h1 class="text-3xl font-extrabold text-gray-900">
                    <?php
                    $parts = preg_split('/(?=[A-Z][a-z]*$)/', $app_name, 2);
                    if (count($parts) > 1 && trim($parts[1])): ?>
                        <?= htmlspecialchars($parts[0]) ?><span class="text-indigo-600"><?= htmlspecialchars($parts[1]) ?></span>
                    <?php else: ?>
                        <?= htmlspecialchars($app_name) ?>
                    <?php endif; ?>
                </h1>
                <p class="text-gray-500 mt-1 text-sm">Sistem Manajemen Bengkel Profesional</p>
        </div>

        <!-- Error Alert -->
        <?php if ($error): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 text-sm flex items-center gap-3">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= $error ?></span>
        </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" action="" class="space-y-5">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="username">
                    <i class="fas fa-user text-indigo-500 mr-1"></i> Username
                </label>
                <input type="text" id="username" name="username" required
                    class="input-field w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-700 placeholder-gray-400 focus:outline-none focus:border-indigo-500"
                    placeholder="Masukkan username">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2" for="password">
                    <i class="fas fa-lock text-indigo-500 mr-1"></i> Password
                </label>
                <input type="password" id="password" name="password" required
                    class="input-field w-full px-4 py-3 border border-gray-300 rounded-xl text-gray-700 placeholder-gray-400 focus:outline-none focus:border-indigo-500"
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
