<?php
// header.php – shared navbar (landing + public pages)
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle).' – Floravera' : 'Floravera – Davao\'s Flower & Craft Marketplace' ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,600;0,700;1,600&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg fv-navbar fixed-top">
  <div class="container-fluid px-4">

    <a class="navbar-brand fv-logo" href="index.php">✿ <span>Flora</span>vera</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMain">
      <ul class="navbar-nav me-auto ms-4 gap-2">
        <?php
          // Browse goes to dashboard if logged in, otherwise to login with redirect
          if(isset($_SESSION['user_id'])){
              $browseHref = $_SESSION['role']==='admin' ? 'admin/dashboard.php' : 'customer/dashboard.php';
          } else {
              $browseHref = 'login.php?redirect=customer/dashboard.php';
          }
        ?>
        <li class="nav-item"><a class="nav-link" href="<?= $browseHref ?>">Browse</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#vendors">Vendors</a></li>
        <li class="nav-item"><a class="nav-link" href="index.php#about">About</a></li>
      </ul>

      <div class="d-flex gap-2 align-items-center">
        <?php if(isset($_SESSION['user_id'])): ?>
          <span class="text-muted small">Hi, <?= htmlspecialchars(explode(' ',$_SESSION['fullname'])[0]) ?></span>
          <a href="<?= $_SESSION['role']==='admin' ? 'admin/dashboard.php' : ($_SESSION['role']==='seller' ? 'customer/dashboard.php' : 'customer/dashboard.php') ?>"
             class="btn btn-fv-fill btn-sm">Dashboard</a>
          <a href="logout.php" class="btn btn-fv-outline btn-sm">Logout</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-fv-outline btn-sm">Login</a>
          <a href="register.php" class="btn btn-fv-fill btn-sm">Register</a>
        <?php endif; ?>
      </div>
    </div>

  </div>
</nav>
