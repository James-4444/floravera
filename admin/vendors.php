<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$vendors=$conn->query("SELECT v.*,u.fullname,u.email FROM vendors v JOIN users u ON u.id=v.user_id ORDER BY v.created_at DESC");
$cur=basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Vendors – Floravera Admin</title>
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
    <div class="fv-topbar"><div class="fv-topbar-title">🏪 Vendors</div></div>
    <div class="fv-dash-content">
      <div class="fv-card">
        <div class="table-responsive">
        <table class="table fv-admin-table align-middle">
          <thead><tr><th>ID</th><th>Shop Name</th><th>Owner</th><th>Type</th><th>City</th><th>Status</th><th>Action</th></tr></thead>
          <tbody>
          <?php while($v=$vendors->fetch_assoc()): ?>
          <tr>
            <td class="text-muted small"><?= $v['id'] ?></td>
            <td style="font-size:13px;font-weight:600"><?= htmlspecialchars($v['shop_name']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($v['fullname']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($v['shop_type']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($v['city']) ?></td>
            <td>
              <span class="fv-status <?= $v['status']==='approved'?'fv-status-completed':($v['status']==='pending'?'fv-status-pending':'fv-status-cancelled') ?>">
                <?= ucfirst($v['status']) ?>
              </span>
            </td>
            <td class="d-flex gap-1">
              <?php if($v['status']==='pending'): ?>
              <a href="vendor_approve.php?id=<?= $v['id'] ?>&action=approve" class="btn btn-success btn-sm" style="font-size:11px">✓ Approve</a>
              <a href="vendor_approve.php?id=<?= $v['id'] ?>&action=reject"  class="btn btn-outline-danger btn-sm" style="font-size:11px">✕ Reject</a>
              <?php else: ?>
              <span class="text-muted small">—</span>
              <?php endif; ?>
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
