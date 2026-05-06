<?php
session_start();
include 'dbconnect.php';

// Capture redirect param (from Browse link or other protected pages)
$redirect = isset($_GET['redirect']) ? trim($_GET['redirect']) : '';
// Whitelist only internal paths to prevent open redirect
$allowed = ['customer/dashboard.php','customer/orders.php','customer/wishlist.php','customer/reviews.php','customer/profile.php'];
if(!in_array($redirect, $allowed)) $redirect = '';

if(isset($_SESSION['user_id'])){
    $dest = $redirect ?: ($_SESSION['role']==='admin' ? 'admin/dashboard.php' : 'customer/dashboard.php');
    header("Location: $dest"); exit();
}

$error = '';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    $redir = trim($_POST['redirect'] ?? '');
    if(!in_array($redir,$allowed)) $redir='';

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=?");
    $stmt->bind_param("ss",$u,$u);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if(!$row){
        $error = "User not found.";
    } elseif(!password_verify($p,$row['password'])){
        $error = "Incorrect password.";
    } else {
        $_SESSION['user_id']  = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['fullname'] = $row['fullname'];
        $_SESSION['role']     = $row['role'];
        if($row['role']==='admin')      { header("Location: admin/dashboard.php"); exit(); }
        elseif($row['role']==='seller') { header("Location: customer/dashboard.php"); exit(); }
        else {
            $dest = $redir ?: 'customer/dashboard.php';
            header("Location: $dest"); exit();
        }
    }
}

$pageTitle = "Login";
?>
<?php include 'header.php'; ?>

<div class="fv-auth-wrap">
  <div class="fv-auth-card">
    <div class="text-center mb-4">
      <div class="fv-auth-logo">✿ <span>Flora</span>vera</div>
      <div class="text-muted small mt-1">Fresh flowers delivered with love</div>
    </div>

    <div class="d-flex fv-tabs mb-4">
      <span class="fv-tab active">Login</span>
      <a href="register.php" class="fv-tab">Register</a>
    </div>

    <?php if($error): ?>
      <div class="alert alert-danger py-2 small"><?= $error ?></div>
    <?php endif; ?>
    <?php if(isset($_GET['registered'])): ?>
      <div class="alert alert-success py-2 small">Account created! Please log in.</div>
    <?php endif; ?>
    <?php if($redirect): ?>
      <div class="alert alert-info py-2 small">Please log in to continue.</div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
      <div class="mb-3">
        <label class="fv-label">Username or Email</label>
        <input type="text" name="username" class="fv-input" placeholder="Enter username or email" required>
      </div>
      <div class="mb-3">
        <label class="fv-label">Password</label>
        <input type="password" name="password" class="fv-input" placeholder="Enter password" required>
      </div>
      <button type="submit" class="btn-fv-auth mt-1">Login to Floravera</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">No account? <a href="register.php" style="color:var(--fv-pink);font-weight:500">Register here</a></p>
  </div>
</div>

<?php include 'footer.php'; ?>
