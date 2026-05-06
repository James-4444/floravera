<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$uid = (int)$_SESSION['user_id'];

// Check existing application
$vendorRow = $conn->query("SELECT * FROM vendors WHERE user_id=$uid LIMIT 1")->fetch_assoc();

$success = ''; $error = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 0; // 0=intro, 1=form, 2=payment, 3=done

// Handle shop application form submission (Step 1 → 2)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['apply'])){
    $shop_name   = trim($_POST['shop_name']);
    $shop_type   = trim($_POST['shop_type']);
    $city        = trim($_POST['city']);
    $description = trim($_POST['description']);

    if(empty($shop_name)||empty($shop_type)||empty($city)){
        $error = "Shop name, type, and city are required.";
        $step = 1;
    } else {
        if($vendorRow){
            $stmt=$conn->prepare("UPDATE vendors SET shop_name=?,shop_type=?,city=?,description=?,status='payment_pending' WHERE user_id=?");
            $stmt->bind_param("ssssi",$shop_name,$shop_type,$city,$description,$uid);
        } else {
            $stmt=$conn->prepare("INSERT INTO vendors (user_id,shop_name,shop_type,city,description,status) VALUES (?,?,?,?,?,'payment_pending')");
            $stmt->bind_param("issss",$uid,$shop_name,$shop_type,$city,$description);
        }
        if($stmt->execute()){
            header("Location: become_vendor.php?step=2"); exit();
        } else {
            $error = "Submission failed. Please try again.";
            $step = 1;
        }
    }
}

