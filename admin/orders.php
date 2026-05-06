<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';

// Update status
if(isset($_GET['status']) && isset($_GET['id'])){
    $oid=(int)$_GET['id'];
    $st=in_array($_GET['status'],['pending','processing','completed','cancelled'])?$_GET['status']:'pending';
    $conn->query("UPDATE orders SET status='$st' WHERE id=$oid");
    header("Location: orders.php"); exit();
}

// Delete order
if(isset($_GET['delete']) && is_numeric($_GET['delete'])){
    $oid=(int)$_GET['delete'];
    $conn->query("DELETE FROM orders WHERE id=$oid");
    header("Location: orders.php?deleted=1"); exit();
}

$orders=$conn->query("SELECT o.*,u.fullname,u.email,
    GROUP_CONCAT(p.name SEPARATOR ', ') AS items
    FROM orders o
    JOIN users u ON u.id=o.customer_id
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    GROUP BY o.id ORDER BY o.created_at DESC");
$cur=basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orders – Floravera Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <span class="text-muted small">Admin Panel</span>
</nav>
<div class="fv-dash-wrap">
  <div class="fv-sidebar">
    <div class="fv-sidebar-user d-flex align-items-center gap-2">
      <div class="fv-avatar">AD</div>
      <div><div class="fv-uname"><?= htmlspecialchars($_SESSION['fullname']) ?></div><div class="fv-urole">Admin</div></div>
    </div>
    <div class="fv-nav-label mt-2">Management</div>
    <a href="dashboard.php" class="fv-nav-item <?= $cur==='dashboard.php'?'active':''?>"><span class="fv-nav-icon">📊</span> Overview</a>
    <a href="users.php"     class="fv-nav-item <?= $cur==='users.php'    ?'active':''?>"><span class="fv-nav-icon">👥</span> Users</a>
    <a href="vendors.php"   class="fv-nav-item <?= $cur==='vendors.php'  ?'active':''?>"><span class="fv-nav-icon">🏪</span> Vendors</a>
    <a href="products.php"  class="fv-nav-item <?= $cur==='products.php' ?'active':''?>"><span class="fv-nav-icon">🌸</span> Products</a>
    <a href="orders.php"    class="fv-nav-item <?= $cur==='orders.php'   ?'active':''?>"><span class="fv-nav-icon">📦</span> Orders</a>
    <div class="fv-logout"><a href="../logout.php"><span>↩</span> Logout</a></div>
  </div>
  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">📦 All Orders</div></div>
    <div class="fv-dash-content">

      <?php if(isset($_GET['deleted'])): ?>
        <div class="alert alert-success rounded-3 mb-3">🗑 Order deleted successfully.</div>
      <?php endif; ?>

      <div class="fv-card">
        <div class="table-responsive">
        <table class="table fv-admin-table align-middle">
          <thead>
            <tr>
              <th>#</th>
              <th>Customer</th>
              <th>Items</th>
              <th>Total</th>
              <th>Status</th>
              <th>Date</th>
              <th>Update Status</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
          <?php while($o=$orders->fetch_assoc()): ?>
          <tr>
            <td class="text-muted small"><?= $o['id'] ?></td>
            <td style="font-size:13px;font-weight:500"><?= htmlspecialchars($o['fullname']) ?></td>
            <td class="text-muted small" style="max-width:160px"><?= htmlspecialchars(mb_strimwidth($o['items'],0,60,'...')) ?></td>
            <td class="fw-600" style="color:var(--fv-pink)">₱<?= number_format($o['total'],2) ?></td>
            <td><span class="fv-status fv-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td class="text-muted small"><?= date('M d, Y',strtotime($o['created_at'])) ?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <?php foreach(['pending','processing','completed','cancelled'] as $st): ?>
                  <?php if($st!==$o['status']): ?>
                  <a href="?id=<?= $o['id'] ?>&status=<?= $st ?>" class="btn btn-outline-secondary btn-sm" style="font-size:10px"><?= ucfirst($st) ?></a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </td>
            <td>
              <a href="?delete=<?= $o['id'] ?>"
                 class="btn btn-sm"
                 style="background:#fee2e2;color:#dc2626;border:none;font-size:11px;font-weight:600;border-radius:8px;"
                 onclick="return confirm('Delete Order #<?= $o['id'] ?>? This cannot be undone.')">
                🗑 Delete
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>


$orders=$conn->query("SELECT o.*,u.fullname,u.email,
    GROUP_CONCAT(p.name SEPARATOR ', ') AS items
    FROM orders o
    JOIN users u ON u.id=o.customer_id
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    GROUP BY o.id ORDER BY o.created_at DESC");
$cur=basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Orders – Floravera Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <span class="text-muted small">Admin Panel</span>
</nav>
<div class="fv-dash-wrap">
  <div class="fv-sidebar">
    <div class="fv-sidebar-user d-flex align-items-center gap-2">
      <div class="fv-avatar">AD</div>
      <div><div class="fv-uname"><?= htmlspecialchars($_SESSION['fullname']) ?></div><div class="fv-urole">Admin</div></div>
    </div>
    <div class="fv-nav-label mt-2">Management</div>
    <a href="dashboard.php" class="fv-nav-item <?= $cur==='dashboard.php'?'active':''?>"><span class="fv-nav-icon">📊</span> Overview</a>
    <a href="users.php"     class="fv-nav-item <?= $cur==='users.php'    ?'active':''?>"><span class="fv-nav-icon">👥</span> Users</a>
    <a href="vendors.php"   class="fv-nav-item <?= $cur==='vendors.php'  ?'active':''?>"><span class="fv-nav-icon">🏪</span> Vendors</a>
    <a href="products.php"  class="fv-nav-item <?= $cur==='products.php' ?'active':''?>"><span class="fv-nav-icon">🌸</span> Products</a>
    <a href="orders.php"    class="fv-nav-item <?= $cur==='orders.php'   ?'active':''?>"><span class="fv-nav-icon">📦</span> Orders</a>
    <div class="fv-logout"><a href="../logout.php"><span>↩</span> Logout</a></div>
  </div>
  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">📦 All Orders</div></div>
    <div class="fv-dash-content">
      <div class="fv-card">
        <div class="table-responsive">
        <table class="table fv-admin-table align-middle">
          <thead><tr><th>#</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th><th>Update</th></tr></thead>
          <tbody>
          <?php while($o=$orders->fetch_assoc()): ?>
          <tr>
            <td class="text-muted small"><?= $o['id'] ?></td>
            <td style="font-size:13px;font-weight:500"><?= htmlspecialchars($o['fullname']) ?></td>
            <td class="text-muted small" style="max-width:160px"><?= htmlspecialchars(mb_strimwidth($o['items'],0,60,'...')) ?></td>
            <td class="fw-600" style="color:var(--fv-pink)">₱<?= number_format($o['total'],2) ?></td>
            <td><span class="fv-status fv-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
            <td class="text-muted small"><?= date('M d, Y',strtotime($o['created_at'])) ?></td>
            <td>
              <div class="d-flex gap-1 flex-wrap">
                <?php foreach(['pending','processing','completed','cancelled'] as $st): ?>
                  <?php if($st!==$o['status']): ?>
                  <a href="?id=<?= $o['id'] ?>&status=<?= $st ?>" class="btn btn-outline-secondary btn-sm" style="font-size:10px"><?= ucfirst($st) ?></a>
                  <?php endif; ?>
                <?php endforeach; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
