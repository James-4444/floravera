<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$uid=(int)$_SESSION['user_id'];
$user=$conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$success=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])){
    $fullname=trim($_POST['fullname']);
    $email=trim($_POST['email']);
    if(empty($fullname)||empty($email)){ $error="All fields are required."; }
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){ $error="Invalid email."; }
    else {
        $stmt=$conn->prepare("UPDATE users SET fullname=?,email=? WHERE id=?");
        $stmt->bind_param("ssi",$fullname,$email,$uid);
        if($stmt->execute()){ $_SESSION['fullname']=$fullname; $success="Profile updated!"; $user['fullname']=$fullname; $user['email']=$email; }
        else { $error="Update failed."; }
    }
}
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['change_password'])){
    $current=$_POST['current_password'];
    $new=$_POST['new_password'];
    $conf=$_POST['confirm_password'];
    if(!password_verify($current,$user['password'])){ $error="Current password is incorrect."; }
    elseif($new!==$conf){ $error="New passwords do not match."; }
    elseif(strlen($new)<6){ $error="Password must be at least 6 characters."; }
    else {
        $hashed=password_hash($new,PASSWORD_DEFAULT);
        $stmt=$conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si",$hashed,$uid);
        if($stmt->execute()) $success="Password changed successfully!";
        else $error="Update failed.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>My Profile – Floravera</title>
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
    <div class="fv-topbar"><div class="fv-topbar-title">👤 My Profile</div></div>
    <div class="fv-dash-content">

      <?php if($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
      <?php if($error):   ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

      <div class="row g-4">
        <div class="col-lg-6">
          <div class="fv-card mb-4">
            <div class="fw-600 mb-3">Account Details</div>
            <form method="POST">
              <div class="mb-3">
                <label class="fv-label">Full Name</label>
                <input type="text" name="fullname" class="fv-input" value="<?= htmlspecialchars($user['fullname']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="fv-label">Email</label>
                <input type="email" name="email" class="fv-input" value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>
              <div class="mb-3">
                <label class="fv-label">Username</label>
                <input type="text" class="fv-input" value="<?= htmlspecialchars($user['username']) ?>" disabled style="opacity:.6">
                <div class="text-muted small mt-1">Username cannot be changed.</div>
              </div>
              <button type="submit" name="update_profile" class="btn-fv-auth">Save Changes</button>
            </form>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="fv-card">
            <div class="fw-600 mb-3">🔒 Change Password</div>
            <form method="POST">
              <div class="mb-3">
                <label class="fv-label">Current Password</label>
                <input type="password" name="current_password" class="fv-input" placeholder="Your current password" required>
              </div>
              <div class="mb-3">
                <label class="fv-label">New Password</label>
                <input type="password" name="new_password" class="fv-input" placeholder="Min. 6 characters" required>
              </div>
              <div class="mb-3">
                <label class="fv-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="fv-input" placeholder="Repeat new password" required>
              </div>
              <button type="submit" name="change_password" class="btn-fv-auth">Update Password</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body></html>
