<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']==='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';

$uid = $_SESSION['user_id'];

// Stats
$totalOrders   = $conn->query("SELECT COUNT(*) c FROM orders WHERE customer_id=$uid")->fetch_assoc()['c'];
$pendingOrders = $conn->query("SELECT COUNT(*) c FROM orders WHERE customer_id=$uid AND status='pending'")->fetch_assoc()['c'];
$reviewCount   = $conn->query("SELECT COUNT(*) c FROM reviews WHERE customer_id=$uid")->fetch_assoc()['c'];

// Recent orders
$recentOrders = $conn->query("SELECT o.*,GROUP_CONCAT(p.name SEPARATOR ', ') AS items
    FROM orders o
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    WHERE o.customer_id=$uid
    GROUP BY o.id ORDER BY o.created_at DESC LIMIT 3");

// Products
$cat    = isset($_GET['cat'])   ? $_GET['cat']   : 'all';
$sort   = isset($_GET['sort'])  ? $_GET['sort']  : 'default';
$search = isset($_GET['search'])? trim($_GET['search']) : '';
$maxPrice = isset($_GET['maxprice']) ? (int)$_GET['maxprice'] : 9999;

$where = "1=1";
if($cat !== 'all') $where .= " AND p.category='".mysqli_real_escape_string($conn,$cat)."'";
if($search) $where .= " AND (p.name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR v.shop_name LIKE '%".mysqli_real_escape_string($conn,$search)."%')";
$where .= " AND p.price <= $maxPrice";

$orderBy = "p.id ASC";
if($sort==='asc')    $orderBy = "p.price ASC";
if($sort==='desc')   $orderBy = "p.price DESC";
if($sort==='rating') $orderBy = "p.id DESC";

$products = $conn->query("SELECT p.*,v.shop_name FROM products p JOIN vendors v ON v.id=p.vendor_id WHERE $where ORDER BY $orderBy");

// Wishlist set for current user
$wlResult = $conn->query("SELECT product_id FROM wishlist WHERE customer_id=$uid");
$wlSet = [];
while($w=$wlResult->fetch_assoc()) $wlSet[] = $w['product_id'];

// Cart count from session
$cartCount = 0;
if(isset($_SESSION['cart'])) foreach($_SESSION['cart'] as $item) $cartCount += $item['qty'];

$pageTitle = "Dashboard";
$bgMap = ['flowers'=>'#fce4ef','bouquets'=>'#fffde7','handicrafts'=>'#e8f5e9','giftsets'=>'#fff3e0'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Browse &amp; Shop – Floravera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>

<!-- TOP NAVBAR (dashboard) -->
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <div class="d-flex align-items-center gap-2">
    <form class="fv-search-box" method="GET">
      <span style="color:var(--fv-muted)">🔍</span>
      <input type="text" name="search" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>" style="border:none;background:transparent;outline:none;font-size:13px;width:180px;font-family:'DM Sans',sans-serif">
      <input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>">
      <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
    </form>
    <a href="cart.php" class="btn-fv-fill d-flex align-items-center gap-2" style="text-decoration:none;font-size:13px">
      🛒 Cart <span class="badge bg-white text-danger"><?= $cartCount ?></span>
    </a>
  </div>
</nav>

<div class="fv-dash-wrap">
  <?php include 'partials/sidebar.php'; ?>
  <div class="fv-main">

    <div class="fv-topbar">
      <div class="fv-topbar-title">Browse &amp; Shop</div>
    </div>

    <div class="fv-dash-content">

      <!-- STAT CARDS -->
      <div class="row g-3 mb-4">
        <div class="col-4"><div class="fv-stat"><div class="fv-stat-label">Total Orders</div><div class="fv-stat-val"><?= $totalOrders ?></div></div></div>
        <div class="col-4"><div class="fv-stat"><div class="fv-stat-label">Pending Orders</div><div class="fv-stat-val"><?= $pendingOrders ?></div></div></div>
        <div class="col-4"><div class="fv-stat hl"><div class="fv-stat-label">Reviews Given</div><div class="fv-stat-val"><?= $reviewCount ?></div></div></div>
      </div>

      <!-- RECENT ORDERS -->
      <div class="fv-card mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="fw-600">Recent Orders</div>
          <a href="orders.php" style="font-size:12px;color:var(--fv-pink)">View all →</a>
        </div>
        <?php if($recentOrders->num_rows===0): ?>
          <p class="text-muted small">No orders yet. Start shopping!</p>
        <?php else: ?>
          <?php while($o=$recentOrders->fetch_assoc()): ?>
          <div class="fv-order-row">
            <div class="fv-order-icon">📦</div>
            <div class="flex-grow-1">
              <div class="fv-order-name"><?= htmlspecialchars($o['items']) ?></div>
              <div class="fv-order-meta">Order #<?= $o['id'] ?> · <?= date('M d, Y',strtotime($o['created_at'])) ?></div>
            </div>
            <div class="text-end">
              <div class="fv-order-price">₱<?= number_format($o['total'],2) ?></div>
              <span class="fv-status fv-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span>
            </div>
          </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>

      <!-- FILTER BAR -->
      <form method="GET" class="mb-3" id="filterForm">
        <!-- Single hidden cat input — JS updates it when chip is clicked -->
        <input type="hidden" name="cat" id="catInput" value="<?= htmlspecialchars($cat) ?>">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">

        <div class="d-flex gap-2 flex-wrap align-items-center mb-2">
          <?php foreach(['all'=>'All','flowers'=>'Flowers','bouquets'=>'Bouquets','handicrafts'=>'Handicrafts','giftsets'=>'Gift Sets'] as $k=>$v): ?>
          <button type="button"
                  onclick="setCategory('<?= $k ?>')"
                  class="fv-chip <?= $cat===$k?'active':'' ?>"
                  data-cat="<?= $k ?>"><?= $v ?></button>
          <?php endforeach; ?>

          <select name="sort" class="ms-auto"
                  style="border:1px solid var(--fv-border);border-radius:10px;padding:6px 10px;font-size:12px;outline:none;background:#fff"
                  onchange="this.form.submit()">
            <option value="default" <?= $sort==='default'?'selected':'' ?>>Sort: Default</option>
            <option value="asc"     <?= $sort==='asc'    ?'selected':'' ?>>Price: Low to high</option>
            <option value="desc"    <?= $sort==='desc'   ?'selected':'' ?>>Price: High to low</option>
            <option value="rating"  <?= $sort==='rating' ?'selected':'' ?>>Newest first</option>
          </select>
        </div>

        <div class="d-flex align-items-center gap-2" style="font-size:13px;color:var(--fv-muted)">
          Max price: ₱<span id="priceLabel"><?= $maxPrice >= 9999 ? 800 : $maxPrice ?></span>
          <input type="range" name="maxprice" min="100" max="800" step="50"
                 value="<?= $maxPrice >= 9999 ? 800 : $maxPrice ?>"
                 style="accent-color:var(--fv-pink)"
                 oninput="document.getElementById('priceLabel').textContent=this.value"
                 onchange="document.getElementById('filterForm').submit()">
        </div>
      </form>

      <!-- PRODUCT GRID -->
      <div class="row g-3">
        <?php if($products->num_rows===0): ?>
          <div class="col-12 text-center py-5 text-muted">No products found 🌱</div>
        <?php endif; ?>
        <?php while($p=$products->fetch_assoc()):
          $bg = $bgMap[$p['category']] ?? '#f3f4f6';
          $inWL = in_array($p['id'], $wlSet);
        ?>
        <div class="col-6 col-sm-4 col-md-3 col-lg-2">
          <div class="fv-dp h-100">
            <div class="fv-dp-img" style="background:<?= $bg ?>">
              <?php if(!empty($p['image']) && file_exists('../'.$p['image'])): ?>
                <img src="../<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>"
                     style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
              <?php else: ?>
                <?= $p['emoji'] ?>
              <?php endif; ?>
              <?php if($p['badge']): ?>
                <span class="position-absolute top-0 start-0 m-2 badge-<?= $p['badge'] ?>-pill"><?= strtoupper($p['badge']) ?></span>
              <?php endif; ?>
              <a href="wishlist_toggle.php?id=<?= $p['id'] ?>" class="wl-heart <?= $inWL?'on':'' ?>">♥</a>
            </div>
            <div class="fv-dp-body">
              <div class="fv-dp-name"><?= htmlspecialchars($p['name']) ?></div>
              <div class="fv-dp-vendor"><?= htmlspecialchars($p['shop_name']) ?></div>
              <div class="fv-dp-stars">★★★★☆ <?= number_format(4.5,1) ?></div>
              <div class="fv-dp-price">₱<?= number_format($p['price'],2) ?></div>
              <a href="cart_add.php?id=<?= $p['id'] ?>" class="btn-add-cart">+ Add to cart</a>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      </div>

    </div><!-- /dash-content -->
  </div><!-- /fv-main -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setCategory(cat){
  document.getElementById('catInput').value = cat;
  // Update active chip styles
  document.querySelectorAll('.fv-chip[data-cat]').forEach(function(btn){
    btn.classList.toggle('active', btn.dataset.cat === cat);
  });
  document.getElementById('filterForm').submit();
}
</script>
<?php include __DIR__.'/partials/chatbot_widget.php'; ?>
</body>
</html>
