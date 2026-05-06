<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';

$uid = $_SESSION['user_id'];
if(empty($_SESSION['cart'])){header("Location: cart.php");exit();}

// Build cart items
$cartItems = [];
$subtotal  = 0;
foreach($_SESSION['cart'] as $pid=>$item){
    $row = $conn->query("SELECT p.*,v.shop_name FROM products p JOIN vendors v ON v.id=p.vendor_id WHERE p.id=$pid")->fetch_assoc();
    if($row){
        $row['qty']=$item['qty'];
        $row['line_total']=$row['price']*$row['qty'];
        $subtotal+=$row['line_total'];
        $cartItems[]=$row;
    }
}

$success='';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    // Insert order
    $stmt=$conn->prepare("INSERT INTO orders (customer_id,total,status) VALUES (?,?,'pending')");
    $stmt->bind_param("id",$uid,$subtotal);
    if($stmt->execute()){
        $orderId=$conn->insert_id;
        foreach($cartItems as $ci){
            $s=$conn->prepare("INSERT INTO order_items (order_id,product_id,qty,price) VALUES (?,?,?,?)");
            $s->bind_param("iiid",$orderId,$ci['id'],$ci['qty'],$ci['price']);
            $s->execute();
        }
        $_SESSION['cart']=[];
        header("Location: orders.php?placed=1");
        exit();
    } else {
        $error = "Something went wrong. Please try again.";
    }
}

$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$bgMap=['flowers'=>'#fce4ef','bouquets'=>'#fffde7','handicrafts'=>'#e8f5e9','giftsets'=>'#fff3e0'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout – Floravera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <a href="cart.php" class="btn-fv-outline btn-sm">← Back to Cart</a>
</nav>

<div class="fv-dash-wrap">
  <?php include 'partials/sidebar.php'; ?>
  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">Checkout</div></div>
    <div class="fv-dash-content">

      <?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <form method="POST">
      <div class="row g-4">

        <!-- Delivery Info -->
        <div class="col-lg-7">
          <div class="fv-card mb-4">
            <div class="fw-600 mb-3">📋 Delivery Information</div>
            <div class="row g-3">
              <div class="col-12">
                <label class="fv-label">Full Name</label>
                <input type="text" name="fullname" class="fv-input" value="<?= htmlspecialchars($user['fullname']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="fv-label">Email</label>
                <input type="email" name="email" class="fv-input" value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="fv-label">Phone Number</label>
                <input type="text" name="phone" class="fv-input" placeholder="09xx-xxx-xxxx" required>
              </div>
              <div class="col-12">
                <label class="fv-label">Delivery Address</label>
                <input type="text" name="address" class="fv-input" placeholder="Street, Barangay" required>
              </div>
              <div class="col-md-6">
                <label class="fv-label">City</label>
                <input type="text" name="city" class="fv-input" value="Davao City">
              </div>
              <div class="col-md-6">
                <label class="fv-label">Zip Code</label>
                <input type="text" name="zip" class="fv-input" placeholder="8000">
              </div>
              <div class="col-12">
                <label class="fv-label">Delivery Notes (optional)</label>
                <textarea name="notes" class="fv-input" rows="2" placeholder="e.g. Leave at gate, call on arrival..."></textarea>
              </div>
            </div>
          </div>

          <div class="fv-card">
            <div class="fw-600 mb-3">💳 Payment Method</div>
            <div class="d-flex flex-column gap-2">
              <label class="d-flex align-items-center gap-3 p-3" style="border:1px solid var(--fv-border);border-radius:12px;cursor:pointer">
                <input type="radio" name="payment" value="cod" checked> Cash on Delivery
              </label>
              <label class="d-flex align-items-center gap-3 p-3" style="border:1px solid var(--fv-border);border-radius:12px;cursor:pointer">
                <input type="radio" name="payment" value="gcash"> GCash
              </label>
              <label class="d-flex align-items-center gap-3 p-3" style="border:1px solid var(--fv-border);border-radius:12px;cursor:pointer">
                <input type="radio" name="payment" value="bank"> Bank Transfer
              </label>
            </div>
          </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-5">
          <div class="fv-card">
            <div class="fw-600 mb-3">🛍 Order Summary</div>
            <?php foreach($cartItems as $ci):
              $bg=$bgMap[$ci['category']]??'#f3f4f6';
            ?>
            <div class="d-flex align-items-center gap-2 mb-3">
              <div class="ci-img" style="background:<?= $bg ?>;width:40px;height:40px;font-size:18px"><?= $ci['emoji'] ?></div>
              <div class="flex-grow-1">
                <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($ci['name']) ?></div>
                <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($ci['shop_name']) ?> × <?= $ci['qty'] ?></div>
              </div>
              <div style="font-size:13px;font-weight:600">₱<?= number_format($ci['line_total'],2) ?></div>
            </div>
            <?php endforeach; ?>
            <hr>
            <div class="d-flex justify-content-between small mb-1"><span class="text-muted">Subtotal</span><span>₱<?= number_format($subtotal,2) ?></span></div>
            <div class="d-flex justify-content-between small mb-3"><span class="text-muted">Delivery</span><span class="text-success fw-500">Free</span></div>
            <div class="d-flex justify-content-between fw-700 mb-4" style="font-size:16px">
              <span>Total</span>
              <span style="color:var(--fv-pink)">₱<?= number_format($subtotal,2) ?></span>
            </div>
            <button type="submit" class="btn-fv-fill w-100 py-3" style="border-radius:12px;font-size:15px;font-weight:700">
              ✓ Place Order
            </button>
            <p class="text-muted text-center small mt-2 mb-0">By placing this order you agree to our terms.</p>
          </div>
        </div>

      </div>
      </form>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
