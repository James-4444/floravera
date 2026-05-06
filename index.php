<?php
session_start();
include 'dbconnect.php';

// Fetch products for landing grid
$products = $conn->query("SELECT p.*, v.shop_name FROM products p JOIN vendors v ON p.vendor_id = v.id ORDER BY p.id LIMIT 8");
?>
<?php include 'header.php'; ?>

<!-- HERO -->
<section class="fv-hero">
  <div class="hero-bg-blob"></div>
  <?php if(isset($_GET['loggedout'])): ?>
  <div style="position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:100;background:#d1fae5;color:#065f46;padding:10px 24px;border-radius:30px;font-size:13px;font-weight:500;box-shadow:0 4px 16px rgba(0,0,0,.1)" id="logoutToast">
    ✓ You've been logged out successfully.
  </div>
  <script>setTimeout(()=>{const t=document.getElementById('logoutToast');if(t)t.style.display='none'},3500)</script>
  <?php endif; ?>
  <div class="container position-relative" style="z-index:2">
    <div class="row align-items-center">
      <div class="col-lg-6 fade-up">
        <div class="hero-tag">✿ Davao City's Premier Marketplace</div>
        <h1 class="hero-h1">Davao's Local<br>Flower &amp; Craft<br><em>Marketplace</em></h1>
        <p class="hero-sub">Discover fresh floral arrangements and handcrafted products from local artisans and florists in Davao City — all in one place.</p>
        <div class="d-flex gap-3 flex-wrap mb-5">
          <a href="<?= isset($_SESSION['user_id']) ? 'customer/dashboard.php' : 'login.php' ?>" class="btn-hero-main">Shop Now</a>
          <?php
            if(!isset($_SESSION['user_id'])){
                echo '<a href="register.php" class="btn-hero-sec">Become a Vendor</a>';
            } else {
                $uid = (int)$_SESSION['user_id'];
                $vRow = $conn->query("SELECT status FROM vendors WHERE user_id=$uid LIMIT 1")->fetch_assoc();
                if(!$vRow){
                    echo '<a href="customer/become_vendor.php" class="btn-hero-sec">Become a Vendor</a>';
                }
            }
          ?>
        </div>
        <div class="d-flex gap-4">
          <div><div class="hero-stat-num">120+</div><div class="hero-stat-label">Local vendors</div></div>
          <div><div class="hero-stat-num">2.4K</div><div class="hero-stat-label">Happy customers</div></div>
          <div><div class="hero-stat-num">4.8★</div><div class="hero-stat-label">Average rating</div></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- WHY FLORAVERA -->
<section class="fv-section" id="why">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag">Why Floravera?</div>
      <h2 class="section-h2">Supporting local Davao florists<br>and handicraft makers</h2>
    </div>
    <div class="row g-4">
      <div class="col-sm-6 col-lg-3"><div class="why-card"><div class="why-icon">🌿</div><h6 class="fw-600">Local vendors only</h6><p class="text-muted small mb-0">Registered Davao City florists and artisans — curated and verified.</p></div></div>
      <div class="col-sm-6 col-lg-3"><div class="why-card"><div class="why-icon">🛒</div><h6 class="fw-600">Easy ordering</h6><p class="text-muted small mb-0">Browse, select, and order in just a few clicks.</p></div></div>
      <div class="col-sm-6 col-lg-3"><div class="why-card"><div class="why-icon">⭐</div><h6 class="fw-600">Ratings &amp; reviews</h6><p class="text-muted small mb-0">Honest feedback from real customers every time.</p></div></div>
      <div class="col-sm-6 col-lg-3"><div class="why-card"><div class="why-icon">🎨</div><h6 class="fw-600">Handcrafted goods</h6><p class="text-muted small mb-0">Unique local handicrafts alongside fresh flowers.</p></div></div>
    </div>
  </div>
</section>

<!-- FEATURED VENDORS -->
<section class="fv-vendors-dark" id="vendors">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag">Featured vendors</div>
      <h2 class="section-h2 text-white">Top-rated shops in Davao City</h2>
      <p class="text-white-50 mt-2">Discover our most loved local sellers</p>
    </div>
    <?php
    $vendors = $conn->query("SELECT v.*, u.fullname FROM vendors v JOIN users u ON v.user_id=u.id WHERE v.status='approved' LIMIT 4");
    $emojis = ['🌹','🎁','🌻','🪴'];
    $ei = 0;
    ?>
    <div class="row g-3">
      <?php while($v = $vendors->fetch_assoc()): ?>
      <div class="col-sm-6 col-lg-3">
        <div class="vendor-card-dark">
          <div class="vendor-avatar-d"><?= $emojis[$ei++ % 4] ?></div>
          <div class="text-white fw-500"><?= htmlspecialchars($v['shop_name']) ?></div>
          <div class="text-white-50 small mb-2"><?= htmlspecialchars($v['shop_type']) ?> · <?= htmlspecialchars($v['city']) ?></div>
          <div style="color:#f59e0b;font-size:13px">★★★★☆ 4.7</div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- BROWSE PRODUCTS -->
