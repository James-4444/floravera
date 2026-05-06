<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$uid=$_SESSION['user_id'];

// Ensure image column exists
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT NULL");

// Handle cancel request
if(isset($_GET['cancel']) && is_numeric($_GET['cancel'])){
    $oid=(int)$_GET['cancel'];
    // Only allow cancel if the order belongs to this user AND status is pending
    $check=$conn->query("SELECT status FROM orders WHERE id=$oid AND customer_id=$uid LIMIT 1")->fetch_assoc();
    if($check && $check['status']==='pending'){
        $conn->query("UPDATE orders SET status='cancelled' WHERE id=$oid AND customer_id=$uid");
    }
    header("Location: orders.php?cancelled=1");
    exit();
}

$orders=$conn->query("SELECT o.*,
    GROUP_CONCAT(p.name SEPARATOR ', ') AS items,
    GROUP_CONCAT(COALESCE(p.image,'') SEPARATOR '|') AS images,
    GROUP_CONCAT(COALESCE(p.emoji,'🌸') SEPARATOR '|') AS emojis,
    COUNT(oi.id) AS item_count
    FROM orders o
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    WHERE o.customer_id=$uid
    GROUP BY o.id ORDER BY o.created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Orders – Floravera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
<style>
.order-row-card {
    background:#fff;
    border:.5px solid var(--fv-border);
    border-radius:16px;
    padding:18px 20px;
    margin-bottom:14px;
    transition:box-shadow .2s;
}
.order-row-card:hover { box-shadow:0 4px 16px rgba(214,51,132,.08); }
.order-thumb-wrap { display:flex; gap:6px; align-items:center; }
.order-thumb {
    width:44px; height:44px; border-radius:10px;
    object-fit:cover; border:.5px solid var(--fv-border);
}
.order-thumb-placeholder {
    width:44px; height:44px; border-radius:10px;
    background:var(--fv-pink-light);
    display:flex; align-items:center; justify-content:center;
    font-size:18px; flex-shrink:0;
}
.order-id   { font-size:12px; color:var(--fv-muted); font-weight:600; }
.order-items-text { font-size:13px; font-weight:500; color:var(--fv-dark); }
.order-date { font-size:12px; color:var(--fv-muted); }
.btn-cancel {
    display:inline-flex; align-items:center; gap:4px;
    padding:5px 14px; border-radius:8px;
    font-size:12px; font-weight:600;
    background:#fee2e2; color:#dc2626;
    border:none; cursor:pointer; text-decoration:none;
    transition:all .15s;
}
.btn-cancel:hover { background:#dc2626; color:#fff; }
.cant-cancel {
    font-size:11px; color:var(--fv-muted);
    font-style:italic;
}
</style>
</head>
<body>
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <a href="cart.php" class="btn-fv-fill btn-sm" style="text-decoration:none">🛒 Cart</a>
</nav>
<div class="fv-dash-wrap">
  <?php include 'partials/sidebar.php'; ?>
  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">📦 My Orders</div></div>
    <div class="fv-dash-content">

      <?php if(isset($_GET['placed'])): ?>
        <div class="alert alert-success rounded-3">✅ Order placed successfully! We'll process it shortly.</div>
      <?php endif; ?>
      <?php if(isset($_GET['cancelled'])): ?>
        <div class="alert alert-warning rounded-3">🚫 Your order has been cancelled.</div>
      <?php endif; ?>

      <?php if($orders->num_rows===0): ?>
        <div class="text-center py-5 text-muted">
          <div style="font-size:48px">📦</div>
          <p class="mt-3">No orders yet. <a href="dashboard.php" style="color:var(--fv-pink)">Start shopping</a>!</p>
        </div>
      <?php else: ?>

        <!-- Header row -->
        <div class="d-flex align-items-center justify-content-between mb-3">
          <span style="font-size:14px;font-weight:600"><?= $orders->num_rows ?> order<?= $orders->num_rows!=1?'s':'' ?></span>
        </div>

        <?php while($o=$orders->fetch_assoc()):
          $imgList   = explode('|', $o['images']);
          $emojiList = explode('|', $o['emojis']);
          $previewCount = min(3, count($imgList));
        ?>
        <div class="order-row-card">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

            <!-- Left: thumbnails + info -->
            <div class="d-flex align-items-center gap-3">
              <div class="order-thumb-wrap">
                <?php for($i=0;$i<$previewCount;$i++):
                  $imgPath = $imgList[$i];
                  $emoji   = $emojiList[$i] ?? '🌸';
                ?>
                  <?php if($imgPath && file_exists('../'.$imgPath)): ?>
                    <img src="../<?= htmlspecialchars($imgPath) ?>" class="order-thumb" alt="product">
                  <?php else: ?>
                    <div class="order-thumb-placeholder"><?= htmlspecialchars($emoji) ?></div>
                  <?php endif; ?>
                <?php endfor; ?>
                <?php if(count($imgList)>3): ?>
                  <div class="order-thumb-placeholder" style="font-size:12px;font-weight:700;color:var(--fv-pink)">
                    +<?= count($imgList)-3 ?>
                  </div>
                <?php endif; ?>
              </div>

              <div>
                <div class="order-id">Order #<?= $o['id'] ?> · <?= $o['item_count'] ?> item<?= $o['item_count']!=1?'s':'' ?></div>
                <div class="order-items-text"><?= htmlspecialchars(mb_strimwidth($o['items'],0,55,'…')) ?></div>
                <div class="order-date"><?= date('M d, Y · g:i A', strtotime($o['created_at'])) ?></div>
              </div>
            </div>

            <!-- Right: total + status + action -->
            <div class="d-flex align-items-center gap-3 flex-wrap">
              <div class="text-end">
                <div style="font-size:16px;font-weight:700;color:var(--fv-pink)">₱<?= number_format($o['total'],2) ?></div>
              </div>
              <span class="fv-status fv-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>

              <?php if($o['status']==='pending'): ?>
                <a href="orders.php?cancel=<?= $o['id'] ?>"
                   class="btn-cancel"
                   onclick="return confirm('Cancel this order?')">✕ Cancel</a>
              <?php elseif($o['status']==='processing'): ?>
                <span class="cant-cancel">Cannot cancel — already processing</span>
              <?php endif; ?>
            </div>

          </div>
        </div>
        <?php endwhile; ?>

      <?php endif; ?>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
