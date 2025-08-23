<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: retailer_login.php"); exit();
}

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) { die("DB error: ".$db->connect_error); }

$stmt = $db->prepare("SELECT id, name FROM retailers WHERE retailer_id = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['retailer_id']);
$stmt->execute();
$retRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$retRow) { die("Retailer not found."); }
$retailer_id = (int)$retRow['id'];

$sql = "SELECT o.id, o.total_amount, o.status, o.created_at
        FROM orders o
        WHERE o.retailer_id = ?
        ORDER BY o.created_at DESC, o.id DESC";
$stmt = $db->prepare($sql);
$stmt->bind_param("i", $retailer_id);
$stmt->execute();
$res = $stmt->get_result();
$orders = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>My Orders - Retailer</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/retailer_styles.css">
</head>
<body>
  <div class="agri-container">
        <aside class="agri-sidebar">
            <div class="agri-sidebar-header">
              <img src="Image/logo.png" alt="AGROMATI Logo" class="logo-image">
            </div>
            <nav class="agri-sidebar-nav">
                <ul>
                    <li class="agri-nav-item" data-page="dashboard">
                        <a href="retailer_dashboard.php" class="agri-nav-link">
                            <i class="fas fa-tachometer-alt agri-nav-icon"></i> 
                            Dashboard
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="Shop">
                        <a href="shop.php" class="agri-nav-link">
                            <i class="fas fa-users agri-nav-icon"></i> 
                            Shop
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="Cart">
                        <a href="cart.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Cart
                        </a>
                    </li>
                    <li class="agri-nav-item active" data-page="My orders">
                        <a href="retailer_orders.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Orders
                        </a>
                    </li>
                    
                    <li class="agri-nav-item" data-page="pos">
                    <a href="retailer_pos.php" class="agri-nav-link">
                        <i class="fas fa-cash-register agri-nav-icon"></i> POS (Sell)
                    </a>
                    </li>
                    <li class="agri-nav-item" data-page="pos_cart">
                        <a href="pos_cart.php" class="agri-nav-link">
                            <i class="fas fa-shopping-cart agri-nav-icon"></i> POS Cart
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="pos_sales">
                        <a href="retailer_posSell.php" class="agri-nav-link">
                            <i class="fas fa-history agri-nav-icon"></i> POS Sales History
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="stock">
                      <a href="retailer_stock.php" class="agri-nav-link">
                          <i class="fas fa-warehouse agri-nav-icon"></i> 
                            Stock Movement
                      </a>
                  </li>
                    <li class="agri-nav-item">
                        <a href="retailer_logout.php" class="agri-nav-link">
                            <i class="fas fa-sign-out-alt agri-nav-icon"></i> 
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>

<main class="flex-grow-1 p-4">
  <div class="page-shell">

    <div class="card-panel card-panel--compact rounded-xl">
      <div class="page-header">
        <h2>Your Cart</h2>
        <div>
          <a class="btn btn-outline-secondary me-2" href="shop.php">
            <i class="fas fa-store me-1"></i> Continue Shopping
          </a>
          <a class="btn btn-outline-primary" href="retailer_dashboard.php">
            <i class="fas fa-home me-1"></i> Dashboard
          </a>
        </div>
      </div>

  <?php if(!empty($_SESSION['ret_ord_msg'])): ?>
    <div class="alert alert-success"><?=$_SESSION['ret_ord_msg']; unset($_SESSION['ret_ord_msg']);?></div>
  <?php endif; ?>
  <?php if(!empty($_SESSION['ret_ord_err'])): ?>
    <div class="alert alert-danger"><?=$_SESSION['ret_ord_err']; unset($_SESSION['ret_ord_err']);?></div>
  <?php endif; ?>

  <?php if (empty($orders)): ?>
    <div class="alert alert-info">You haven’t placed any orders yet.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-striped align-middle">
        <thead class="table-light">
          <tr>
            <th>Order #</th>
            <th>Date</th>
            <th>Status</th>
            <th class="text-end">Total</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($orders as $o): 
          $badge = $o['status']==='pending'   ? 'bg-secondary' :
                   ($o['status']==='confirmed' ? 'bg-info' :
                   ($o['status']==='shipped'   ? 'bg-success' : 'bg-danger'));
        ?>
          <tr>
            <td>#<?= (int)$o['id'] ?></td>
            <td><?= htmlspecialchars($o['created_at']) ?></td>
            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($o['status']) ?></span></td>
            <td class="text-end">৳<?= number_format((float)$o['total_amount'], 2) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="retailer_order_view.php?order_id=<?= (int)$o['id'] ?>">
                <i class="fas fa-eye me-1"></i>View
              </a>
              <?php if ($o['status'] === 'pending'): ?>
              <form class="d-inline" method="post" action="retailer_cancel_order.php" onsubmit="return confirm('Cancel this order?');">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-ban me-1"></i>Cancel</button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-3 small">
      <strong>Status:</strong>
      <span class="badge bg-secondary">pending</span>
      <span class="badge bg-info">confirmed</span>
      <span class="badge bg-success">shipped</span>
      <span class="badge bg-danger">cancelled</span>
    </div>
  <?php endif; ?>

   </div>

  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
