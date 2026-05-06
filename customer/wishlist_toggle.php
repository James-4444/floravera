<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$uid=(int)$_SESSION['user_id'];
$pid=(int)$_GET['id'];

$check=$conn->query("SELECT id FROM wishlist WHERE customer_id=$uid AND product_id=$pid")->fetch_assoc();
if($check){
    $conn->query("DELETE FROM wishlist WHERE customer_id=$uid AND product_id=$pid");
} else {
    $conn->query("INSERT INTO wishlist (customer_id,product_id) VALUES ($uid,$pid)");
}
header("Location: ".(isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:'dashboard.php'));
exit();
