<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$uid=$_SESSION['user_id'];

$items=$conn->query("SELECT p.*,v.shop_name FROM wishlist w JOIN products p ON p.id=w.product_id JOIN vendors v ON v.id=p.vendor_id WHERE w.customer_id=$uid");
$bgMap=['flowers'=>'#fce4ef','bouquets'=>'#fffde7','handicrafts'=>'#e8f5e9','giftsets'=>'#fff3e0'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Wishlist – Floravera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <a href="cart.php" class="btn-fv-fill btn-sm" style="text-decoration:none">🛒 Cart</a>
</nav>
<div class="fv-dash-wrap">
  <?php include 'partials/sidebar.php'; ?>
  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">❤ My Wishlist</div></div>
    <div class="fv-dash-content">
      <?php if($items->num_rows===0): ?>
        <div class="text-center py-5 text-muted">Your wishlist is empty. Click ♥ on any product to save it!</div>
      <?php else: ?>
      <div class="row g-3">
        <?php while($p=$items->fetch_assoc()):
          $bg=$bgMap[$p['category']]??'#f3f4f6';
        ?>
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
          <div class="fv-dp h-100">
            <div class="fv-dp-img" style="background:<?= $bg ?>">
              <?= $p['emoji'] ?>
              <a href="wishlist_toggle.php?id=<?= $p['id'] ?>" class="wl-heart on">♥</a>
            </div>
            <div class="fv-dp-body">
              <div class="fv-dp-name"><?= htmlspecialchars($p['name']) ?></div>
              <div class="fv-dp-vendor"><?= htmlspecialchars($p['shop_name']) ?></div>
              <div class="fv-dp-price">₱<?= number_format($p['price'],2) ?></div>
              <a href="cart_add.php?id=<?= $p['id'] ?>" class="btn-add-cart">+ Add to cart</a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
