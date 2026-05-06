<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

include '../dbconnect.php';

$uid = (int)$_SESSION['user_id'];

// Get vendor row
$vendorRow = $conn->query("SELECT * FROM vendors WHERE user_id=$uid LIMIT 1")->fetch_assoc();
if (!$vendorRow || $vendorRow['status'] !== 'approved') {
    header('Location: become_vendor.php');
    exit;
}
$vid = (int)$vendorRow['id'];

// Ensure image column exists (safe to run repeatedly)
$conn->query("ALTER TABLE products ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT NULL");

/* ── Handle Actions ─────────────────────────────────────── */
$success = $error = '';

// DELETE product
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    $row = $conn->query("SELECT image FROM products WHERE id=$pid AND vendor_id=$vid LIMIT 1")->fetch_assoc();
    if ($row && $row['image'] && file_exists('../' . $row['image'])) {
        unlink('../' . $row['image']);
    }
    $conn->query("DELETE FROM products WHERE id=$pid AND vendor_id=$vid");
    $success = 'Product deleted.';
}

// ADD / EDIT product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = $conn->real_escape_string(trim($_POST['name']));
    $desc  = $conn->real_escape_string(trim($_POST['description']));
    $price = (float)$_POST['price'];
    $stock = (int)$_POST['stock'];
    $cat   = $conn->real_escape_string($_POST['category']);
    $badge = $conn->real_escape_string(trim($_POST['badge']));
    $pid   = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

    if ($name === '' || $price <= 0) {
        $error = 'Product name and a valid price are required.';
    } else {
        // Handle image upload
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
            $ftype   = mime_content_type($_FILES['image']['tmp_name']);
            if (!in_array($ftype, $allowed)) {
                $error = 'Only JPG, PNG, WEBP, or GIF images are allowed.';
            } else {
                $ext  = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fname = 'prod_' . $vid . '_' . time() . '.' . strtolower($ext);
                $dest  = '../uploads/products/' . $fname;
                if (!is_dir('../uploads/products')) mkdir('../uploads/products', 0755, true);
                if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
                    $imagePath = 'uploads/products/' . $fname;
                } else {
                    $error = 'Failed to save image. Check folder permissions.';
                }
            }
        }

        if (!$error) {
            if ($pid > 0) {
                $imageSQL = '';
                if ($imagePath) {
                    // Delete old image
                    $old = $conn->query("SELECT image FROM products WHERE id=$pid LIMIT 1")->fetch_assoc();
                    if ($old && $old['image'] && file_exists('../' . $old['image'])) unlink('../' . $old['image']);
                    $imageSQL = ", image='" . $conn->real_escape_string($imagePath) . "'";
                }
                $conn->query("UPDATE products SET
                    name='$name', description='$desc', price=$price, stock=$stock,
                    category='$cat', badge=" . ($badge ? "'$badge'" : "NULL") . "$imageSQL
                    WHERE id=$pid AND vendor_id=$vid");
                $success = 'Product updated!';
            } else {
                $imgVal = $imagePath ? "'" . $conn->real_escape_string($imagePath) . "'" : "NULL";
                $conn->query("INSERT INTO products (vendor_id,name,description,price,stock,category,badge,image)
                    VALUES ($vid,'$name','$desc',$price,$stock,'$cat'," . ($badge ? "'$badge'" : "NULL") . ",$imgVal)");
                $success = 'Product added!';
            }
        }
    }
}

// Fetch all products for this vendor
$products = $conn->query("SELECT * FROM products WHERE vendor_id=$vid ORDER BY created_at DESC");

// Pre-fill edit form
$editProduct = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $epid = (int)$_GET['edit'];
    $r = $conn->query("SELECT * FROM products WHERE id=$epid AND vendor_id=$vid LIMIT 1");
    if ($r->num_rows) $editProduct = $r->fetch_assoc();
}

$categories = ['flowers','bouquets','handicrafts','giftsets'];
$badges     = ['','new','low','hot','sale'];

