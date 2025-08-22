<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: retailer_login.php"); exit(); }

$cart = $_SESSION['pos_cart'] ?? [];
if (empty($cart)) { $_SESSION['pos_err']="POS cart is empty."; header("Location: pos_cart.php"); exit(); }

$customer_name  = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : null;
$customer_phone = isset($_POST['customer_phone']) ? trim($_POST['customer_phone']) : null;

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) die($db->connect_error);


$stmt = $db->prepare("SELECT id FROM retailers WHERE retailer_id=? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute(); $ret = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$ret) { $_SESSION['pos_err']="Retailer not found."; header("Location: pos_cart.php"); exit(); }
$retailer_id = (int)$ret['id'];

$db->begin_transaction();
try {

  $total_items = 0; $total_amount = 0.00;
  foreach ($cart as $pid=>$line) {
    $qty = (int)$line['qty']; $price = (float)$line['price'];

    $stmt = $db->prepare("SELECT qty_available FROM retailer_inventory WHERE retailer_id=? AND product_id=? FOR UPDATE");
    $stmt->bind_param("ii",$retailer_id,$pid);
    $stmt->execute(); $inv = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$inv || $qty > (int)$inv['qty_available']) throw new Exception("Insufficient stock for ".$line['name']);
    $total_items  += $qty;
    $total_amount += $qty * $price;
  }

  
  $stmt = $db->prepare("INSERT INTO retailer_sales (retailer_id, customer_name, customer_phone, total_items, total_amount)
                        VALUES (?,?,?,?,?)");
  $stmt->bind_param("issid", $retailer_id, $customer_name, $customer_phone, $total_items, $total_amount);
  $stmt->execute(); $sale_id = $stmt->insert_id; $stmt->close();


  $ins = $db->prepare("INSERT INTO retailer_sale_items (sale_id, product_id, quantity, unit_price) VALUES (?,?,?,?)");
  $dec = $db->prepare("UPDATE retailer_inventory SET qty_available = qty_available - ? WHERE retailer_id=? AND product_id=?");
  $mov = $db->prepare("INSERT INTO retailer_stock_moves (retailer_id, product_id, direction, quantity, unit_price, source_type, source_id)
                       VALUES (?,?, 'out', ?, ?, 'sale', ?)");

  foreach ($cart as $pid=>$line) {
    $qty = (int)$line['qty']; $price = (float)$line['price'];
    $ins->bind_param("iiid", $sale_id, $pid, $qty, $price); $ins->execute();
    $dec->bind_param("iii", $qty, $retailer_id, $pid); $dec->execute();
    $mov->bind_param("iiidi", $retailer_id, $pid, $qty, $price, $sale_id); $mov->execute();
  }
  $ins->close(); $dec->close(); $mov->close();

  $db->commit();
  
  unset($_SESSION['pos_cart']);


  $_SESSION['pos_msg'] = "Sale #$sale_id saved. Total ৳".number_format($total_amount,2);
  header("Location: retailer_sales_receipt.php?sale_id=".$sale_id);
  exit();

} catch (Exception $e) {
  $db->rollback();
  $_SESSION['pos_err'] = "Checkout failed: ".$e->getMessage();
  header("Location: pos_cart.php"); exit();
}
