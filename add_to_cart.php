<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: retailer_login.php"); exit();
}

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) { die("DB error: ".$db->connect_error); }

if (!isset($_POST['add_to_cart'], $_POST['product_id'], $_POST['quantity'])) {
  header("Location: shop.php"); exit();
}

$product_id = (int)$_POST['product_id'];
$qty = max(1, (int)$_POST['quantity']);

$sessionRetailerId = $_SESSION['retailer_id']; 
$stmt = $db->prepare("SELECT id FROM retailers WHERE retailer_id = ?");
$stmt->bind_param("s",$sessionRetailerId);
$stmt->execute(); $ridRes = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$ridRes) { die("Retailer not found."); }
$retailer_id = (int)$ridRes['id'];

$stmt = $db->prepare("SELECT per_unit_price, total_units FROM products WHERE id = ?");
$stmt->bind_param("i",$product_id);
$stmt->execute(); $prod = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$prod) { header("Location: shop.php"); exit(); }

if ($qty > (int)$prod['total_units']) {
  $_SESSION['cart_error'] = "Quantity exceeds available stock.";
  header("Location: shop.php"); exit();
}
$unit_price = (float)$prod['per_unit_price'];

$stmt = $db->prepare("SELECT id FROM carts WHERE retailer_id=? AND status='active' LIMIT 1");
$stmt->bind_param("i",$retailer_id);
$stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();

if ($row) { $cart_id = (int)$row['id']; }
else {
  $stmt = $db->prepare("INSERT INTO carts (retailer_id) VALUES (?)");
  $stmt->bind_param("i",$retailer_id);
  $stmt->execute(); $cart_id = $stmt->insert_id; $stmt->close();
}
$stmt = $db->prepare("SELECT id, quantity FROM cart_items WHERE cart_id=? AND product_id=?");
$stmt->bind_param("ii",$cart_id,$product_id);
$stmt->execute(); $itm = $stmt->get_result()->fetch_assoc(); $stmt->close();

if ($itm) {
  $newQty = $itm['quantity'] + $qty;
  if ($newQty > (int)$prod['total_units']) {
    $_SESSION['cart_error'] = "Cart qty would exceed stock.";
    header("Location: cart.php"); exit();
  }
  $stmt = $db->prepare("UPDATE cart_items SET quantity=? , unit_price=? WHERE id=?");
  $stmt->bind_param("idi", $newQty, $unit_price, $itm['id']);
  $stmt->execute(); $stmt->close();
} else {
  $stmt = $db->prepare("INSERT INTO cart_items (cart_id, product_id, quantity, unit_price) VALUES (?,?,?,?)");
  $stmt->bind_param("iiid", $cart_id, $product_id, $qty, $unit_price);
  $stmt->execute(); $stmt->close();
}

header("Location: cart.php");
exit();
