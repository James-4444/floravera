<?php
session_start();
if(!isset($_SESSION['user_id'])){header("Location: ../login.php");exit();}
$id=(int)$_GET['id'];
unset($_SESSION['cart'][$id]);
header("Location: cart.php");exit();
