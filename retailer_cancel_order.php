<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: retailer_login.php"); exit();
}

if (!isset($_POST['order_id'])) { header("Location: retailer_orders.php"); exit(); }
$order_id = (int)$_POST['order_id'];

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) { die("DB error: ".$db->connect_error); }

// Get numeric retailer id
$stmt = $db->prepare("SELECT id FROM retailers WHERE retailer_id=? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute(); $retRow = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$retRow) { $_SESSION['ret_ord_err']="Retailer not found."; header("Location: retailer_orders.php"); exit(); }
$retailer_id = (int)$retRow['id'];

// Ensure order belongs to retailer & is pending
$stmt = $db->prepare("SELECT status FROM orders WHERE id=? AND retailer_id=? LIMIT 1");
$stmt->bind_param("ii", $order_id, $retailer_id);
$stmt->execute(); $o = $stmt->get_result()->fetch_assoc(); $stmt->close();
if (!$o) { $_SESSION['ret_ord_err']="Order not found."; header("Location: retailer_orders.php"); exit(); }
if ($o['status'] !== 'pending') { $_SESSION['ret_ord_err']="Only pending orders can be cancelled."; header("Location: retailer_orders.php"); exit(); }

$db->begin_transaction();
try {
  // Set all non-cancelled items to cancelled
  $stmt = $db->prepare("UPDATE order_items SET status='cancelled' WHERE order_id=? AND status!='cancelled'");
  $stmt->bind_param("i", $order_id); $stmt->execute(); $stmt->close();

  // Set overall order to cancelled
  $stmt = $db->prepare("UPDATE orders SET status='cancelled' WHERE id=?");
  $stmt->bind_param("i", $order_id); $stmt->execute(); $stmt->close();

  $db->commit();
  $_SESSION['ret_ord_msg'] = "Order #$order_id cancelled.";
} catch (Exception $e) {
  $db->rollback();
  $_SESSION['ret_ord_err'] = "Cancel failed.";
}
header("Location: retailer_orders.php");
exit();
