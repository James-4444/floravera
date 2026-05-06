<?php
// cart_add.php – Add product to session cart
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}

$id = (int)$_GET['id'];
if($id > 0){
    if(!isset($_SESSION['cart'][$id])){
        $_SESSION['cart'][$id] = ['qty'=>1];
    } else {
        $_SESSION['cart'][$id]['qty']++;
    }
}
header("Location: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'dashboard.php'));
exit();
