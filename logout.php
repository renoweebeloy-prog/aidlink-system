<?php
session_start();

require_once __DIR__ . '/Auth.php';

Auth::logout();

header('Location: login.php');
exit;
?>
