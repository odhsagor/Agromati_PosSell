<?php
session_start();

$db_host = 'localhost';
$db_user = 'root'; 
$db_pass = '';
$db_name = 'agromatiDB';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_product'])) {
        $name = $_POST['name'];
        $type = $_POST['type'];
        $unit = $_POST['unit_of_measure'];
        $seasonality = $_POST['seasonality'];
        $nutrition = $_POST['nutrition'];
        $per_unit_price = $_POST['per_unit_price'];
        $total_units = $_POST['total_units'];
        
        $stmt = $conn->prepare("INSERT INTO products (user_id, name, type, unit_of_measure, seasonality, nutrition, per_unit_price, total_units) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssdi", $userId, $name, $type, $unit, $seasonality, $nutrition, $per_unit_price, $total_units);
        $stmt->execute();
        $stmt->close();
        
        header("Location: products.php");
        exit();
    } elseif (isset($_POST['delete_product'])) {
        $productId = $_POST['product_id'];
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $productId, $userId);
        $stmt->execute();
        $stmt->close();
        
        header("Location: products.php");
        exit();
    }
}
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Catalog - AGROMATI</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/products.css">
    <script>
        function calculateTotalPrice(input) {
            const perUnitPrice = parseFloat(document.querySelector('input[name="per_unit_price"]').value) || 0;
            const totalUnits = parseFloat(input.value) || 0;
            const totalPrice = perUnitPrice * totalUnits;
            document.getElementById('total_price_display').textContent = totalPrice.toFixed(2);
        }
        
        function calculateTotalPriceFromUnit(input) {
            const totalUnits = parseFloat(document.querySelector('input[name="total_units"]').value) || 0;
            const perUnitPrice = parseFloat(input.value) || 0;
            const totalPrice = perUnitPrice * totalUnits;
            document.getElementById('total_price_display').textContent = totalPrice.toFixed(2);
        }
    </script>
