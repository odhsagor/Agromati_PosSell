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

$retailer_id = $password = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $retailer_id = trim($_POST['retailer_id']);
    $password = trim($_POST['password']);

    if (empty($retailer_id)) {
        $errors['retailer_id'] = 'Retailer ID is required';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id, retailer_id, name, email, password FROM retailers WHERE retailer_id = ?");
        $stmt->bind_param("s", $retailer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $retailer = $result->fetch_assoc();
            
            if (password_verify($password, $retailer['password'])) {
                $_SESSION['retailer_id'] = $retailer['retailer_id'];
                $_SESSION['retailer_name'] = $retailer['name'];
                $_SESSION['retailer_email'] = $retailer['email'];
                $_SESSION['logged_in'] = true;
                
                header("Location: retailer_dashboard.php");
                exit();
            } else {
                $errors['login'] = 'Invalid Retailer ID or password';
            }
        } else {
            $errors['login'] = 'Invalid Retailer ID or password';
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
    <title>AGROMATI — Retailer Login</title>
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
                <li><a href="retailer_login.php" class="btn-primary">Retailer Login</a></li>
                <li><a href="login.php">Login</a></li>
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
                <div class="retailer-login-header">
                    <h2>Retailer Login</h2>
                    <p>Access your retailer account to manage your business</p>
                </div>

                <?php if (isset($errors['login'])): ?>
                    <div class="message-box error">
                        <?php echo htmlspecialchars($errors['login']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="retailer_id">Retailer ID</label>
                        <input
                            id="retailer_id"
                            name="retailer_id"
                            type="text"
                            value="<?php echo htmlspecialchars($retailer_id); ?>"
                            required
                            placeholder="Your ID"
                            pattern="RET\d{3,}"
                            title="Retailer ID should start with RET followed by numbers"
                        />
                        <?php if (isset($errors['retailer_id'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['retailer_id']); ?></p>
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
                        <?php endif; ?>
                    </div>

                    <div class="forgot-password">
                        <a href="retailer_forgot_password.php">Forgot Password?</a>
                    </div>

                    <button type="submit" class="submit-btn">
                        <span>Login to Your Account</span>
                    </button>

                    <p class="signup-link">Don't have an account? <a href="retailer_signup.php">Register as Retailer</a></p>
                </form>
            </section>
        </div>
    </main>

    <script>
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const input = this.parentElement.querySelector('input');
                const isShown = input.type === 'text';
                
                input.type = isShown ? 'password' : 'text';
                this.setAttribute('aria-pressed', !isShown);
                const svg = this.querySelector('svg');
                if (svg) {
                    svg.style.display = isShown ? 'block' : 'none';
                }
            });
        });
        document.querySelector('.mobile-menu-btn')?.addEventListener('click', function() {
            this.classList.toggle('active');
            document.querySelector('.nav-links')?.classList.toggle('active');
        });
    </script>
</body>
</html>