<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) { header("Location: retailer_login.php"); exit(); }

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) { die("DB error: ".$db->connect_error); }

$db->begin_transaction();

try {
  $stmt = $db->prepare("SELECT id FROM retailers WHERE retailer_id=? LIMIT 1");
  $stmt->bind_param("s", $_SESSION['retailer_id']);
  $stmt->execute(); $ret = $stmt->get_result()->fetch_assoc(); $stmt->close();
  if (!$ret) throw new Exception("Retailer not found");
  $retailer_id = (int)$ret['id'];

  $stmt = $db->prepare("SELECT id FROM carts WHERE retailer_id=? AND status='active' LIMIT 1");
  $stmt->bind_param("i",$retailer_id); $stmt->execute();
  $cartRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
  if (!$cartRow) throw new Exception("No active cart.");
  $cart_id = (int)$cartRow['id'];


  $sql = "SELECT ci.product_id, ci.quantity, ci.unit_price, p.user_id AS farmer_id, p.total_units
          FROM cart_items ci JOIN products p ON p.id = ci.product_id
          WHERE ci.cart_id=?";
  $stmt = $db->prepare($sql); $stmt->bind_param("i",$cart_id); $stmt->execute();
  $res = $stmt->get_result(); $items = $res->fetch_all(MYSQLI_ASSOC); $stmt->close();
  if (empty($items)) throw new Exception("Cart is empty.");

  foreach ($items as $it) {
    if ($it['quantity'] > (int)$it['total_units']) {
      throw new Exception("Insufficient stock for a product. Please update your cart.");
    }
  }

  $stmt = $db->prepare("INSERT INTO orders (retailer_id, total_amount) VALUES (?,0.00)");
  $stmt->bind_param("i",$retailer_id); $stmt->execute(); $order_id = $stmt->insert_id; $stmt->close();

  $orderTotal = 0.00;

  $oi = $db->prepare("INSERT INTO order_items (order_id, product_id, farmer_id, quantity, unit_price) VALUES (?,?,?,?,?)");
  $upd = $db->prepare("UPDATE products SET total_units = total_units - ? WHERE id=?");

  foreach ($items as $it) {
    $oi->bind_param("iiiid", $order_id, $it['product_id'], $it['farmer_id'], $it['quantity'], $it['unit_price']);
    $oi->execute();
    $orderTotal += $it['quantity'] * $it['unit_price'];

  
    $upd->bind_param("ii", $it['quantity'], $it['product_id']);
    $upd->execute();
  }
  $oi->close(); $upd->close();


  $stmt = $db->prepare("UPDATE orders SET total_amount=? WHERE id=?");
  $stmt->bind_param("di",$orderTotal,$order_id); $stmt->execute(); $stmt->close();
  
  $stmt = $db->prepare("UPDATE carts SET status='ordered' WHERE id=?");
  $stmt->bind_param("i",$cart_id); $stmt->execute(); $stmt->close();


  $db->commit();
  header("Location: retailer_orders.php"); // a simple page to list own orders (optional)
  exit();

} catch (Exception $e) {
  $db->rollback();
  $_SESSION['cart_error'] = "Order failed: ".$e->getMessage();
  header("Location: cart.php"); exit();
}