// Handle payment submission (Step 2 → 3)
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['pay'])){
    $payment_method  = trim($_POST['payment_method']);
    $reference_no    = trim($_POST['reference_no']);
    $account_name    = trim($_POST['account_name']);

    $allowed = ['gcash','maya','bpi','bdo','unionbank','landbank','metrobank'];
    if(!in_array($payment_method, $allowed)){
        $error = "Please select a valid payment method.";
        $step = 2;
    } elseif(empty($reference_no)){
        $error = "Please enter your reference/transaction number.";
        $step = 2;
    } else {
        // Save payment record and mark vendor as pending admin approval
        $stmt=$conn->prepare("INSERT INTO vendor_payments (user_id,payment_method,reference_no,account_name,amount,status) VALUES (?,?,?,?,299.00,'pending')");
        $stmt->bind_param("isss",$uid,$payment_method,$reference_no,$account_name);
        $stmt->execute();

        // Update vendor status to pending (awaiting admin confirmation)
        $conn->query("UPDATE vendors SET status='pending' WHERE user_id=$uid");

        header("Location: become_vendor.php?step=3"); exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Become a Vendor – Floravera</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,700;1,600&family=DM+Sans:wght@400;500;600&display=swap" rel="stylesheet">
<link href="../style.css" rel="stylesheet">
<style>
/* ── Card wrapper ─────────────────────────────────────── */
.vendor-step-card{
  background:#fff;
  border:0.5px solid var(--fv-border);
  border-radius:24px;
  padding:40px;
  max-width:620px;
  margin:0 auto;
}

/* ── Step progress bar ───────────────────────────────── */
.step-indicator{display:flex;align-items:center;gap:8px;margin-bottom:28px}
.step-dot{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;flex-shrink:0}
.step-dot.active {background:var(--fv-pink);color:#fff}
.step-dot.done   {background:#16a34a;color:#fff}
.step-dot.inactive{background:#e5e7eb;color:#9ca3af}
.step-line{flex:1;height:2px;background:#e5e7eb;border-radius:2px}
.step-line.done  {background:#16a34a}
.step-line.active{background:var(--fv-pink)}

/* ── Step badge ──────────────────────────────────────── */
.step-badge{
  display:inline-flex;align-items:center;gap:6px;
  background:var(--fv-pink-light);color:var(--fv-pink);
  font-size:11px;font-weight:600;padding:5px 14px;
  border-radius:20px;letter-spacing:.8px;
  text-transform:uppercase;margin-bottom:16px
}

/* ── Benefit list ────────────────────────────────────── */
.benefit-item{display:flex;align-items:flex-start;gap:12px;padding:12px;background:#f9f9f9;border-radius:12px;margin-bottom:10px}
.benefit-icon{font-size:22px;flex-shrink:0}
.benefit-title{font-size:13px;font-weight:600;color:#1a1a1a;margin-bottom:2px}
.benefit-desc{font-size:12px;color:var(--fv-muted)}

/* ── Success / status circles ────────────────────────── */
.success-circle{
  width:80px;height:80px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:36px;margin:0 auto 20px
}
.circle-green {background:#d1fae5}
.circle-yellow{background:#fef3c7}

/* ── Registration fee banner ─────────────────────────── */
.fee-banner{
  background:linear-gradient(135deg,#fff0f6,#ffe4ef);
  border:1px solid #ffc8dc;border-radius:16px;
  padding:20px 24px;margin-bottom:28px;
  display:flex;align-items:center;gap:16px
}
.fee-amount{
  font-family:'Cormorant Garamond',serif;
  font-size:40px;font-weight:700;color:var(--fv-pink);
  line-height:1
}
.fee-label{font-size:13px;font-weight:600;color:#1a1a1a;margin-bottom:2px}
.fee-sub{font-size:11px;color:var(--fv-muted)}
.fee-note{font-size:11px;color:#92400e;background:#fef3c7;border-radius:8px;padding:8px 12px;margin-top:12px}

/* ── Payment method cards ────────────────────────────── */
.pay-methods{display:flex;flex-direction:column;gap:10px;margin-bottom:24px}
.pay-option{
  position:relative;cursor:pointer;
  border:1.5px solid var(--fv-border);
  border-radius:14px;padding:14px 18px;
  transition:all .2s;background:#fff;
  display:flex;align-items:center;gap:14px
}
.pay-option:hover{border-color:var(--fv-pink);background:#fff8fb}
.pay-option input[type=radio]{position:absolute;opacity:0;width:0}
.pay-option.selected{border-color:var(--fv-pink);background:#fff0f5;box-shadow:0 0 0 3px rgba(232,68,122,.1)}
.pay-logo{
  width:48px;height:30px;border-radius:6px;
  display:flex;align-items:center;justify-content:center;
  font-size:18px;font-weight:700;flex-shrink:0
}
.pay-logo.gcash  {background:#007bff;color:#fff;font-size:11px}
.pay-logo.maya   {background:#00b365;color:#fff;font-size:11px}
.pay-logo.bpi    {background:#c0272d;color:#fff;font-size:11px}
.pay-logo.bdo    {background:#002b5b;color:#fff;font-size:11px}
.pay-logo.ub     {background:#e56b25;color:#fff;font-size:10px}
.pay-logo.lb     {background:#006400;color:#fff;font-size:10px}
.pay-logo.metro  {background:#1a3c6b;color:#fff;font-size:10px}
.pay-name{font-size:13px;font-weight:600;color:#1a1a1a}
.pay-desc{font-size:11px;color:var(--fv-muted)}
.pay-check{
  margin-left:auto;width:20px;height:20px;border-radius:50%;
  background:var(--fv-pink);color:#fff;
  display:none;align-items:center;justify-content:center;font-size:11px
}
.pay-option.selected .pay-check{display:flex}

/* ── Payment detail panels ───────────────────────────── */
.pay-detail-panel{
  display:none;
  background:#f9f9f9;border-radius:12px;padding:16px;
  margin-top:-4px;margin-bottom:16px;
  border:1px solid var(--fv-border)
}
.pay-detail-panel.show{display:block}
.qr-placeholder{
  width:120px;height:120px;border-radius:12px;
  background:#e5e7eb;display:flex;align-items:center;
  justify-content:center;font-size:11px;color:#6b7280;
  text-align:center;margin:0 auto 10px;border:2px dashed #d1d5db
}
.copy-row{
  display:flex;align-items:center;gap:8px;
  background:#fff;border:1px solid var(--fv-border);
  border-radius:10px;padding:8px 12px;font-size:13px;
  font-weight:600;margin-bottom:6px
}
.copy-btn{
  margin-left:auto;background:var(--fv-pink);color:#fff;border:none;
  border-radius:7px;padding:4px 10px;font-size:11px;font-weight:600;cursor:pointer
}
</style>
</head>
<body>

<nav class="fv-navbar navbar fixed-top px-4 d-flex align-items-center justify-content-between">
  <a class="fv-logo" href="../index.php">✿ <span>Flora</span>vera</a>
  <a href="cart.php" class="btn-fv-fill btn-sm" style="text-decoration:none">🛒 Cart</a>
</nav>

<div class="fv-dash-wrap">
  <?php include 'partials/sidebar.php'; ?>
  <div class="fv-main">
    <div class="fv-topbar"><div class="fv-topbar-title">🏪 Become a Vendor</div></div>
    <div class="fv-dash-content">


    <!-- ═══════════════════════════════════════════
         STATE: Already pending (submitted + paid)
    ═══════════════════════════════════════════ -->
    <?php if($vendorRow && $vendorRow['status']==='pending' && $step===0): ?>
      <div class="vendor-step-card text-center">
        <div class="success-circle circle-yellow">⏳</div>
        <h4 class="fw-600 mb-2">Application Under Review</h4>
        <p class="text-muted mb-4">Your vendor application for <strong><?= htmlspecialchars($vendorRow['shop_name']) ?></strong> is being reviewed by our team. We'll notify you once it's approved.</p>
        <div class="p-3 mb-4" style="background:#fef3c7;border-radius:12px;font-size:13px;color:#92400e">
          <strong>Status:</strong> Pending approval · Usually reviewed within 1–2 business days.
        </div>
        <a href="dashboard.php" class="btn-fv-fill" style="text-decoration:none;padding:10px 28px;border-radius:12px">← Back to Shopping</a>
      </div>


    <!-- ═══════════════════════════════════════════
         STEP 3 — SUCCESS / DONE
    ═══════════════════════════════════════════ -->
    <?php elseif($step===3): ?>
      <div class="vendor-step-card text-center">
        <div class="step-indicator">
          <div class="step-dot done">✓</div><div class="step-line done"></div>
          <div class="step-dot done">✓</div><div class="step-line done"></div>
          <div class="step-dot done">✓</div>
        </div>
        <div class="success-circle circle-green">✅</div>
        <h4 class="fw-600 mb-2" style="font-size:22px">You're all set!</h4>
        <p class="text-muted mb-4">Your application and registration fee payment have been submitted. Our team will verify your payment and approve your shop within <strong>1–2 business days</strong>.</p>
        <div class="p-3 mb-4" style="background:#d1fae5;border-radius:12px;font-size:13px;color:#065f46">
          ✓ Payment submitted · ✓ Application received · ⏳ Awaiting admin approval
        </div>
        <a href="dashboard.php" class="btn-fv-fill" style="text-decoration:none;padding:12px 32px;border-radius:12px">← Back to Shopping</a>
      </div>


    <!-- ═══════════════════════════════════════════
         STEP 2 — REGISTRATION FEE PAYMENT
    ═══════════════════════════════════════════ -->
    <?php elseif($step===2): ?>
      <div class="vendor-step-card">
        <div class="step-indicator">
          <div class="step-dot done">✓</div><div class="step-line done"></div>
          <div class="step-dot done">✓</div><div class="step-line active"></div>
          <div class="step-dot active">3</div>
        </div>

        <div class="step-badge">Step 3 of 3 · Registration Fee</div>
        <h4 class="fw-600 mb-1" style="font-size:20px">Pay Registration Fee</h4>
        <p class="text-muted small mb-4">A one-time registration fee is required to activate your vendor account on Floravera.</p>

        <!-- Fee Banner -->
        <div class="fee-banner">
          <div>
            <div class="fee-amount">₱299</div>
          </div>
          <div>
            <div class="fee-label">One-time Vendor Registration Fee</div>
            <div class="fee-sub">Non-refundable · No monthly fees · Unlimited products</div>
            <div class="fee-note">⚠ Pay using any of the methods below, then enter your reference number to complete your application.</div>
          </div>
        </div>

        <?php if($error): ?><div class="alert alert-danger small"><?= $error ?></div><?php endif; ?>

        <form method="POST" id="payForm">
          <input type="hidden" name="pay" value="1">

          <!-- Payment Methods -->
          <div class="fw-600 mb-2" style="font-size:13px">Select Payment Method</div>
          <div class="pay-methods">

            <!-- GCash -->
            <div class="pay-option" onclick="selectPay(this,'gcash')" id="opt-gcash">
              <input type="radio" name="payment_method" value="gcash">
              <div class="pay-logo gcash">G</div>
              <div>
                <div class="pay-name">GCash</div>
                <div class="pay-desc">Send to 0917-123-4567 · Name: Floravera PH</div>
              </div>
              <div class="pay-check">✓</div>
            </div>
            <div class="pay-detail-panel" id="detail-gcash">
              <div class="text-center">
                <div class="qr-placeholder">📱 GCash QR<br><span style="font-size:9px">Upload your QR here</span></div>
                <div style="font-size:12px;color:var(--fv-muted);margin-bottom:10px">Or send directly to:</div>
              </div>
              <div class="copy-row">📱 0917-123-4567 <button type="button" class="copy-btn" onclick="copyText('0917-123-4567')">Copy</button></div>
              <div class="copy-row">👤 Floravera PH</div>
              <div style="font-size:11px;color:#6b7280;margin-top:8px">Send exactly <strong>₱299.00</strong> and save your reference number.</div>
            </div>

            <!-- Maya -->
            <div class="pay-option" onclick="selectPay(this,'maya')" id="opt-maya">
              <input type="radio" name="payment_method" value="maya">
              <div class="pay-logo maya">M</div>
              <div>
                <div class="pay-name">Maya (PayMaya)</div>
                <div class="pay-desc">Send to 0961-234-5678 · Name: Floravera PH</div>
              </div>
              <div class="pay-check">✓</div>
            </div>
            <div class="pay-detail-panel" id="detail-maya">
              <div class="copy-row">📱 0961-234-5678 <button type="button" class="copy-btn" onclick="copyText('0961-234-5678')">Copy</button></div>
              <div class="copy-row">👤 Floravera PH</div>
              <div style="font-size:11px;color:#6b7280;margin-top:8px">Send exactly <strong>₱299.00</strong> and save your reference number.</div>
            </div>

            <!-- BPI -->
            <div class="pay-option" onclick="selectPay(this,'bpi')" id="opt-bpi">
              <input type="radio" name="payment_method" value="bpi">
              <div class="pay-logo bpi">BPI</div>
              <div>
                <div class="pay-name">BPI Bank Transfer</div>
                <div class="pay-desc">Online banking or BPI app · Instapay available</div>
              </div>
              <div class="pay-check">✓</div>
            </div>
            <div class="pay-detail-panel" id="detail-bpi">
              <div class="copy-row">🏦 Account No: 1234-5678-90 <button type="button" class="copy-btn" onclick="copyText('1234567890')">Copy</button></div>
              <div class="copy-row">👤 Floravera Marketplace Inc.</div>
              <div class="copy-row">🏷 BPI · Savings Account</div>
              <div style="font-size:11px;color:#6b7280;margin-top:8px">Use <strong>InstaPay</strong> or <strong>PESONet</strong>. Amount: <strong>₱299.00</strong>.</div>
            </div>

            <!-- BDO -->
            <div class="pay-option" onclick="selectPay(this,'bdo')" id="opt-bdo">
              <input type="radio" name="payment_method" value="bdo">
              <div class="pay-logo bdo">BDO</div>
              <div>
                <div class="pay-name">BDO Bank Transfer</div>
                <div class="pay-desc">Online banking or BDO app · Instapay available</div>
              </div>
              <div class="pay-check">✓</div>
            </div>
            <div class="pay-detail-panel" id="detail-bdo">
              <div class="copy-row">🏦 Account No: 0098-7654-321 <button type="button" class="copy-btn" onclick="copyText('009876543210')">Copy</button></div>
              <div class="copy-row">👤 Floravera Marketplace Inc.</div>
              <div class="copy-row">🏷 BDO · Savings Account</div>
              <div style="font-size:11px;color:#6b7280;margin-top:8px">Use <strong>InstaPay</strong> or <strong>PESONet</strong>. Amount: <strong>₱299.00</strong>.</div>
            </div>

            <!-- UnionBank -->
            <div class="pay-option" onclick="selectPay(this,'unionbank')" id="opt-unionbank">
              <input type="radio" name="payment_method" value="unionbank">
              <div class="pay-logo ub">UB</div>
              <div>
                <div class="pay-name">UnionBank Online</div>
                <div class="pay-desc">Transfer via UnionBank app · InstaPay</div>
              </div>
              <div class="pay-check">✓</div>
            </div>
            <div class="pay-detail-panel" id="detail-unionbank">
              <div class="copy-row">🏦 Account No: 1111-2222-3333 <button type="button" class="copy-btn" onclick="copyText('111122223333')">Copy</button></div>
              <div class="copy-row">👤 Floravera Marketplace Inc.</div>
              <div style="font-size:11px;color:#6b7280;margin-top:8px">Use <strong>InstaPay</strong>. Amount: <strong>₱299.00</strong>.</div>
            </div>

            <!-- Landbank -->
            <div class="pay-option" onclick="selectPay(this,'landbank')" id="opt-landbank">
              <input type="radio" name="payment_method" value="landbank">
              <div class="pay-logo lb">LBP</div>
              <div>
                <div class="pay-name">Landbank (LBP)</div>
                <div class="pay-desc">Transfer via iAccess or Landbank app</div>
              </div>
              <div class="pay-check">✓</div>
            </div>
            <div class="pay-detail-panel" id="detail-landbank">
              <div class="copy-row">🏦 Account No: 4444-5555-6666 <button type="button" class="copy-btn" onclick="copyText('444455556666')">Copy</button></div>
              <div class="copy-row">👤 Floravera Marketplace Inc.</div>
              <div style="font-size:11px;color:#6b7280;margin-top:8px">Use <strong>iAccess</strong> or <strong>LinkBiz</strong>. Amount: <strong>₱299.00</strong>.</div>
            </div>

            <!-- Metrobank -->
            <div class="pay-option" onclick="selectPay(this,'metrobank')" id="opt-metrobank">
              <input type="radio" name="payment_method" value="metrobank">
              <div class="pay-logo metro">Metro</div>
              <div>
                <div class="pay-name">Metrobank</div>
                <div class="pay-desc">Transfer via Metrobank online or app</div>
              </div>
              <div class="pay-check">✓</div>
            </div>
            <div class="pay-detail-panel" id="detail-metrobank">
              <div class="copy-row">🏦 Account No: 7777-8888-9999 <button type="button" class="copy-btn" onclick="copyText('777788889999')">Copy</button></div>
              <div class="copy-row">👤 Floravera Marketplace Inc.</div>
              <div style="font-size:11px;color:#6b7280;margin-top:8px">Use <strong>Metrobank Online</strong>. Amount: <strong>₱299.00</strong>.</div>
            </div>

          </div><!-- /pay-methods -->

          <!-- Reference Number Entry -->
          <div id="refSection" style="display:none">
            <hr style="border-color:#f0e6ea;margin-bottom:20px">
            <div class="fw-600 mb-3" style="font-size:13px">Confirm Your Payment</div>

            <div class="mb-3">
              <label class="fv-label">Transaction / Reference Number <span class="text-danger">*</span></label>
              <input type="text" name="reference_no" id="reference_no" class="fv-input"
                     placeholder="e.g. 1234567890 or GC-XXXXX"
                     pattern="[A-Za-z0-9\-]+" title="Letters, numbers, and hyphens only" required>
              <div class="text-muted small mt-1">Found in your payment app under "Transaction History" or your SMS receipt.</div>
            </div>

            <div class="mb-4">
              <label class="fv-label">Account Name Used for Payment <span class="text-danger">*</span></label>
              <input type="text" name="account_name" class="fv-input"
                     placeholder="Full name on your GCash/bank account" required>
              <div class="text-muted small mt-1">This helps us verify your payment quickly.</div>
            </div>

            <div class="p-3 mb-4" style="background:#fff8e1;border-radius:12px;border:1px solid #ffe082;font-size:12px;color:#5d4037">
              ⚠️ <strong>Important:</strong> Make sure you've already sent <strong>₱299.00</strong> before submitting. Fake or unverified references will result in rejection. Your shop will only be activated after admin verifies your payment.
            </div>

            <div class="d-flex gap-2">
              <a href="become_vendor.php?step=1" class="btn-fv-outline" style="padding:10px 20px;border-radius:12px;text-decoration:none;font-size:14px">← Back</a>
              <button type="submit" class="btn-fv-auth flex-grow-1" style="font-size:15px">
                ✓ Submit Payment & Application
              </button>
            </div>
          </div>

        </form>
      </div><!-- /vendor-step-card -->


    <!-- ═══════════════════════════════════════════
         STEP 1 — SHOP APPLICATION FORM
    ═══════════════════════════════════════════ -->
    <?php elseif($step===1 || isset($_POST['agree'])): ?>
      <div class="vendor-step-card">
        <div class="step-indicator">
          <div class="step-dot done">✓</div><div class="step-line done"></div>
          <div class="step-dot active">2</div><div class="step-line"></div>
          <div class="step-dot inactive">3</div>
        </div>

        <div class="step-badge">Step 2 of 3</div>
        <h4 class="fw-600 mb-1">Tell us about your shop</h4>
        <p class="text-muted small mb-4">Fill in your shop details. After submitting, you'll be directed to pay the registration fee.</p>

        <?php if($error): ?><div class="alert alert-danger small"><?= $error ?></div><?php endif; ?>

        <form method="POST">
          <input type="hidden" name="apply" value="1">
          <div class="mb-3">
            <label class="fv-label">Shop Name <span class="text-danger">*</span></label>
            <input type="text" name="shop_name" class="fv-input" placeholder="e.g. Maria's Flower Garden"
                   value="<?= htmlspecialchars($_POST['shop_name'] ?? $vendorRow['shop_name'] ?? '') ?>" required>
          </div>
          <div class="mb-3">
            <label class="fv-label">Shop Type <span class="text-danger">*</span></label>
            <select name="shop_type" class="fv-input" required>
              <option value="">Select shop type...</option>
              <?php foreach(['Florist','Handicrafts','Gift Shop','Bouquet Maker','Mixed (Flowers & Crafts)'] as $t): ?>
              <option value="<?= $t ?>" <?= (($_POST['shop_type'] ?? $vendorRow['shop_type'] ?? '')===$t)?'selected':'' ?>><?= $t ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="fv-label">City / Location <span class="text-danger">*</span></label>
            <input type="text" name="city" class="fv-input" placeholder="e.g. Davao City"
                   value="<?= htmlspecialchars($_POST['city'] ?? $vendorRow['city'] ?? 'Davao City') ?>" required>
          </div>
          <div class="mb-4">
            <label class="fv-label">Shop Description</label>
            <textarea name="description" class="fv-input" rows="4"
                      placeholder="Tell customers what you sell, your specialties, delivery options, etc."><?= htmlspecialchars($_POST['description'] ?? $vendorRow['description'] ?? '') ?></textarea>
          </div>
          <div class="d-flex gap-2">
            <a href="become_vendor.php" class="btn-fv-outline" style="padding:10px 20px;border-radius:12px;text-decoration:none;font-size:14px">← Back</a>
            <button type="submit" class="btn-fv-auth flex-grow-1">Next: Pay Registration Fee →</button>
          </div>
        </form>
      </div>


    <!-- ═══════════════════════════════════════════
         STEP 0 — INTRO / AGREE
    ═══════════════════════════════════════════ -->
    <?php else: ?>
      <div class="vendor-step-card">
        <div class="step-indicator">
          <div class="step-dot active">1</div><div class="step-line"></div>
          <div class="step-dot inactive">2</div><div class="step-line"></div>
          <div class="step-dot inactive">3</div>
        </div>

        <div class="step-badge">Step 1 of 3</div>
        <h4 class="fw-600 mb-1" style="font-family:'Cormorant Garamond',serif;font-size:26px">Sell on Floravera 🌸</h4>
        <p class="text-muted mb-4">Join hundreds of Davao City florists and artisans on Floravera's local marketplace.</p>

        <div class="mb-4">
          <div class="benefit-item">
            <div class="benefit-icon">🌿</div>
            <div><div class="benefit-title">Reach more customers</div><div class="benefit-desc">Get discovered by thousands of Davao shoppers looking for local flowers and crafts.</div></div>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">📦</div>
            <div><div class="benefit-title">Manage your shop easily</div><div class="benefit-desc">Add products, track orders, and manage your shop all from one dashboard.</div></div>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">⭐</div>
            <div><div class="benefit-title">Build your reputation</div><div class="benefit-desc">Collect reviews and ratings to grow customer trust and repeat buyers.</div></div>
          </div>
          <div class="benefit-item">
            <div class="benefit-icon">💳</div>
            <div>
              <div class="benefit-title">One-time Registration Fee: <span style="color:var(--fv-pink)">₱299</span></div>
              <div class="benefit-desc">A one-time, non-refundable fee via GCash, Maya, or bank transfer. No monthly charges.</div>
            </div>
          </div>
        </div>

        <div class="p-3 mb-4" style="background:#f9fafb;border-radius:12px;font-size:12px;color:var(--fv-muted);border:0.5px solid var(--fv-border)">
          By clicking "I want to become a vendor" you agree to Floravera's vendor terms, and that you will only list genuine handmade or locally-sourced products available in Davao City. The ₱299 registration fee is non-refundable.
        </div>

        <form method="POST">
          <input type="hidden" name="agree" value="1">
          <button type="submit"
                  onclick="this.form.action='become_vendor.php?step=1'; return true;"
                  class="btn-fv-auth w-100" style="font-size:15px;padding:13px">
            I want to become a vendor →
          </button>
        </form>
        <a href="dashboard.php" class="d-block text-center mt-3 small text-muted">← Not now, back to shopping</a>
      </div>
    <?php endif; ?>

    </div><!-- /fv-dash-content -->
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Payment method selection
function selectPay(el, method) {
  // Deselect all
  document.querySelectorAll('.pay-option').forEach(o => o.classList.remove('selected'));
  document.querySelectorAll('.pay-detail-panel').forEach(p => p.classList.remove('show'));

  // Select this one
  el.classList.add('selected');
  el.querySelector('input[type=radio]').checked = true;

  // Show detail panel
  const panel = document.getElementById('detail-' + method);
  if(panel) panel.classList.add('show');

  // Show reference section
  document.getElementById('refSection').style.display = 'block';

  // Scroll to ref section
  setTimeout(()=>{
    document.getElementById('refSection').scrollIntoView({behavior:'smooth', block:'nearest'});
  }, 200);
}

// Copy to clipboard
function copyText(text) {
  navigator.clipboard.writeText(text).then(() => {
    // Quick toast
    const toast = document.createElement('div');
    toast.textContent = '✓ Copied!';
    toast.style.cssText = 'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:#1a1a1a;color:#fff;padding:8px 20px;border-radius:20px;font-size:13px;z-index:9999;font-family:DM Sans,sans-serif';
    document.body.appendChild(toast);
    setTimeout(()=>toast.remove(), 2000);
  });
}
</script>
</body>
</html>