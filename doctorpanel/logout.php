<?php
require_once __DIR__ . '/inc/auth.php';
logout_doctor();
header('Location: login.php');
exit;
?>
