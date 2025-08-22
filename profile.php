<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'agromatiDB';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$name = $email = $phone = $password = '';
$profile_picture = '';
$errors = [];
$success_message = '';

// Fetch existing user data
$stmt = $conn->prepare("SELECT name, email, phone, profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($name, $email, $phone, $profile_picture);
$stmt->fetch();
$stmt->close();

// Check if the form is being submitted to update the profile
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Handle Profile Picture
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $profile_picture = file_get_contents($_FILES['profile_picture']['tmp_name']);
    }

    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors['phone'] = 'Invalid phone number';
    }

    if (!empty($password) && strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    // If no errors, proceed with saving the profile
    if (empty($errors)) {
        $hashed_password = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

        $update_query = "UPDATE users SET name = ?, email = ?, phone = ? ";
        
        if (!empty($profile_picture)) {
            $update_query .= ", profile_picture = ? ";
        }
        
        if (!empty($password)) {
            $update_query .= ", password = ? ";
        }
        
        $update_query .= "WHERE id = ?";
        
        // Prepare the query for execution
        $stmt = $conn->prepare($update_query);
        
        if (!empty($profile_picture) && !empty($password)) {
            $stmt->bind_param("ssssssi", $name, $email, $phone, $profile_picture, $hashed_password, $user_id);
        } elseif (!empty($profile_picture)) {
            $stmt->bind_param("sssss", $name, $email, $phone, $profile_picture, $user_id);
        } elseif (!empty($password)) {
            $stmt->bind_param("ssssi", $name, $email, $phone, $hashed_password, $user_id);
        } else {
            $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        }

        // Execute the query and check if it's successful
        if ($stmt->execute()) {
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_phone'] = $phone;
            $success_message = 'Profile updated successfully!';
        } else {
            $errors['database'] = 'Failed to update profile. Please try again.';
        }
        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGROMATI — Profile</title>
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
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
                    <li class="agri-nav-item" data-page="harvests">
                        <a href="harvests.php" class="agri-nav-link">
                            <i class="fas fa-seedling agri-nav-icon"></i> 
                            My Harvests
                        </a>
                    </li>
                    <li class="agri-nav-item" data-page="products">
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
                    <li class="agri-nav-item active" data-page="profile">
                        <a href="profile.php" class="agri-nav-link">
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
                        <a href="weather.php" class="agri-nav-link">
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
            <h1 class="agri-main-title">Your Profile</h1>

            <?php if (!empty($success_message)): ?>
                <div class="message-box success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="message-box error">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="profile-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input id="name" name="name" type="text" value="<?php echo htmlspecialchars($name); ?>" required />
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" name="email" type="email" value="<?php echo htmlspecialchars($email); ?>" required />
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input id="phone" name="phone" type="tel" value="<?php echo htmlspecialchars($phone); ?>" required />
                </div>

                <div class="form-group">
                    <label for="profile_picture">Profile Picture</label>
                    <input id="profile_picture" name="profile_picture" type="file" />
                    <?php if (!empty($profile_picture)): ?>
                        <img src="data:image/jpeg;base64,<?php echo base64_encode($profile_picture); ?>" alt="Profile Picture" class="profile-img" />
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="password">New Password (Leave blank to keep current password)</label>
                    <input id="password" name="password" type="password" />
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input id="confirm_password" name="confirm_password" type="password" />
                </div>

                <button type="submit" class="submit-btn">Update Profile</button>
            </form>
        </main>
    </div>
</body>
</html>
