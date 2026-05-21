<?php

session_start();

require_once __DIR__ . '/../app/Auth.php';

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
    justify-content:center;
    align-items:center;
    color:white;
}

.logout-box{
    width:420px;
    max-width:90%;
    padding:35px;
    border-radius:26px;
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(14px);
    border:1px solid rgba(255,255,255,.1);
    text-align:center;
    box-shadow:0 20px 50px rgba(0,0,0,.45);
}

.logout-icon{
    width:85px;
    height:85px;
    margin:0 auto 20px;
    border-radius:50%;
    background:rgba(255,255,255,.12);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:40px;
}

.logout-box h1{
    margin-bottom:10px;
    font-size:34px;
}

.logout-box p{
    color:#dbe8ff;
    line-height:1.7;
    margin-bottom:28px;
}

.actions{
    display:flex;
    gap:15px;
    justify-content:center;
    flex-wrap:wrap;
}

.btn{
    padding:13px 24px;
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

    <div class="logout-icon">
        ↳
    </div>

    <h1>Logout</h1>

    <p>
        Are you sure you want to log out from AidLink?
    </p>

    <div class="actions">

        <form method="POST">

            <button
                type="submit"
                name="confirm_logout"
                class="btn logout-btn"
            >
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
