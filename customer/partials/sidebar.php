<?php
// customer/partials/sidebar.php
$current     = basename($_SERVER['PHP_SELF']);
$inVendor    = strpos($_SERVER['PHP_SELF'], '/vendor/') !== false;
$isShopOrders = $inVendor && $current === 'orders.php';
$isMyOrders   = !$inVendor && $current === 'orders.php';

// Determine base path depending on whether we are inside vendor/ or customer/
$base = $inVendor ? '../customer/' : '';
$vendorBase = $inVendor ? '' : '../vendor/';

// Check if user already has a vendor application
include_once dirname(__DIR__).'/../dbconnect.php';
$uid = (int)$_SESSION['user_id'];
$vendorRow = $conn->query("SELECT * FROM vendors WHERE user_id=$uid LIMIT 1")->fetch_assoc();
?>
<div class="fv-sidebar">
  <div class="fv-sidebar-user d-flex align-items-center gap-2">
    <div class="fv-avatar"><?= strtoupper(substr($_SESSION['fullname'],0,1).(strrchr($_SESSION['fullname'],' ')? substr(strrchr($_SESSION['fullname'],' '),1,1) : '')) ?></div>
    <div>
      <div class="fv-uname"><?= htmlspecialchars($_SESSION['fullname']) ?></div>
      <div class="fv-urole"><?= isset($vendorRow) && $vendorRow['status']==='approved' ? 'Vendor' : 'Customer' ?></div>
    </div>
  </div>

  <div class="fv-nav-label mt-2">Shop</div>
  <a href="<?= $base ?>dashboard.php"  class="fv-nav-item <?= $current==='dashboard.php' ?'active':'' ?>"><span class="fv-nav-icon">🛍</span> Browse &amp; Shop</a>
  <a href="<?= $base ?>wishlist.php"   class="fv-nav-item <?= $current==='wishlist.php'  ?'active':'' ?>"><span class="fv-nav-icon">❤</span> Wishlist</a>

  <div class="fv-nav-label">My Shop</div>
  <?php if(!$vendorRow): ?>
    <a href="<?= $base ?>become_vendor.php" class="fv-nav-item <?= $current==='become_vendor.php'?'active':'' ?>"><span class="fv-nav-icon">🏪</span> Become a Vendor</a>
  <?php elseif($vendorRow['status']==='pending'): ?>
    <a href="<?= $base ?>become_vendor.php" class="fv-nav-item <?= $current==='become_vendor.php'?'active':'' ?>">
      <span class="fv-nav-icon">⏳</span> Application Pending
    </a>
  <?php elseif($vendorRow['status']==='approved'): ?>
    <a href="<?= $base ?>my_shop.php" class="fv-nav-item <?= $current==='my_shop.php'?'active':'' ?>"><span class="fv-nav-icon">🏪</span> My Shop</a>
    <a href="<?= $vendorBase ?>orders.php" class="fv-nav-item <?= $isShopOrders?'active':'' ?>"><span class="fv-nav-icon">📋</span> Shop Orders</a>
  <?php else: ?>
    <a href="<?= $base ?>become_vendor.php" class="fv-nav-item">
      <span class="fv-nav-icon">🔄</span> Re-apply as Vendor
    </a>
  <?php endif; ?>

  <div class="fv-nav-label">My Account</div>
  <a href="<?= $base ?>orders.php"     class="fv-nav-item <?= $isMyOrders    ?'active':'' ?>"><span class="fv-nav-icon">📦</span> My Orders</a>
  <a href="<?= $base ?>reviews.php"    class="fv-nav-item <?= $current==='reviews.php'   ?'active':'' ?>"><span class="fv-nav-icon">⭐</span> My Reviews</a>
  <a href="<?= $base ?>profile.php"    class="fv-nav-item <?= $current==='profile.php'   ?'active':'' ?>"><span class="fv-nav-icon">👤</span> My Profile</a>

  <div class="fv-logout">
    <a href="<?= $inVendor ? '../' : '../' ?>logout.php"><span>↩</span> Logout</a>
  </div>
</div>
