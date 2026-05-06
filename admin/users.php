<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$success='';
if(isset($_GET['deleted'])) $success="User deleted.";

$users=$conn->query("SELECT * FROM users ORDER BY id ASC");
$cur=basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Users – Floravera Admin</title>
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
    <div class="fv-topbar"><div class="fv-topbar-title">👥 Users</div></div>
    <div class="fv-dash-content">
      <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <div class="fv-card">
        <div class="table-responsive">
        <table class="table fv-admin-table align-middle">
          <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Username</th><th>Role</th><th>Joined</th><th>Action</th></tr></thead>
          <tbody>
          <?php while($u=$users->fetch_assoc()): ?>
          <tr>
            <td class="text-muted small"><?= $u['id'] ?></td>
            <td style="font-size:13px;font-weight:500"><?= htmlspecialchars($u['fullname']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($u['email']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($u['username']) ?></td>
            <td>
              <form method="POST" action="update_role.php" class="d-inline">
                <input type="hidden" name="id" value="<?= $u['id'] ?>">
                <select name="role" class="form-select form-select-sm" style="width:110px;display:inline" onchange="this.form.submit()">
                  <option value="customer" <?= $u['role']==='customer'?'selected':'' ?>>customer</option>
                  <option value="seller"   <?= $u['role']==='seller'  ?'selected':'' ?>>seller</option>
                  <option value="admin"    <?= $u['role']==='admin'   ?'selected':'' ?>>admin</option>
                </select>
              </form>
            </td>
            <td class="text-muted small"><?= date('M d, Y',strtotime($u['created_at'])) ?></td>
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
