<?php
session_start();
require_once __DIR__ . '/../app/helpers.php';

redirect('login.php?mode=register');
