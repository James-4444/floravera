<?php
// delete_user.php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$id=(int)$_GET['id'];
if($id && $id!==$_SESSION['user_id']){
    $conn->query("DELETE FROM users WHERE id=$id");
}
header("Location: users.php?deleted=1");exit();
