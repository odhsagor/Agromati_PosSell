<?php
// farmer_update_order_status.php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: login.php"); exit();
}
$farmer_id = (int)$_SESSION['user_id'];

if (!isset($_POST['new_status'])) { header("Location: order.php"); exit(); }
$new_status = $_POST['new_status'];
$valid = ['pending','confirmed','shipped','cancelled'];
if (!in_array($new_status, $valid, true)) { header("Location: order.php"); exit(); }

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) { die("DB error: ".$db->connect_error); }

$db->begin_transaction();
try {
  $affected_order_ids = [];

  if (!empty($_POST['order_item_id'])) {

    $oi_id = (int)$_POST['order_item_id'];

    $stmt = $db->prepare("SELECT order_id FROM order_items WHERE id=? AND farmer_id=? LIMIT 1");
    $stmt->bind_param("ii",$oi_id,$farmer_id);
    $stmt->execute(); $row = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if (!$row) throw new Exception("Item not found or not yours.");
    $order_id = (int)$row['order_id'];

    $stmt = $db->prepare("UPDATE order_items SET status=? WHERE id=? AND farmer_id=?");
    $stmt->bind_param("sii",$new_status,$oi_id,$farmer_id);
    $stmt->execute(); $stmt->close();

    $affected_order_ids[] = $order_id;

  } elseif (!empty($_POST['order_id'])) {
    
    $order_id = (int)$_POST['order_id'];

    $stmt = $db->prepare("SELECT COUNT(*) c FROM order_items WHERE order_id=? AND farmer_id=?");
    $stmt->bind_param("ii",$order_id,$farmer_id);
    $stmt->execute(); $c = $stmt->get_result()->fetch_assoc()['c'] ?? 0; $stmt->close();
    if ((int)$c === 0) throw new Exception("No items to update in this order.");

    $stmt = $db->prepare("UPDATE order_items SET status=? WHERE order_id=? AND farmer_id=?");
    $stmt->bind_param("sii",$new_status,$order_id,$farmer_id);
    $stmt->execute(); $stmt->close();

    $affected_order_ids[] = $order_id;

  } else {
    throw new Exception("Nothing to update.");
  }
  foreach (array_unique($affected_order_ids) as $oid) {
    $sql = "SELECT status FROM order_items WHERE order_id=?";
    $stmt = $db->prepare($sql); $stmt->bind_param("i",$oid);
    $stmt->execute(); $res = $stmt->get_result();
    $statuses = [];
    while ($r = $res->fetch_assoc()) { $statuses[] = $r['status']; }
    $stmt->close();

    $all_cancelled = !empty($statuses) && count(array_unique($statuses)) === 1 && $statuses[0] === 'cancelled';
    if ($all_cancelled) {
      $newOrderStatus = 'cancelled';
    } elseif (in_array('pending', $statuses, true)) {
      $newOrderStatus = 'pending';
    } elseif (in_array('confirmed', $statuses, true)) {
      $newOrderStatus = 'confirmed';
    } else {
      // either all shipped or mix of shipped/cancelled but none pending/confirmed
      $newOrderStatus = 'shipped';
    }

    $stmt = $db->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si",$newOrderStatus,$oid);
    $stmt->execute(); $stmt->close();
  }

  $db->commit();
  $_SESSION['order_msg'] = "Status updated.";
} catch (Exception $e) {
  $db->rollback();
  $_SESSION['order_err'] = "Update failed: ".$e->getMessage();
}
header("Location: order.php");
exit();
