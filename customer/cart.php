<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';

// Initialize cart
if(!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$uid = $_SESSION['user_id'];
$cartItems = [];
$subtotal  = 0;

foreach($_SESSION['cart'] as $pid => $item){
    $row = $conn->query("SELECT p.*,v.shop_name FROM products p JOIN vendors v ON v.id=p.vendor_id WHERE p.id=$pid")->fetch_assoc();
    if($row){
        $row['qty'] = $item['qty'];
        $row['line_total'] = $row['price'] * $row['qty'];
        $subtotal += $row['line_total'];
        $cartItems[] = $row;
    }
}

$bgMap = ['flowers'=>'#fce4ef','bouquets'=>'#fffde7','handicrafts'=>'#e8f5e9','giftsets'=>'#fff3e0'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cart – Floravera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <a href="dashboard.php" class="btn-fv-outline btn-sm">← Continue Shopping</a>
</nav>

<div class="fv-dash-wrap">
  <?php include 'partials/sidebar.php'; ?>
  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">🛒 Your Cart</div></div>
    <div class="fv-dash-content">

      <?php if(empty($cartItems)): ?>
        <div class="text-center py-5">
          <div style="font-size:60px">🌸</div>
          <h5 class="mt-3">Your cart is empty</h5>
          <a href="dashboard.php" class="btn-fv-fill mt-3 d-inline-block" style="text-decoration:none">Browse Products</a>
        </div>
      <?php else: ?>

      <div class="row g-4">
        <div class="col-lg-8">
          <div class="fv-card">
            <div class="fw-600 mb-3">Cart Items (<?= count($cartItems) ?>)</div>
            <table class="table checkout-table align-middle">
              <thead>
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Qty</th>
                  <th>Total</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($cartItems as $item):
                  $bg = $bgMap[$item['category']] ?? '#f3f4f6';
                ?>
                <tr>
                  <td>
                    <div class="d-flex align-items-center gap-2">
                      <div class="ci-img" style="background:<?= $bg ?>;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                        <?php if(!empty($item['image']) && file_exists('../'.$item['image'])): ?>
                          <img src="../<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width:100%;height:100%;object-fit:cover;">
                        <?php else: ?>
                          <?= $item['emoji'] ?>
                        <?php endif; ?>
                      </div>
                      <div>
                        <div class="ci-name"><?= htmlspecialchars($item['name']) ?></div>
                        <div class="text-muted small"><?= htmlspecialchars($item['shop_name']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="ci-price">₱<?= number_format($item['price'],2) ?></td>
                  <td>
                    <div class="d-flex align-items-center gap-1">
                      <a href="cart_update.php?id=<?= $item['id'] ?>&action=dec" class="qty-btn">−</a>
                      <span class="fw-600 px-2"><?= $item['qty'] ?></span>
                      <a href="cart_update.php?id=<?= $item['id'] ?>&action=inc" class="qty-btn">+</a>
                    </div>
                  </td>
                  <td class="fw-600">₱<?= number_format($item['line_total'],2) ?></td>
                  <td><a href="cart_remove.php?id=<?= $item['id'] ?>" class="text-danger small">✕ Remove</a></td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="fv-card">
            <div class="fw-600 mb-3">Order Summary</div>
            <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Subtotal</span><span>₱<?= number_format($subtotal,2) ?></span></div>
            <div class="d-flex justify-content-between mb-2 small"><span class="text-muted">Delivery</span><span class="text-success">Free</span></div>
            <hr>
            <div class="d-flex justify-content-between fw-600 mb-4"><span>Total</span><span style="color:var(--fv-pink);font-size:18px">₱<?= number_format($subtotal,2) ?></span></div>
            <a href="checkout.php" class="btn-fv-fill d-block text-center" style="text-decoration:none;padding:12px;border-radius:12px;font-size:15px;font-weight:600">
              Proceed to Checkout →
            </a>
            <a href="dashboard.php" class="d-block text-center mt-2 small" style="color:var(--fv-muted)">← Continue shopping</a>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
