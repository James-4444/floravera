<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$success=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['add_product'])){
    $vid=(int)$_POST['vendor_id'];
    $name=trim($_POST['name']);
    $price=(float)$_POST['price'];
    $stock=(int)$_POST['stock'];
    $cat=$_POST['category'];
    $emoji=trim($_POST['emoji']);
    if(empty($name)||$price<=0){ $error="Name and price are required."; }
    else {
        $stmt=$conn->prepare("INSERT INTO products (vendor_id,name,price,stock,category,emoji) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param("isdiss",$vid,$name,$price,$stock,$cat,$emoji);
        if($stmt->execute()) $success="Product added!";
        else $error="Failed to add product.";
    }
}
if(isset($_GET['delete'])){
    $id=(int)$_GET['delete'];
    $conn->query("DELETE FROM products WHERE id=$id");
    $success="Product deleted.";
}

$products=$conn->query("SELECT p.*,v.shop_name FROM products p JOIN vendors v ON v.id=p.vendor_id ORDER BY p.id DESC");
$vendors=$conn->query("SELECT * FROM vendors WHERE status='approved'");
$cur=basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Products – Floravera Admin</title>
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
    <div class="fv-topbar"><div class="fv-topbar-title">🌸 Products</div></div>
    <div class="fv-dash-content">
      <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <!-- Add Product Form -->
      <div class="fv-card mb-4">
        <div class="fw-600 mb-3">+ Add New Product</div>
        <form method="POST" class="row g-2">
          <div class="col-md-3"><label class="fv-label">Vendor</label>
            <select name="vendor_id" class="fv-input" required>
              <?php $vendors->data_seek(0); while($v=$vendors->fetch_assoc()): ?>
              <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['shop_name']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="col-md-3"><label class="fv-label">Product Name</label><input type="text" name="name" class="fv-input" placeholder="e.g. Rose bouquet" required></div>
          <div class="col-md-2"><label class="fv-label">Price (₱)</label><input type="number" name="price" class="fv-input" placeholder="350" step="0.01" required></div>
          <div class="col-md-1"><label class="fv-label">Stock</label><input type="number" name="stock" class="fv-input" value="10"></div>
          <div class="col-md-2"><label class="fv-label">Category</label>
            <select name="category" class="fv-input">
              <option value="flowers">Flowers</option>
              <option value="bouquets">Bouquets</option>
              <option value="handicrafts">Handicrafts</option>
              <option value="giftsets">Gift Sets</option>
            </select>
          </div>
          <div class="col-md-1"><label class="fv-label">Emoji</label><input type="text" name="emoji" class="fv-input" value="🌸"></div>
          <div class="col-12"><button type="submit" name="add_product" class="btn-fv-fill" style="padding:8px 24px">Add Product</button></div>
        </form>
      </div>

      <!-- Products Table -->
      <div class="fv-card">
        <div class="table-responsive">
        <table class="table fv-admin-table align-middle">
          <thead><tr><th>ID</th><th>Product</th><th>Vendor</th><th>Category</th><th>Price</th><th>Stock</th><th>Action</th></tr></thead>
          <tbody>
          <?php while($p=$products->fetch_assoc()): ?>
          <tr>
            <td class="text-muted small"><?= $p['id'] ?></td>
            <td><span style="font-size:18px"><?= $p['emoji'] ?></span> <?= htmlspecialchars($p['name']) ?></td>
            <td class="text-muted small"><?= htmlspecialchars($p['shop_name']) ?></td>
            <td><span class="badge bg-light text-dark" style="font-size:10px"><?= $p['category'] ?></span></td>
            <td class="fw-600" style="color:var(--fv-pink)">₱<?= number_format($p['price'],2) ?></td>
            <td class="<?= $p['stock']<=5?'text-danger fw-600':'' ?>"><?= $p['stock'] ?></td>
            <td><a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')" style="font-size:11px">Delete</a></td>
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
