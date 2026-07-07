<?php
require_once __DIR__ . '/config/auth.php';
doLogout();
header('Location: ' . BASE_URL . '/login.php');
exit;
