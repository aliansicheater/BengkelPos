<?php
session_start();
session_destroy();
header('Location: /BengkelPOS/index.php');
exit;