<section class="fv-section" id="products" style="background:var(--fv-cream2)">
  <div class="container">
    <div class="text-center mb-4">
      <div class="section-tag">Browse products</div>
      <h2 class="section-h2">Flowers and handicrafts from local Davao makers</h2>
    </div>

    <!-- Category filter chips (JS filter) -->
    <div class="d-flex gap-2 flex-wrap justify-content-center mb-4" id="landingFilters">
      <span class="fv-chip active" data-cat="all">All</span>
      <span class="fv-chip" data-cat="flowers">Flowers</span>
      <span class="fv-chip" data-cat="bouquets">Bouquets</span>
      <span class="fv-chip" data-cat="handicrafts">Handicrafts</span>
      <span class="fv-chip" data-cat="giftsets">Gift Sets</span>
    </div>

    <div class="row g-3" id="landingGrid">
      <?php
      $conn->query("SELECT p.*, v.shop_name FROM products p JOIN vendors v ON p.vendor_id=v.id ORDER BY p.id LIMIT 12");
      $allProds = $conn->query("SELECT p.*, v.shop_name FROM products p JOIN vendors v ON p.vendor_id=v.id ORDER BY p.id LIMIT 12");
      while($p = $allProds->fetch_assoc()):
        $bg = ['flowers'=>'#fce4ef','bouquets'=>'#fffde7','handicrafts'=>'#e8f5e9','giftsets'=>'#fff3e0'][$p['category']] ?? '#f3f4f6';
      ?>
      <div class="col-6 col-sm-4 col-md-3 landing-card" data-cat="<?= $p['category'] ?>">
        <div class="fv-product-card h-100">
          <div class="fv-product-img" style="background:<?= $bg ?>">
            <?= $p['emoji'] ?>
            <?php if($p['badge']): ?>
              <span class="position-absolute top-0 start-0 m-2 badge-<?= $p['badge'] ?>-pill"><?= strtoupper($p['badge']) ?></span>
            <?php endif; ?>
          </div>
          <div class="p-3">
            <div class="fw-600 mb-1" style="font-size:14px"><?= htmlspecialchars($p['name']) ?></div>
            <div class="text-muted small mb-2"><?= htmlspecialchars($p['shop_name']) ?></div>
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="fv-price">₱<?= number_format($p['price'],2) ?></span>
              <span class="fv-stars">★★★★☆</span>
            </div>
            <?php if(isset($_SESSION['user_id'])): ?>
              <a href="customer/cart_add.php?id=<?= $p['id'] ?>" class="btn-add-cart">+ Add to cart</a>
            <?php else: ?>
              <a href="login.php" class="btn-add-cart">Login to buy</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>

<!-- CTA BANNER -->
<section class="fv-cta text-center" id="about">
  <div class="container">
    <h2 class="text-white mb-3" style="font-family:'Cormorant Garamond',serif;font-size:clamp(28px,4vw,44px);font-weight:700">Are you a Davao florist or artisan?</h2>
    <p class="text-white-75 mb-4">Join Floravera and reach more customers — create your vendor shop for free.</p>
    <?php
      if(!isset($_SESSION['user_id'])){
          echo '<a href="register.php" class="btn btn-light px-4 py-2 fw-600" style="border-radius:40px;color:var(--fv-pink)">Register as a vendor</a>';
      } else {
          $uid2 = (int)$_SESSION['user_id'];
          $vRow2 = $conn->query("SELECT status FROM vendors WHERE user_id=$uid2 LIMIT 1")->fetch_assoc();
          if(!$vRow2){
              echo '<a href="customer/become_vendor.php" class="btn btn-light px-4 py-2 fw-600" style="border-radius:40px;color:var(--fv-pink)">Register as a vendor</a>';
          }
      }
    ?>
  </div>
</section>

<?php include 'footer.php'; ?>

<script>
// Landing category filter (JS, no page reload)
document.querySelectorAll('#landingFilters .fv-chip').forEach(chip => {
  chip.addEventListener('click', function(){
    document.querySelectorAll('#landingFilters .fv-chip').forEach(c=>c.classList.remove('active'));
    this.classList.add('active');
    const cat = this.dataset.cat;
    document.querySelectorAll('.landing-card').forEach(card=>{
      card.style.display = (cat==='all' || card.dataset.cat===cat) ? '' : 'none';
    });
  });
});
</script>
