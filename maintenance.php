<?php
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Maintenance</title>

<style>

body{
    margin:0;
    padding:0;
    height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Arial,sans-serif;
    background:linear-gradient(135deg,#071b34,#0d2f5c,#0f4c81);
    color:white;
}

.box{
    width:500px;
    max-width:90%;
    padding:40px;
    border-radius:25px;
    text-align:center;
    background:rgba(255,255,255,.08);
    backdrop-filter:blur(15px);
    box-shadow:0 20px 50px rgba(0,0,0,.45);
}

h1{
    font-size:38px;
    margin-bottom:15px;
}

p{
    color:#dbe8ff;
    font-size:18px;
    line-height:1.7;
}

.status{
    display:inline-block;
    padding:10px 20px;
    border-radius:50px;
    background:#ff3b3b;
    margin-bottom:20px;
    font-weight:bold;
}

</style>
</head>
<body>

<div class="box">

    <div class="status">
        SYSTEM OFFLINE
    </div>

    <h1>Maintenance Mode</h1>

    <p>
        The system is currently not available.<br>
        Please come back later.
    </p>

</div>

</body>
</html>
