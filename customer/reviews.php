<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$uid=(int)$_SESSION['user_id'];
$success=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $pid=(int)$_POST['product_id'];
    $rating=(int)$_POST['rating'];
    $comment=trim($_POST['comment']);
    if($rating<1||$rating>5){ $error="Please select a rating."; }
    else {
        $stmt=$conn->prepare("INSERT INTO reviews (customer_id,product_id,rating,comment) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE rating=VALUES(rating),comment=VALUES(comment)");
        $stmt->bind_param("iiis",$uid,$pid,$rating,$comment);
        if($stmt->execute()) $success="Review submitted!";
        else $error="Failed to submit review.";
    }
}

$reviews=$conn->query("SELECT r.*,p.name AS product_name,p.emoji,v.shop_name
    FROM reviews r
    JOIN products p ON p.id=r.product_id
    JOIN vendors v ON v.id=p.vendor_id
    WHERE r.customer_id=$uid
    ORDER BY r.created_at DESC");

// Products from completed orders (eligible for review)
$eligible=$conn->query("SELECT DISTINCT p.id,p.name,p.emoji FROM orders o
    JOIN order_items oi ON oi.order_id=o.id
    JOIN products p ON p.id=oi.product_id
    WHERE o.customer_id=$uid AND o.status='completed'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Reviews – Floravera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
</head>
<body>
<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <a href="cart.php" class="btn-fv-fill btn-sm" style="text-decoration:none">🛒 Cart</a>
</nav>
<div class="fv-dash-wrap">
  <?php include 'partials/sidebar.php'; ?>
  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">⭐ My Reviews</div></div>
    <div class="fv-dash-content">

      <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <div class="row g-4">
        <!-- Leave a review -->
        <div class="col-lg-5">
          <div class="fv-card">
            <div class="fw-600 mb-3">Write a Review</div>
            <?php if($eligible->num_rows===0): ?>
              <p class="text-muted small">Complete an order first to leave a review.</p>
            <?php else: ?>
            <form method="POST">
              <div class="mb-3">
                <label class="fv-label">Product</label>
                <select name="product_id" class="fv-input" required>
                  <option value="">Select a product...</option>
                  <?php while($e=$eligible->fetch_assoc()): ?>
                  <option value="<?= $e['id'] ?>"><?= $e['emoji'] ?> <?= htmlspecialchars($e['name']) ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="mb-3">
                <label class="fv-label">Rating</label>
                <div class="d-flex gap-2">
                  <?php for($i=1;$i<=5;$i++): ?>
                  <label style="cursor:pointer;font-size:24px;color:#ccc" id="star<?= $i ?>" onclick="setRating(<?= $i ?>)">★</label>
                  <?php endfor; ?>
                  <input type="hidden" name="rating" id="ratingInput" value="0">
                </div>
              </div>
              <div class="mb-3">
                <label class="fv-label">Comment</label>
                <textarea name="comment" class="fv-input" rows="3" placeholder="Share your experience..."></textarea>
              </div>
              <button type="submit" class="btn-fv-auth">Submit Review</button>
            </form>
            <?php endif; ?>
          </div>
        </div>

        <!-- Past reviews -->
        <div class="col-lg-7">
          <div class="fv-card">
            <div class="fw-600 mb-3">My Reviews (<?= $reviews->num_rows ?>)</div>
            <?php if($reviews->num_rows===0): ?>
              <p class="text-muted small">No reviews yet.</p>
            <?php else: ?>
              <?php while($r=$reviews->fetch_assoc()): ?>
              <div class="fv-order-row">
                <div class="fv-order-icon"><?= $r['emoji'] ?></div>
                <div class="flex-grow-1">
                  <div class="fv-order-name"><?= htmlspecialchars($r['product_name']) ?></div>
                  <div class="fv-order-meta"><?= htmlspecialchars($r['shop_name']) ?> · <?= date('M d, Y',strtotime($r['created_at'])) ?></div>
                  <div style="color:#f59e0b;font-size:13px"><?= str_repeat('★',$r['rating']).str_repeat('☆',5-$r['rating']) ?></div>
                  <?php if($r['comment']): ?><div class="text-muted small mt-1">"<?= htmlspecialchars($r['comment']) ?>"</div><?php endif; ?>
                </div>
              </div>
              <?php endwhile; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setRating(n){
  document.getElementById('ratingInput').value=n;
  for(let i=1;i<=5;i++){
    document.getElementById('star'+i).style.color=i<=n?'#f59e0b':'#ccc';
  }
}
</script>
</body></html>
