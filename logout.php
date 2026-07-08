<?php
session_start();
session_destroy();
header('Location: /Bengkel POS/index.php');
exit;
