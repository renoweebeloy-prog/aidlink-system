<?php

session_start();

require_once __DIR__ . '/Auth.php';

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

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    font-family:Arial,sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    overflow:hidden;
    background:
    radial-gradient(circle at top left,#00b7a8 0%,transparent 30%),
    radial-gradient(circle at bottom right,#001d4a 0%,transparent 35%),
    linear-gradient(135deg,#03152c,#05284d,#021024);
    position:relative;
}

/* floating circles */

body::before,
body::after{
    content:'';
    position:absolute;
    border-radius:50%;
    background:rgba(0,255,220,.05);
    animation:float 10s infinite ease-in-out;
}

body::before{
    width:300px;
    height:300px;
    left:-100px;
    top:120px;
}

body::after{
    width:400px;
    height:400px;
    right:-150px;
    bottom:-100px;
}

@keyframes float{
    0%{
        transform:translateY(0px);
    }
    50%{
        transform:translateY(-20px);
    }
    100%{
        transform:translateY(0px);
    }
}

.logout-card{
    width:430px;
    max-width:92%;
    background:rgba(5,16,45,.88);
    border-radius:28px;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    box-shadow:0 20px 60px rgba(0,0,0,.45);
    border:1px solid rgba(255,255,255,.08);
    backdrop-filter:blur(10px);
}

.logout-top{
    background:linear-gradient(135deg,#008f86,#00b8aa);
    padding:45px 35px;
    text-align:center;
}

.logout-top h1{
    color:white;
    font-size:52px;
    font-weight:800;
    margin-bottom:10px;
}

.logout-top p{
    color:#dff;
    line-height:1.6;
}

.logout-bottom{
    padding:35px;
    text-align:center;
}

.logout-bottom h2{
    color:white;
    margin-bottom:14px;
    font-size:30px;
}

.logout-bottom p{
    color:#b8c7e0;
    margin-bottom:30px;
    line-height:1.6;
}

.actions{
    display:flex;
    gap:15px;
    justify-content:center;
    flex-wrap:wrap;
}

.btn{
    border:none;
    padding:14px 28px;
    border-radius:14px;
    cursor:pointer;
    font-size:15px;
    font-weight:700;
    transition:.3s;
    text-decoration:none;
}

.logout-btn{
    background:#13d3c3;
    color:white;
}

.logout-btn:hover{
    transform:translateY(-2px);
    opacity:.9;
}

.cancel-btn{
    background:rgba(255,255,255,.08);
    color:white;
}

.cancel-btn:hover{
    background:rgba(255,255,255,.15);
}

.dev-text{
    margin-top:25px;
    color:#d7e6ff;
    font-size:14px;
    opacity:.9;
}

</style>

</head>

<body>

<div class="logout-card">

    <div class="logout-top">

        <h1>AidLink</h1>

        <p>
            Securely sign out from your AidLink account.
        </p>

    </div>

    <div class="logout-bottom">

        <h2>Logout</h2>

        <p>
            Are you sure you want to log out?
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

        <div class="dev-text">
     
        </div>

    </div>

</div>

</body>
</html>
