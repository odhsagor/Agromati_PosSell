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

$email = $password = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }
    
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['logged_in'] = true;
                
                if (isset($_POST['remember_email'])) {
                    setcookie('remembered_email', $email, time() + 30 * 24 * 60 * 60); // 30 days
                }
                
                header("Location: dashboard.php");
                exit();
            } else {
                $errors['login'] = 'Invalid email or password';
            }
        } else {
            $errors['login'] = 'Invalid email or password';
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
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#16a34a">
    <title>AGROMATI — Sign in</title>
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
                <li><a href="login.php" class="btn-primary">Login</a></li>
                <li><a href="signup.php">Sign Up</a></li>
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
                    <h1>Log in to AGROMATI</h1>
                    <p>Manage your farming operations efficiently</p>
                </header>

                <?php if (isset($errors['login'])): ?>
                    <div class="message-box error">
                        <?php echo htmlspecialchars($errors['login']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            inputmode="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                            placeholder="Enter Your Email"
                            aria-describedby="emailHelp"
                        />
                        <p id="emailHelp" class="help-text">Use the email you registered with.</p>
                        <?php if (isset($errors['email'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['email']); ?></p>
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
                                aria-describedby="passwordHint"
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
                        <p id="passwordHint" class="help-text">At least 8 characters.</p>
                        <?php if (isset($errors['password'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['password']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input 
                                id="remember_email"
                                name="remember_email"
                                type="checkbox"
                                <?php echo isset($_COOKIE['remembered_email']) ? 'checked' : ''; ?>
                            />
                            Remember my email
                        </label>
                        <a href="forgot_password.php" class="forgot-password">Forgot your password?</a>
                    </div>

                    <button type="submit" class="submit-btn">
                        <span>Sign in</span>
                    </button>

                    <p class="signup-link">Don't have an account? <a href="signup.php">Create one</a></p>
                </form>
            </section>

        </div>
    </main>

    <script src="js/script.js"></script>
</body>
</html>