</head>
<body>
    <div class="agri-container">
        <aside class="agri-sidebar">
            <div class="agri-sidebar-header">
                <h2>AGROMATI</h2>
                <p>Farmer Portal</p>
            </div>
            <nav class="agri-sidebar-nav">
                <ul>
                    <li class="agri-nav-item" data-page="dashboard">
                        <a href="dashboard.php" class="agri-nav-link">
                            <i class="fas fa-tachometer-alt agri-nav-icon"></i> 
                            Dashboard
                        </a>
                    </li>
                    <li class="agri-nav-item " data-page="harvests">
                        <a href="harvests.php" class="agri-nav-link">
                            <i class="fas fa-seedling agri-nav-icon"></i> 
                            My Harvests
                        </a>
                    </li>
                    <li class="agri-nav-item active" data-page="products">
                        <a href="products.php" class="agri-nav-link">
                            <i class="fas fa-box agri-nav-icon"></i> 
                            My Products
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="wholesaler">
                        <a href="warehouses.php" class="agri-nav-link">
                            <i class="fas fa-warehouse agri-nav-icon"></i> 
                            wholesaler
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="profile">
                        <a href="#" class="agri-nav-link">
                            <i class="fas fa-user agri-nav-icon"></i> 
                            Profile
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="orders">
                        <a href="order.php" class="agri-nav-link">
                            <i class="fas fa-clipboard-list agri-nav-icon"></i> 
                            Orders
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="weather">
                        <a href="#" class="agri-nav-link">
                            <i class="fas fa-cloud-sun agri-nav-icon"></i> 
                            Weather
                        </a>
                    </li>
                    <li class="agri-nav-item">
                        <a href="logout.php" class="agri-nav-link">
                            <i class="fas fa-sign-out-alt agri-nav-icon"></i> 
                            Logout
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        <main class="agri-main-content">
            <div class="container py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="agri-main-title">Product Catalog</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-circle me-1"></i> Add Product
                    </button>
                </div>
                <div class="row">
                    <?php foreach ($products as $product): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card shadow-sm agri-product-card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p><strong>Type:</strong> <?= htmlspecialchars($product['type']) ?></p>
                                <p><strong>Unit:</strong> <?= htmlspecialchars($product['unit_of_measure']) ?></p>
                                <p><strong>Season:</strong> <?= htmlspecialchars($product['seasonality']) ?></p>
                                <p><strong>Nutrition:</strong> <?= htmlspecialchars($product['nutrition']) ?></p>
                                <p><strong>Price/Unit:</strong> ৳<?= number_format($product['per_unit_price'], 2) ?></p>
                                <p><strong>Total Units:</strong> <?= number_format($product['total_units']) ?></p>
                                <p><strong>Total Value:</strong> ৳<?= number_format($product['total_price'], 2) ?></p>
                                <div class="d-flex justify-content-between mt-3">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProductModal<?= $product['id'] ?>">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <button type="submit" name="delete_product" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal fade" id="editProductModal<?= $product['id'] ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Edit Product</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="update_product.php">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <div class="row mb-3">
                                            <div class="col">
                                                <label class="form-label">Product Name</label>
                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Type</label>
                                                <input type="text" name="type" class="form-control" value="<?= htmlspecialchars($product['type']) ?>" required>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col">
                                                <label class="form-label">Unit of Measure</label>
                                                <input type="text" name="unit_of_measure" class="form-control" value="<?= htmlspecialchars($product['unit_of_measure']) ?>">
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Seasonality</label>
                                                <input type="text" name="seasonality" class="form-control" value="<?= htmlspecialchars($product['seasonality']) ?>">
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col">
                                                <label class="form-label">Price Per Unit (৳)</label>
                                                <input type="number" step="0.01" name="per_unit_price" class="form-control" value="<?= $product['per_unit_price'] ?>" oninput="calculateTotalPriceFromUnit(this)">
                                            </div>
                                            <div class="col">
                                                <label class="form-label">Total Units</label>
                                                <input type="number" name="total_units" class="form-control" value="<?= $product['total_units'] ?>" oninput="calculateTotalPrice(this)">
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Total Price: $<span id="total_price_display_edit_<?= $product['id'] ?>"><?= number_format($product['total_price'], 2) ?></span></label>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Nutrition Contains</label>
                                            <textarea name="nutrition" class="form-control" rows="2"><?= htmlspecialchars($product['nutrition']) ?></textarea>
                                        </div>
                                        <div class="text-end">
                                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Update</button>
                                        </div>
                                    </form>
                                    <script>
                                        function calculateTotalPrice(input) {
                                            const row = input.closest('.modal-content');
                                            const perUnitPrice = parseFloat(row.querySelector('input[name="per_unit_price"]').value) || 0;
                                            const totalUnits = parseFloat(input.value) || 0;
                                            const totalPrice = perUnitPrice * totalUnits;
                                            row.querySelector('#total_price_display_edit_<?= $product['id'] ?>').textContent = totalPrice.toFixed(2);
                                        }
                                        
                                        function calculateTotalPriceFromUnit(input) {
                                            const row = input.closest('.modal-content');
                                            const totalUnits = parseFloat(row.querySelector('input[name="total_units"]').value) || 0;
                                            const perUnitPrice = parseFloat(input.value) || 0;
                                            const totalPrice = perUnitPrice * totalUnits;
                                            row.querySelector('#total_price_display_edit_<?= $product['id'] ?>').textContent = totalPrice.toFixed(2);
                                        }
                                    </script>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Product</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Product Name</label>
                                        <input type="text" name="name" class="form-control" placeholder="Rice" required>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Type</label>
                                        <input type="text" name="type" class="form-control" placeholder="Cereal" required>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Unit of Measure</label>
                                        <input type="text" name="unit_of_measure" class="form-control" placeholder="Kg">
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Seasonality</label>
                                        <input type="text" name="seasonality" class="form-control" placeholder="Winter">
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col">
                                        <label class="form-label">Price Per Unit (৳)</label>
                                        <input type="number" step="0.01" name="per_unit_price" class="form-control" placeholder="2.50" oninput="calculateTotalPriceFromUnit(this)" required>
                                    </div>
                                    <div class="col">
                                        <label class="form-label">Total Units</label>
                                        <input type="number" name="total_units" class="form-control" placeholder="100" oninput="calculateTotalPrice(this)" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Total Price: ৳<span id="total_price_display">0.00</span></label>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Nutrition Contains</label>
                                    <textarea name="nutrition" class="form-control" rows="2" placeholder="Vitamins, Minerals"></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" name="add_product" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Product</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>