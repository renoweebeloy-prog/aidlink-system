<?php
session_start();

require_once __DIR__ . '/app/Auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_logout'])) {

    Auth::logout();

    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Logout - AidLink</title>

<style>

body{
    margin:0;
    padding:0;
    font-family:Arial,sans-serif;
    background:linear-gradient(135deg,#071b34,#0d2f5c,#0f4c81);
    height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
}

.logout-box{
    width:400px;
    max-width:90%;
    padding:35px;
    border-radius:24px;
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(12px);
    border:1px solid rgba(255,255,255,.1);
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.45);
}

.logout-box h1{
    margin-bottom:10px;
    font-size:32px;
}

.logout-box p{
    color:#dbe8ff;
    margin-bottom:28px;
}

.actions{
    display:flex;
    gap:14px;
    justify-content:center;
}

.btn{
    padding:12px 24px;
    border:none;
    border-radius:14px;
    cursor:pointer;
    font-size:15px;
    font-weight:bold;
    transition:.3s;
    text-decoration:none;
}

.logout-btn{
    background:#ff3b3b;
    color:white;
}

.logout-btn:hover{
    background:#d91f1f;
    transform:translateY(-2px);
}

.cancel-btn{
    background:rgba(255,255,255,.12);
    color:white;
}

.cancel-btn:hover{
    background:rgba(255,255,255,.22);
    transform:translateY(-2px);
}

</style>
</head>

<body>

<div class="logout-box">

    <h1>Logout</h1>

    <p>Are you sure you want to log out?</p>

    <div class="actions">

        <form method="POST">
            <button type="submit" name="confirm_logout" class="btn logout-btn">
                Yes, Logout
            </button>
        </form>

        <a href="dashboard.php" class="btn cancel-btn">
            Cancel
        </a>

    </div>

</div>

</body>
</html>
