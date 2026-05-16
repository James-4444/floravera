<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';

$uid = (int)$_SESSION['user_id'];

// Must be an approved vendor
$vendorRow = $conn->query("SELECT * FROM vendors WHERE user_id=$uid LIMIT 1")->fetch_assoc();
if(!$vendorRow || $vendorRow['status']!=='approved'){
    header("Location: ../customer/dashboard.php");
    exit();
}
$vid = (int)$vendorRow['id'];

// Handle status update by vendor
if(isset($_GET['status']) && isset($_GET['id'])){
    $oid = (int)$_GET['id'];
    $allowed = ['processing','completed'];
    $st = in_array($_GET['status'], $allowed) ? $_GET['status'] : null;
    if($st){
        // Verify this order contains at least one product from this vendor
        $check = $conn->query("SELECT COUNT(*) c FROM order_items oi
            JOIN products p ON p.id=oi.product_id
            WHERE oi.order_id=$oid AND p.vendor_id=$vid")->fetch_assoc()['c'];
        if($check > 0){
            $conn->query("UPDATE orders SET status='$st' WHERE id=$oid");
        }
    }
    header("Location: orders.php");
    exit();
}

// Fetch orders that contain this vendor's products
$orders = $conn->query("SELECT DISTINCT o.*, u.fullname, u.email,
    GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') AS items,
    GROUP_CONCAT(DISTINCT COALESCE(p.image,'') SEPARATOR '|') AS images,
    GROUP_CONCAT(DISTINCT COALESCE(p.emoji,'🌸') SEPARATOR '|') AS emojis,
    SUM(oi.qty * oi.price) AS vendor_total,
    COUNT(DISTINCT oi.id) AS item_count
    FROM orders o
    JOIN users u ON u.id=o.customer_id
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    WHERE p.vendor_id=$vid
    GROUP BY o.id ORDER BY o.created_at DESC");

$cur = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Shop Orders – FloraVera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
<style>
.order-card {
    background:#fff;
    border:.5px solid var(--fv-border);
    border-radius:16px;
    padding:18px 22px;
    margin-bottom:14px;
    transition:box-shadow .2s;
}
.order-card:hover { box-shadow:0 4px 16px rgba(214,51,132,.08); }
.order-thumb {
    width:44px; height:44px; border-radius:10px;
    object-fit:cover; border:.5px solid var(--fv-border); flex-shrink:0;
}
.order-thumb-placeholder {
    width:44px; height:44px; border-radius:10px;
    background:var(--fv-pink-light);
    display:flex; align-items:center; justify-content:center;
    font-size:18px; flex-shrink:0;
}
.order-id   { font-size:12px; color:var(--fv-muted); font-weight:600; }
.order-customer { font-size:13px; font-weight:600; color:var(--fv-dark); }
.order-items-text { font-size:12px; color:var(--fv-muted); }
.action-btn { display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:8px;font-size:12px;font-weight:600;border:none;cursor:pointer;text-decoration:none;transition:all .15s; }
.btn-process { background:#dbeafe;color:#1e40af; }
.btn-process:hover { background:#1e40af;color:#fff; }
.btn-complete { background:#d1fae5;color:#065f46; }
.btn-complete:hover { background:#065f46;color:#fff; }
</style>
</head>
<body>
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <span style="font-size:13px;color:var(--fv-muted)">📦 Shop Orders</span>
</nav>

<div class="d-flex" style="padding-top:66px;min-height:100vh;">
  <?php include '../customer/partials/sidebar.php'; ?>

  <main class="fv-main p-4">

    <!-- Header -->
    <div style="background:linear-gradient(135deg,var(--fv-dark2),#2d1b4e);border-radius:20px;padding:24px 28px;color:#fff;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
      <div>
        <h2 style="font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:700;margin:0">📦 Shop Orders</h2>
        <p style="font-size:13px;color:rgba(255,255,255,.5);margin:4px 0 0"><?= htmlspecialchars($vendorRow['shop_name']) ?></p>
      </div>
      <?php
        $pending    = $conn->query("SELECT COUNT(DISTINCT o.id) c FROM orders o JOIN order_items oi ON oi.order_id=o.id JOIN products p ON p.id=oi.product_id WHERE p.vendor_id=$vid AND o.status='pending'")->fetch_assoc()['c'];
        $processing = $conn->query("SELECT COUNT(DISTINCT o.id) c FROM orders o JOIN order_items oi ON oi.order_id=o.id JOIN products p ON p.id=oi.product_id WHERE p.vendor_id=$vid AND o.status='processing'")->fetch_assoc()['c'];
      ?>
      <div class="d-flex gap-3">
        <div style="background:rgba(255,255,255,.08);border:.5px solid rgba(255,255,255,.12);border-radius:14px;padding:12px 20px;text-align:center;">
          <div style="font-size:22px;font-weight:700;color:#fbbf24;font-family:'Cormorant Garamond',serif"><?= $pending ?></div>
          <div style="font-size:11px;color:rgba(255,255,255,.4)">Pending</div>
        </div>
        <div style="background:rgba(255,255,255,.08);border:.5px solid rgba(255,255,255,.12);border-radius:14px;padding:12px 20px;text-align:center;">
          <div style="font-size:22px;font-weight:700;color:#60a5fa;font-family:'Cormorant Garamond',serif"><?= $processing ?></div>
          <div style="font-size:11px;color:rgba(255,255,255,.4)">Processing</div>
        </div>
      </div>
    </div>

    <?php if($orders->num_rows===0): ?>
      <div class="text-center py-5" style="color:var(--fv-muted)">
        <div style="font-size:48px">📭</div>
        <p class="mt-3">No orders for your shop yet.</p>
      </div>
    <?php else: ?>
      <?php while($o=$orders->fetch_assoc()):
        $imgList   = explode('|', $o['images']);
        $emojiList = explode('|', $o['emojis']);
        $previewCount = min(3, count($imgList));
      ?>
      <div class="order-card">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">

          <!-- Thumbnails + info -->
          <div class="d-flex align-items-center gap-3">
            <div class="d-flex gap-1">
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
                <div class="order-thumb-placeholder" style="font-size:12px;font-weight:700;color:var(--fv-pink)">+<?= count($imgList)-3 ?></div>
              <?php endif; ?>
            </div>
            <div>
              <div class="order-id">Order #<?= $o['id'] ?> · <?= $o['item_count'] ?> item<?= $o['item_count']!=1?'s':'' ?> · <?= date('M d, Y', strtotime($o['created_at'])) ?></div>
              <div class="order-customer">👤 <?= htmlspecialchars($o['fullname']) ?></div>
              <div class="order-items-text"><?= htmlspecialchars(mb_strimwidth($o['items'],0,60,'…')) ?></div>
            </div>
          </div>

          <!-- Total + status + actions -->
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <div style="font-size:16px;font-weight:700;color:var(--fv-pink)">₱<?= number_format($o['vendor_total'],2) ?></div>
            <span class="fv-status fv-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>

            <?php if($o['status']==='pending'): ?>
              <a href="orders.php?id=<?= $o['id'] ?>&status=processing" class="action-btn btn-process">▶ Mark Processing</a>
            <?php elseif($o['status']==='processing'): ?>
              <a href="orders.php?id=<?= $o['id'] ?>&status=completed" class="action-btn btn-complete">✓ Mark Completed</a>
            <?php endif; ?>
          </div>

        </div>
      </div>
      <?php endwhile; ?>
    <?php endif; ?>

  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include '../customer/partials/chatbot_widget.php'; ?>
<?php include __DIR__.'/../customer/partials/chatbot_widget.php'; ?>
</body>
</html>
