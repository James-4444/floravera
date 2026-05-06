<?php
session_start();
if(!isset($_SESSION['user_id'])||$_SESSION['role']!=='admin'){header("Location: ../login.php");exit();}
include '../dbconnect.php';
$id=(int)$_GET['id'];
$action=($_GET['action']==='approve')?'approved':'rejected';
$conn->query("UPDATE vendors SET status='$action' WHERE id=$id");
// Also update user role to seller if approved
if($action==='approved'){
    $v=$conn->query("SELECT user_id FROM vendors WHERE id=$id")->fetch_assoc();
    if($v) $conn->query("UPDATE users SET role='seller' WHERE id=".(int)$v['user_id']);
}
header("Location: vendors.php");exit();
