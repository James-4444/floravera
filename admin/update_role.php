<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$id=(int)$_POST['id'];
$role=in_array($_POST['role'],['admin','seller','customer'])?$_POST['role']:'customer';
$stmt=$conn->prepare("UPDATE users SET role=? WHERE id=?");
$stmt->bind_param("si",$role,$id);
$stmt->execute();
header("Location: users.php");exit();
