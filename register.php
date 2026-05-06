<?php
session_start();
include 'dbconnect.php';

$errors = [];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $fullname = trim($_POST['fullname']);
    $email    = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirmpassword'];

    if(empty($fullname)||empty($email)||empty($username)||empty($password))
        $errors[] = "All fields are required.";
    elseif(!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format.";
    elseif($password !== $confirm)
        $errors[] = "Passwords do not match.";
    elseif(strlen($password) < 6)
        $errors[] = "Password must be at least 6 characters.";

    if(empty($errors)){
        // Check for duplicate email
        $chkEmail = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $chkEmail->bind_param("s", $email);
        $chkEmail->execute();
        $chkEmail->store_result();
        if($chkEmail->num_rows > 0)
            $errors[] = "This email address is already registered. Please use a different email.";
        $chkEmail->close();
    }

    if(empty($errors)){
        // Check for duplicate username
        $chkUser = $conn->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $chkUser->bind_param("s", $username);
        $chkUser->execute();
        $chkUser->store_result();
        if($chkUser->num_rows > 0)
            $errors[] = "This username is already taken. Please choose a different username.";
        $chkUser->close();
    }

    if(empty($errors)){
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $role   = 'customer';
        $stmt = $conn->prepare("INSERT INTO users (fullname,email,username,password,role) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss",$fullname,$email,$username,$hashed,$role);
        if($stmt->execute()){
            header("Location: login.php?registered=1");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}

$pageTitle = "Register";
?>
<?php include 'header.php'; ?>

<div class="fv-auth-wrap">
  <div class="fv-auth-card">
    <div class="text-center mb-4">
      <div class="fv-auth-logo">✿ <span>Flora</span>vera</div>
      <div class="text-muted small mt-1">Create your flower shop account</div>
    </div>

    <div class="d-flex fv-tabs mb-4">
      <a href="login.php" class="fv-tab">Login</a>
      <span class="fv-tab active">Register</span>
    </div>

    <?php if(!empty($errors)): ?>
      <div class="alert alert-danger py-2 small"><?= implode('<br>',$errors) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="mb-3">
        <label class="fv-label">Full Name</label>
        <input type="text" name="fullname" class="fv-input" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="fv-label">Email</label>
        <input type="email" name="email" class="fv-input" placeholder="your@email.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="fv-label">Username</label>
        <input type="text" name="username" class="fv-input" placeholder="Choose a username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="mb-3">
        <label class="fv-label">Password</label>
        <input type="password" name="password" class="fv-input" placeholder="Min. 6 characters" required>
      </div>
      <div class="mb-3">
        <label class="fv-label">Confirm Password</label>
        <input type="password" name="confirmpassword" class="fv-input" placeholder="Repeat password" required>
      </div>
      <button type="submit" class="btn-fv-auth mt-1">Create Account</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">Already have an account? <a href="login.php" style="color:var(--fv-pink);font-weight:500">Login</a></p>
  </div>
</div>

<?php include 'footer.php'; ?>
