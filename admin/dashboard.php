<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';

$totalUsers   = $conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'];
$totalVendors = $conn->query("SELECT COUNT(*) c FROM vendors WHERE status='approved'")->fetch_assoc()['c'];
$totalOrders  = $conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'];
$totalRevenue = $conn->query("SELECT SUM(total) s FROM orders WHERE status='completed'")->fetch_assoc()['s'] ?? 0;

$recentOrders = $conn->query("SELECT o.*,u.fullname FROM orders o JOIN users u ON u.id=o.customer_id ORDER BY o.created_at DESC LIMIT 5");
$allUsers     = $conn->query("SELECT * FROM users ORDER BY id ASC");
$pendingVendors = $conn->query("SELECT v.*,u.fullname,u.email FROM vendors v JOIN users u ON u.id=v.user_id WHERE v.status='pending' LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Dashboard – Floravera</title>
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
  <!-- ADMIN SIDEBAR -->
  <div class="fv-sidebar">
    <div class="fv-sidebar-user d-flex align-items-center gap-2">
      <div class="fv-avatar">AD</div>
      <div>
        <div class="fv-uname"><?= htmlspecialchars($_SESSION['fullname']) ?></div>
        <div class="fv-urole">Admin</div>
      </div>
    </div>
    <?php $cur=basename($_SERVER['PHP_SELF']); ?>
    <div class="fv-nav-label mt-2">Management</div>
    <a href="dashboard.php"  class="fv-nav-item <?= $cur==='dashboard.php'?'active':''?>"><span class="fv-nav-icon">📊</span> Overview</a>
    <a href="users.php"      class="fv-nav-item <?= $cur==='users.php'    ?'active':''?>"><span class="fv-nav-icon">👥</span> Users</a>
    <a href="vendors.php"    class="fv-nav-item <?= $cur==='vendors.php'  ?'active':''?>"><span class="fv-nav-icon">🏪</span> Vendors</a>
    <a href="products.php"   class="fv-nav-item <?= $cur==='products.php' ?'active':''?>"><span class="fv-nav-icon">🌸</span> Products</a>
    <a href="orders.php"     class="fv-nav-item <?= $cur==='orders.php'   ?'active':''?>"><span class="fv-nav-icon">📦</span> Orders</a>
    <div class="fv-logout">
      <a href="../logout.php"><span>↩</span> Logout</a>
    </div>
  </div>

  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">Overview</div></div>
    <div class="fv-dash-content">

      <!-- STAT CARDS -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3"><div class="fv-stat"><div class="fv-stat-label">Total Users</div><div class="fv-stat-val"><?= $totalUsers ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="fv-stat"><div class="fv-stat-label">Active Vendors</div><div class="fv-stat-val"><?= $totalVendors ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="fv-stat"><div class="fv-stat-label">Total Orders</div><div class="fv-stat-val"><?= $totalOrders ?></div></div></div>
        <div class="col-6 col-lg-3"><div class="fv-stat hl"><div class="fv-stat-label">Total Revenue</div><div class="fv-stat-val">₱<?= number_format($totalRevenue) ?></div></div></div>
      </div>

      <div class="row g-4 mb-4">
        <!-- Recent Orders -->
        <div class="col-lg-7">
          <div class="fv-card h-100">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <div class="fw-600">Recent Orders</div>
              <a href="orders.php" style="font-size:12px;color:var(--fv-pink)">View all →</a>
            </div>
            <table class="table checkout-table align-middle">
              <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Status</th></tr></thead>
              <tbody>
              <?php while($o=$recentOrders->fetch_assoc()): ?>
              <tr>
                <td class="text-muted small">#<?= $o['id'] ?></td>
                <td style="font-size:13px"><?= htmlspecialchars($o['fullname']) ?></td>
                <td class="fw-600" style="color:var(--fv-pink)">₱<?= number_format($o['total'],2) ?></td>
                <td><span class="fv-status fv-status-<?= $o['status'] ?>"><?= ucfirst($o['status']) ?></span></td>
              </tr>
              <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pending Vendor Approvals -->
        <div class="col-lg-5">
          <div class="fv-card h-100">
            <div class="fw-600 mb-3">Pending Vendor Approvals</div>
            <?php if($pendingVendors->num_rows===0): ?>
              <p class="text-muted small">No pending approvals.</p>
            <?php else: ?>
              <?php while($v=$pendingVendors->fetch_assoc()): ?>
              <div class="d-flex align-items-center gap-2 mb-3 p-2" style="background:#f9f9f9;border-radius:10px">
                <div class="fv-order-icon" style="font-size:16px">🏪</div>
                <div class="flex-grow-1">
                  <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($v['shop_name']) ?></div>
                  <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($v['fullname']) ?></div>
                </div>
                <a href="vendor_approve.php?id=<?= $v['id'] ?>&action=approve" class="btn btn-success btn-sm" style="font-size:11px">✓ Approve</a>
                <a href="vendor_approve.php?id=<?= $v['id'] ?>&action=reject"  class="btn btn-outline-danger btn-sm" style="font-size:11px">✕</a>
              </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- ALL USERS TABLE -->
      <div class="fv-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div class="fw-600">All Users</div>
          <a href="users.php" style="font-size:12px;color:var(--fv-pink)">Manage →</a>
        </div>
        <div class="table-responsive">
        <table class="table fv-admin-table align-middle">
          <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Action</th></tr></thead>
          <tbody>
          <?php while($u=$allUsers->fetch_assoc()): ?>
          <tr>
            <td class="text-muted small"><?= $u['id'] ?></td>
            <td style="font-size:13px;font-weight:500"><?= htmlspecialchars($u['fullname']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($u['username']) ?></td>
            <td>
              <span class="fv-status <?= $u['role']==='admin'?'fv-status-processing':($u['role']==='seller'?'fv-status-completed':'fv-status-pending') ?>">
                <?= $u['role'] ?>
              </span>
            </td>
            <td>
              <?php if($u['id']!==$_SESSION['user_id']): ?>
              <a href="delete_user.php?id=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                 onclick="return confirm('Delete this user?')" style="font-size:11px">Delete</a>
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
