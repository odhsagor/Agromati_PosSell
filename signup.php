<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = ''; 
$db_name = 'agromatiDB';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $email = $phone = $password = '';
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($name)) {
        $errors['name'] = 'Name is required';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors['email'] = 'Email already exists';
        }
        $stmt->close();
    }

    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errors['phone'] = 'Invalid phone number';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match';
    }

    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $name, $email, $phone, $hashed_password);
        
        if ($stmt->execute()) {
            $success = true;
            $name = $email = $phone = $password = '';
        } else {
            $errors['database'] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AGROMATI — Sign Up</title>
    <link rel="stylesheet" href="css/retailer_signup.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo_M"><img src="image/logo.png"alt="AGROMATI Logo"class="logo-img">
            </a>
            
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="retailer_signup.php">Retailer Signup</a></li>
                <li><a href="retailer_login.php">Retailer Login</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="signup.php" class="btn-primary">Sign Up</a></li>
            </ul>
            
            <div class="mobile-menu-btn">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </nav>

    <main class="login-page">
        <div class="login-container">
            <section class="login-card">
                <header class="login-header">
                    <h1>Create an Account</h1>
                    <p>Join AGROMATI to manage your farming operations</p>
                </header>

                <?php if ($success): ?>
                    <div class="message-box success">
                        Registration successful! You can now <a href="login.php">login</a>.
                    </div>
                <?php elseif (isset($errors['database'])): ?>
                    <div class="message-box error">
                        <?php echo htmlspecialchars($errors['database']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="<?php echo htmlspecialchars($name); ?>"
                            required
                            placeholder="Write your Name"
                        />
                        <?php if (isset($errors['name'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['name']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            inputmode="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                            placeholder="Write Your mail Id"
                        />
                        <?php if (isset($errors['email'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['email']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input
                            id="phone"
                            name="phone"
                            type="tel"
                            inputmode="tel"
                            value="<?php echo htmlspecialchars($phone); ?>"
                            required
                            placeholder="(+880)"
                        />
                        <?php if (isset($errors['phone'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['phone']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-input">
                            <input
                                id="password"
                                name="password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                class="toggle-password"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (isset($errors['password'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['password']); ?></p>
                        <?php else: ?>
                            <p class="help-text">At least 8 characters.</p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-input">
                            <input
                                id="confirm_password"
                                name="confirm_password"
                                type="password"
                                required
                                minlength="8"
                                placeholder="••••••••"
                            />
                            <button
                                type="button"
                                class="toggle-password"
                                aria-label="Show password"
                                aria-pressed="false"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7Zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10Z"/>
                                </svg>
                            </button>
                        </div>
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['confirm_password']); ?></p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="submit-btn">
                        <span>Create Account</span>
                    </button>

                    <p class="signup-link">Already have an account? <a href="login.php">Log in</a></p>
                </form>
            </section>
        </div>
    </main>

    <script src="js/script.js"></script>
    <script src="js/signUp.js"></script>
</body>
</html>