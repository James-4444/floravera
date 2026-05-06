<?php
// cart_update.php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
$id=(int)$_GET['id'];
$action=$_GET['action']??'inc';
if(isset($_SESSION['cart'][$id])){
    if($action==='inc') $_SESSION['cart'][$id]['qty']++;
    else {
        $_SESSION['cart'][$id]['qty']--;
        if($_SESSION['cart'][$id]['qty']<=0) unset($_SESSION['cart'][$id]);
    }
}
header("Location: cart.php");exit();
