<?php
session_start();
if (!isset($_SESSION['logged_in'])) 
    
    { header("Location: retailer_login.php"); exit(); }

$db = new mysqli('localhost','root','','agromatiDB'); 

if ($db->connect_error) die($db->connect_error);

if (!isset($_POST['cart_item_id'], $_POST['quantity'])) { header("Location: cart.php"); exit(); }
$cid = (int)$_POST['cart_item_id']; $qty = max(1,(int)$_POST['quantity']);

$stmt = $db->prepare("SELECT p.total_units FROM cart_items ci JOIN products p ON p.id=ci.product_id WHERE ci.id=?");
$stmt->bind_param("i",$cid); $stmt->execute(); $stockRow=$stmt->get_result()->fetch_assoc(); $stmt->close();
if(!$stockRow){ header("Location: cart.php"); exit(); }
if ($qty > (int)$stockRow['total_units']) { $_SESSION['cart_error']="Quantity exceeds stock."; header("Location: cart.php"); exit(); }

$stmt = $db->prepare("UPDATE cart_items SET quantity=? WHERE id=?");
$stmt->bind_param("ii",$qty,$cid); $stmt->execute(); $stmt->close();
header("Location: cart.php");
