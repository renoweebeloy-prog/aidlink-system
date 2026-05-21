<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Maintenance - AidLink</title>

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

.maintenance-card{
    width:500px;
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

.top-section{
    background:linear-gradient(135deg,#008f86,#00b8aa);
    padding:45px 35px;
    text-align:center;
}

.top-section h1{
    color:white;
    font-size:52px;
    font-weight:800;
    margin-bottom:10px;
}

.top-section p{
    color:#dff;
    line-height:1.6;
}

.bottom-section{
    padding:40px;
    text-align:center;
}

.icon{
    font-size:75px;
    margin-bottom:18px;
}

.bottom-section h2{
    color:white;
    margin-bottom:15px;
    font-size:32px;
}

.bottom-section p{
    color:#c4d3eb;
    line-height:1.8;
    margin-bottom:25px;
}

.status{
    display:inline-block;
    padding:12px 24px;
    border-radius:14px;
    background:rgba(0,255,220,.1);
    color:#13d3c3;
    font-weight:bold;
    border:1px solid rgba(0,255,220,.2);
}

.dev-text{
    margin-top:28px;
    color:#d7e6ff;
    font-size:14px;
    opacity:.9;
}

</style>

</head>

<body>

<div class="maintenance-card">

    <div class="top-section">

        <h1>AidLink</h1>

        <p>
            Community Aid Coordination Platform
        </p>

    </div>

    <div class="bottom-section">

        <div class="icon">🛠️</div>

        <h2>System Maintenance</h2>

        <p>
            The AidLink system is currently unavailable due to maintenance.
            <br><br>
            Please come back later.
        </p>

        <div class="status">
            System Temporarily Offline
        </div>

        <div class="dev-text">
            Developed @ Mark Bryan Aguimod
        </div>

    </div>

</div>

</body>
</html>
