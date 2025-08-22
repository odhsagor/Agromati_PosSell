<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: retailer_login.php"); exit(); }
$db = new mysqli('localhost','root','','agromatiDB'); if ($db->connect_error) die($db->connect_error);

if (!isset($_POST['product_id'], $_POST['quantity'], $_POST['unit_price'])) { header("Location: retailer_pos.php"); exit(); }

$product_id = (int)$_POST['product_id'];
$qty        = max(1, (int)$_POST['quantity']);
$unit_price = round((float)$_POST['unit_price'], 2);

$stmt = $db->prepare("SELECT id FROM retailers WHERE retailer_id = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute(); $ret = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$ret) { $_SESSION['pos_err']="Retailer not found."; header("Location: retailer_pos.php"); exit(); }
$retailer_id = (int)$ret['id'];

$stmt = $db->prepare("SELECT qty_available FROM retailer_inventory WHERE retailer_id=? AND product_id=?");
$stmt->bind_param("ii",$retailer_id,$product_id);
$stmt->execute(); $inv = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$inv || $qty > (int)$inv['qty_available']) {
  $_SESSION['pos_err'] = "Insufficient stock.";
  header("Location: retailer_pos.php"); exit();
}
$stmt = $db->prepare("SELECT name, unit_of_measure FROM products WHERE id=?");
$stmt->bind_param("i",$product_id);
$stmt->execute(); $prod = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!isset($_SESSION['pos_cart'])) $_SESSION['pos_cart'] = []; // [product_id => ['name','unit','qty','price']]
$cart =& $_SESSION['pos_cart'];

if (!isset($cart[$product_id])) {
  $cart[$product_id] = [
    'name' => $prod['name'],
    'unit' => $prod['unit_of_measure'],
    'qty'  => 0,
    'price'=> $unit_price
  ];
}
$newQty = $cart[$product_id]['qty'] + $qty;
if ($newQty > (int)$inv['qty_available']) {
  $_SESSION['pos_err'] = "Adding that much would exceed stock.";
} else {
  $cart[$product_id]['qty']   = $newQty;
  $cart[$product_id]['price'] = $unit_price; // last price wins
  $_SESSION['pos_msg'] = "Added to POS cart.";
}
header("Location: retailer_pos.php");
