<?php
session_start();

// If confirmed, destroy session and redirect
if(isset($_GET['confirm'])){
    session_destroy();
    header("Location: index.php?loggedout=1");
    exit();
}

// If cancelled, go back to the right dashboard
if(isset($_GET['cancel'])){
    if(!isset($_SESSION['user_id'])){
        header("Location: index.php"); exit();
    }
    switch($_SESSION['role']){
        case 'admin':  header("Location: admin/dashboard.php"); break;
        case 'seller': header("Location: customer/dashboard.php"); break;
        default:       header("Location: customer/dashboard.php"); break;
    }
    exit();
}

// Must be logged in to see this page
if(!isset($_SESSION['user_id'])){
    header("Location: index.php"); exit();
}

$name = htmlspecialchars(explode(' ',$_SESSION['fullname'])[0]);
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Logout – Floravera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="style.css" rel="stylesheet">
<style>
.logout-wrap{
  min-height:100vh;
  display:flex;align-items:center;justify-content:center;
  background:linear-gradient(135deg,#fce4ef 0%,#fdf8f5 60%,#f3e5f5 100%);
  padding:20px;
}
.logout-card{
  background:#fff;
  border-radius:24px;
  padding:44px 40px;
  text-align:center;
  max-width:420px;
  width:100%;
  box-shadow:0 24px 60px rgba(214,51,132,.1);
}
.logout-icon{
  width:80px;height:80px;
  border-radius:50%;
  background:var(--fv-pink-light);
  display:flex;align-items:center;justify-content:center;
  font-size:36px;
  margin:0 auto 20px;
}
.logout-title{
  font-family:'Cormorant Garamond',serif;
  font-size:26px;font-weight:700;
  color:var(--fv-dark);margin-bottom:8px;
}
.btn-confirm{
  width:100%;padding:12px;
  background:linear-gradient(135deg,var(--fv-pink),var(--fv-pink2));
  color:#fff;border:none;border-radius:12px;
  font-size:15px;font-weight:600;cursor:pointer;
  font-family:'DM Sans',sans-serif;
  transition:all .2s;text-decoration:none;
  display:inline-block;margin-bottom:10px;
}
.btn-confirm:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(214,51,132,.3);color:#fff;}
.btn-cancel{
  width:100%;padding:12px;
  background:transparent;
  color:var(--fv-muted);
  border:1.5px solid var(--fv-border);
  border-radius:12px;font-size:15px;font-weight:500;
  cursor:pointer;font-family:'DM Sans',sans-serif;
  transition:all .2s;text-decoration:none;
  display:inline-block;
}
.btn-cancel:hover{border-color:var(--fv-pink);color:var(--fv-pink);}
</style>
</head>
<body>

<!-- Minimal nav just with logo -->
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center">
  <a class="fv-logo" href="index.php">✿ <span>Flora</span>vera</a>
</nav>

<div class="logout-wrap" style="padding-top:86px">
  <div class="logout-card">
    <div class="logout-icon">👋</div>
    <div class="logout-title">See you soon, <?= $name ?>!</div>
    <p class="text-muted mb-4" style="font-size:14px;line-height:1.65">
      Are you sure you want to log out of your
      <strong>Floravera <?= ucfirst($role) ?></strong> account?<br>
      Your cart and wishlist will be preserved.
    </p>

    <a href="logout.php?confirm=1" class="btn-confirm" onclick="sessionStorage.removeItem('floraMessages')">Yes, log me out</a>
    <a href="logout.php?cancel=1" class="btn-cancel">← Stay logged in</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
