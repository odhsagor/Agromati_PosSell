<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: retailer_login.php"); exit(); }
if (!isset($_POST['product_id'])) { header("Location: pos_cart.php"); exit(); }

$pid = (int)$_POST['product_id'];
if (!isset($_SESSION['pos_cart'][$pid])) { header("Location: pos_cart.php"); exit(); }

$db = new mysqli('localhost','root','','agromatiDB'); if ($db->connect_error) die($db->connect_error);

$stmt = $db->prepare("SELECT id FROM retailers WHERE retailer_id=? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']); $stmt->execute();
$ret = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$ret) { $_SESSION['pos_err']="Retailer not found."; header("Location: pos_cart.php"); exit(); }
$retailer_id = (int)$ret['id'];

$stmt = $db->prepare("SELECT qty_available FROM retailer_inventory WHERE retailer_id=? AND product_id=?");
$stmt->bind_param("ii",$retailer_id,$pid); $stmt->execute();
$inv = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$inv) { $_SESSION['pos_err']="No inventory for item."; header("Location: pos_cart.php"); exit(); }

$line =& $_SESSION['pos_cart'][$pid];

if (isset($_POST['quantity'])) {
  $qty = max(1,(int)$_POST['quantity']);
  if ($qty > (int)$inv['qty_available']) { $_SESSION['pos_err']="Qty exceeds stock."; }
  else { $line['qty'] = $qty; $_SESSION['pos_msg']="Quantity updated."; }
}

if (isset($_POST['unit_price'])) {
  $price = round(max(0.01,(float)$_POST['unit_price']),2);
  $line['price'] = $price; $_SESSION['pos_msg']="Price updated.";
}

header("Location: pos_cart.php");
