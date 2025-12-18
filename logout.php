<?php
ob_start();
session_start();

// Hapus session
$_SESSION['username'] = null;
$_SESSION['firstname'] = null;
$_SESSION['lastname'] = null;
$_SESSION['user_role'] = null;

// redirect ke halaman index CMS
require_once __DIR__ . "/includes/config.php"; // pastikan BASE_URL tersedia
header("Location: " . BASE_URL . "/index.php");
exit();
?>