$total    = $conn->query("SELECT COUNT(*) c FROM products WHERE vendor_id=$vid")->fetch_assoc()['c'];
$lowStock = $conn->query("SELECT COUNT(*) c FROM products WHERE vendor_id=$vid AND stock <= 5")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Shop – FloraVera</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../style.css">
<style>
.shop-header {
    background: linear-gradient(135deg, var(--fv-dark2), #2d1b4e);
    border-radius: 20px;
    padding: 28px 30px;
    color: #fff;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
}
.shop-header h2 { font-family: 'Cormorant Garamond', serif; font-size: 28px; font-weight: 700; margin: 0; }
.shop-header p  { font-size: 13px; color: rgba(255,255,255,.55); margin: 4px 0 0; }
.shop-stat {
    background: rgba(255,255,255,.08);
    border: .5px solid rgba(255,255,255,.12);
    border-radius: 14px;
    padding: 12px 20px;
    text-align: center;
    min-width: 90px;
}
.shop-stat-num   { font-size: 22px; font-weight: 700; color: #fff; font-family: 'Cormorant Garamond', serif; }
.shop-stat-label { font-size: 11px; color: rgba(255,255,255,.4); margin-top: 2px; }

.form-card {
    background: #fff;
    border: .5px solid var(--fv-border);
    border-radius: 20px;
    padding: 28px;
    margin-bottom: 28px;
}
.form-card h5 { font-weight: 600; font-size: 15px; margin-bottom: 20px; }
.form-label { font-size: 12px; font-weight: 600; color: var(--fv-muted); text-transform: uppercase; letter-spacing: .6px; margin-bottom: 5px; }
.form-control, .form-select {
    border-radius: 10px;
    border: 1.5px solid var(--fv-border);
    font-size: 14px;
    padding: 10px 14px;
    transition: border-color .2s;
}
.form-control:focus, .form-select:focus { border-color: var(--fv-pink); box-shadow: 0 0 0 3px rgba(214,51,132,.08); }

/* Image upload */
.img-preview-box {
    width: 100%; height: 130px;
    border: 2px dashed var(--fv-border);
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    flex-direction: column; gap: 6px;
    color: var(--fv-muted); font-size: 13px;
    cursor: pointer;
    transition: border-color .2s, background .2s;
    overflow: hidden;
    background: var(--fv-cream);
    position: relative;
}
.img-preview-box:hover { border-color: var(--fv-pink); background: var(--fv-pink-light); }
.img-preview-box img { width: 100%; height: 100%; object-fit: cover; border-radius: 10px; }
#imgInput { display: none; }

/* Products table */
.products-card { background: #fff; border: .5px solid var(--fv-border); border-radius: 20px; overflow: hidden; }
.products-card .card-head {
    padding: 18px 24px;
    border-bottom: .5px solid var(--fv-border);
    display: flex; align-items: center; justify-content: space-between;
}
.products-card .card-head h5 { font-weight: 600; font-size: 15px; margin: 0; }
.prod-table { width: 100%; border-collapse: collapse; }
.prod-table th {
    font-size: 11px; font-weight: 600; color: var(--fv-muted);
    text-transform: uppercase; letter-spacing: .7px;
    padding: 12px 16px; border-bottom: .5px solid var(--fv-border);
    background: var(--fv-cream);
}
.prod-table td { padding: 12px 16px; border-bottom: .5px solid var(--fv-border); font-size: 13.5px; vertical-align: middle; }
.prod-table tr:last-child td { border-bottom: none; }
.prod-table tr:hover td { background: var(--fv-cream); }

.prod-thumb {
    width: 52px; height: 52px; border-radius: 10px;
    object-fit: cover; border: .5px solid var(--fv-border);
}
.prod-thumb-placeholder {
    width: 52px; height: 52px; border-radius: 10px;
    background: var(--fv-pink-light);
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
}
.prod-name { font-weight: 600; color: var(--fv-dark); }
.prod-desc { font-size: 12px; color: var(--fv-muted); }
.price-tag { font-weight: 700; color: var(--fv-pink); }
.stock-low { color: #ef4444; font-weight: 600; }
.cat-pill { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: capitalize; background: var(--fv-pink-light); color: var(--fv-pink); }
.badge-pill { display: inline-block; padding: 2px 9px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
.badge-new  { background: #d1fae5; color: #065f46; }
.badge-low  { background: #fee2e2; color: #991b1b; }
.badge-hot  { background: #fff3cd; color: #92400e; }
.badge-sale { background: #ede9fe; color: #5b21b6; }

.action-btn { display: inline-flex; align-items: center; gap: 4px; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; transition: all .15s; }
.btn-edit   { background: var(--fv-pink-light); color: var(--fv-pink); }
.btn-edit:hover { background: var(--fv-pink); color: #fff; }
.btn-del    { background: #fee2e2; color: #dc2626; }
.btn-del:hover  { background: #dc2626; color: #fff; }

.empty-state { padding: 48px; text-align: center; color: var(--fv-muted); }
.empty-state .es-icon { font-size: 48px; margin-bottom: 12px; }
.empty-state p { font-size: 14px; margin: 0; }

.fv-alert { padding: 12px 16px; border-radius: 12px; font-size: 13.5px; font-weight: 500; margin-bottom: 20px; }
.fv-alert-success { background: #d1fae5; color: #065f46; }
.fv-alert-error   { background: #fee2e2; color: #991b1b; }
</style>
</head>
<body>

<!-- TOP NAVBAR (same pattern as other customer pages) -->
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <div class="d-flex align-items-center gap-2">
    <span style="font-size:13px;color:var(--fv-muted)">🏪 My Shop</span>
  </div>
</nav>

<div class="d-flex" style="padding-top:66px; min-height:100vh;">
    <?php include 'partials/sidebar.php'; ?>

    <main class="fv-main p-4">

        <!-- Shop Header Banner -->
        <div class="shop-header">
            <div>
                <h2>🏪 <?= htmlspecialchars($vendorRow['shop_name']) ?></h2>
                <p><?= htmlspecialchars($vendorRow['shop_type']) ?> · <?= htmlspecialchars($vendorRow['city']) ?></p>
            </div>
            <div class="d-flex gap-3">
                <div class="shop-stat">
                    <div class="shop-stat-num"><?= $total ?></div>
                    <div class="shop-stat-label">Products</div>
                </div>
                <div class="shop-stat">
                    <div class="shop-stat-num" style="color:<?= $lowStock > 0 ? '#f87171' : '#fff' ?>"><?= $lowStock ?></div>
                    <div class="shop-stat-label">Low Stock</div>
                </div>
            </div>
        </div>

        <?php if ($success): ?>
            <div class="fv-alert fv-alert-success">✅ <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="fv-alert fv-alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add / Edit Form -->
        <div class="form-card">
            <h5><?= $editProduct ? '✏️ Edit Product' : '➕ Add New Product' ?></h5>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                <?php endif; ?>
                <div class="row g-3">

                    <!-- Image Upload Column -->
                    <div class="col-md-2">
                        <label class="form-label">Product Image</label>
                        <div class="img-preview-box" onclick="document.getElementById('imgInput').click()">
                            <?php if (!empty($editProduct['image']) && file_exists('../' . $editProduct['image'])): ?>
                                <img src="../<?= htmlspecialchars($editProduct['image']) ?>" id="previewImg" alt="preview">
                            <?php else: ?>
                                <div id="previewPlaceholder" style="text-align:center">
                                    <div style="font-size:28px">🖼️</div>
                                    <div style="font-size:11px;margin-top:4px">Click to upload</div>
                                </div>
                                <img id="previewImg" src="" alt="preview" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:10px">
                            <?php endif; ?>
                        </div>
                        <input type="file" name="image" id="imgInput" accept="image/jpeg,image/png,image/webp,image/gif" onchange="previewImage(this)">
                        <div style="font-size:11px;color:var(--fv-muted);margin-top:5px">JPG, PNG, WEBP · Max 5MB</div>
                    </div>

                    <!-- Fields Column -->
                    <div class="col-md-10">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Product Name *</label>
                                <input type="text" name="name" class="form-control" required
                                    value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>"
                                    placeholder="e.g. Rose Bouquet">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Price (₱) *</label>
                                <input type="number" name="price" class="form-control" min="1" step="0.01" required
                                    value="<?= $editProduct['price'] ?? '' ?>" placeholder="0.00">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Stock</label>
                                <input type="number" name="stock" class="form-control" min="0"
                                    value="<?= $editProduct['stock'] ?? 10 ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Category *</label>
                                <select name="category" class="form-select" required>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?= $c ?>" <?= ($editProduct['category'] ?? 'flowers') === $c ? 'selected' : '' ?>>
                                            <?= ucfirst($c) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Badge</label>
                                <select name="badge" class="form-select">
                                    <?php foreach ($badges as $b): ?>
                                        <option value="<?= $b ?>" <?= ($editProduct['badge'] ?? '') === $b ? 'selected' : '' ?>>
                                            <?= $b === '' ? '— None' : ucfirst($b) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Description</label>
                                <input type="text" name="description" class="form-control"
                                    value="<?= htmlspecialchars($editProduct['description'] ?? '') ?>"
                                    placeholder="Short description of your product...">
                            </div>
                        </div>
                    </div>

                </div><!-- /row -->

                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn-fv-fill" style="padding:10px 26px">
                        <?= $editProduct ? '💾 Save Changes' : '➕ Add Product' ?>
                    </button>
                    <?php if ($editProduct): ?>
                        <a href="my_shop.php" class="btn-fv-outline" style="padding:9px 22px">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Products Table -->
        <div class="products-card">
            <div class="card-head">
                <h5>📦 My Products</h5>
                <span style="font-size:12px;color:var(--fv-muted)"><?= $total ?> listing<?= $total != 1 ? 's' : '' ?></span>
            </div>

            <?php if ($products->num_rows === 0): ?>
                <div class="empty-state">
                    <div class="es-icon">🌿</div>
                    <p>You haven't listed any products yet.<br>Use the form above to add your first one!</p>
                </div>
            <?php else: ?>
                <div style="overflow-x:auto">
                    <table class="prod-table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Product</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Badge</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($p = $products->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($p['image']) && file_exists('../' . $p['image'])): ?>
                                        <img src="../<?= htmlspecialchars($p['image']) ?>" class="prod-thumb" alt="<?= htmlspecialchars($p['name']) ?>">
                                    <?php else: ?>
                                        <div class="prod-thumb-placeholder">🌸</div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="prod-name"><?= htmlspecialchars($p['name']) ?></div>
                                    <?php if ($p['description']): ?>
                                        <div class="prod-desc"><?= htmlspecialchars(mb_substr($p['description'], 0, 55)) ?>...</div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="cat-pill"><?= htmlspecialchars($p['category']) ?></span></td>
                                <td><span class="price-tag">₱<?= number_format($p['price'], 2) ?></span></td>
                                <td>
                                    <span class="<?= $p['stock'] <= 5 ? 'stock-low' : '' ?>">
                                        <?= $p['stock'] ?><?= $p['stock'] <= 5 ? ' ⚠️' : '' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($p['badge']): ?>
                                        <span class="badge-pill badge-<?= $p['badge'] ?>"><?= $p['badge'] ?></span>
                                    <?php else: ?>
                                        <span style="color:var(--fv-muted);font-size:12px">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <a href="my_shop.php?edit=<?= $p['id'] ?>" class="action-btn btn-edit">✏️ Edit</a>
                                        <a href="my_shop.php?delete=<?= $p['id'] ?>"
                                           class="action-btn btn-del"
                                           onclick="return confirm('Delete this product?')">🗑 Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        const img = document.getElementById('previewImg');
        const placeholder = document.getElementById('previewPlaceholder');
        img.src = e.target.result;
        img.style.display = 'block';
        if (placeholder) placeholder.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
}
</script>
</body>
</html>
