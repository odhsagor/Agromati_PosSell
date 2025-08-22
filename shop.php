<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
  header("Location: retailer_login.php"); exit();
}

$db = new mysqli('localhost','root','','agromatiDB');
if ($db->connect_error) { die("DB error: ".$db->connect_error); }

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT p.id, p.name, p.type, p.unit_of_measure, p.per_unit_price, p.total_units, u.name AS farmer_name
        FROM products p 
        JOIN users u ON u.id = p.user_id";
$params = [];
if ($search !== '') {
  $sql .= " WHERE p.name LIKE ? OR p.type LIKE ?";
  $stmt = $db->prepare($sql);
  $like = "%{$search}%";
  $stmt->bind_param("ss", $like, $like);
} else {
  $stmt = $db->prepare($sql);
}
$stmt->execute();
$res = $stmt->get_result();
$products = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Shop - AGROMATI</title>
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
                    <li class="agri-nav-item active" data-page="Shop">
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
                    <li class="agri-nav-item" data-page="My orders">
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
                    <li class="agri-nav-item">
                        <a href="retailer_logout.php" class="agri-nav-link">
                            <i class="fas fa-sign-out-alt agri-nav-icon"></i> 
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
  </aside>

  <main class="agri-main-content container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h2>Shop Products</h2>
      <form class="d-flex" method="get">
        <input name="q" class="form-control me-2" placeholder="Search name or type..." value="<?=htmlspecialchars($search)?>">
        <button class="btn btn-outline-secondary">Search</button>
      </form>
    </div>

    <div class="row">
      <?php foreach ($products as $p): ?>
      <div class="col-md-4 mb-4">
        <div class="card shadow-sm h-100">
          <div class="card-body d-flex flex-column">
            <h5 class="card-title"><?=htmlspecialchars($p['name'])?></h5>
            <p class="mb-1"><strong>Type:</strong> <?=htmlspecialchars($p['type'])?></p>
            <p class="mb-1"><strong>Unit:</strong> <?=htmlspecialchars($p['unit_of_measure'])?></p>
            <p class="mb-1"><strong>Price/Unit:</strong> ৳<?=number_format($p['per_unit_price'],2)?></p>
            <p class="mb-3"><strong>Available Units:</strong> <?=number_format($p['total_units'])?></p>
            <small class="text-muted mb-3">Farmer: <?=htmlspecialchars($p['farmer_name'])?></small>
            <form class="mt-auto" method="post" action="add_to_cart.php">
              <input type="hidden" name="product_id" value="<?=$p['id']?>">
              <div class="input-group">
                <input type="number" name="quantity" class="form-control" min="1" max="<?=max(1,(int)$p['total_units'])?>" value="1" required>
                <button class="btn btn-success" type="submit" name="add_to_cart"><i class="fas fa-cart-plus me-1"></i>Add to Cart</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php if (empty($products)): ?>
        <p>No products found.</p>
      <?php endif; ?>
    </div>
  </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
