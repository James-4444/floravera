<?php
// browse.php - redirect helper
session_start();
if(isset($_SESSION['user_id'])){
    header("Location: customer/dashboard.php");
} else {
    header("Location: login.php?redirect=customer/dashboard.php");
}
exit();
