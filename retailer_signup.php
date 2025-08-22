<?php
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'agromatiDB';


$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function generateRetailerID($conn) {
    $prefix = "RET";
    $digits = 3;
    $max_attempts = 10;

    $sql = "SELECT MAX(CAST(SUBSTRING(retailer_id, 4) AS UNSIGNED)) AS max_id 
            FROM retailers 
            WHERE retailer_id REGEXP '^{$prefix}[0-9]+$'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $next_num = ($row['max_id']) ? $row['max_id'] + 1 : 1;
    } else {
        $next_num = 1;
    }
    
    
    $attempt = 0;
    while ($attempt < $max_attempts) {
        $retailer_id = $prefix . str_pad($next_num, $digits, '0', STR_PAD_LEFT);
        
        $stmt = $conn->prepare("SELECT id FROM retailers WHERE retailer_id = ?");
        $stmt->bind_param("s", $retailer_id);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $retailer_id;
        }
        
        $next_num++;
        $attempt++;
        $stmt->close();
    }
    
    return $prefix . uniqid();
}


$retailer_id = $name = $email = $phone = $password = '';
$errors = [];
$success = false;


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    $retailer_id = generateRetailerID($conn);

    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Name must be less than 100 characters';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    } elseif (strlen($email) > 100) {
        $errors['email'] = 'Email must be less than 100 characters';
    } else {
        $stmt = $conn->prepare("SELECT id FROM retailers WHERE email = ?");
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
        $errors['phone'] = 'Invalid phone number (10-15 digits)';
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
        
        $stmt = $conn->prepare("INSERT INTO retailers (retailer_id, name, email, phone, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $retailer_id, $name, $email, $phone, $hashed_password);
        
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
    <title>AGROMATI — Retailer Sign Up</title>
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
                <li><a href="retailer_signup.php" class="btn-primary">Retailer Signup</a></li>
                <li><a href="retailer_login.php">Retailer Login</a></li>
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
                <div class="retailer-header">
                    <h2>Retailer Registration</h2>
                    <p>Join our network of agricultural retailers</p>
                </div>

                <?php if ($success): ?>
                    <div class="message-box success">
                        <h3>Registration Successful!</h3>
                        <div class="retailer-id-display">
                            Your Retailer ID: <strong><?php echo htmlspecialchars($retailer_id); ?></strong>
                        </div>
                        <p>Please save this ID for future reference. You can now <a href="retailer_login.php">login</a> to your account.</p>
                    </div>
                <?php elseif (isset($errors['database'])): ?>
                    <div class="message-box error">
                        <?php echo htmlspecialchars($errors['database']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="login-form" <?php echo $success ? 'style="display:none;"' : ''; ?>>
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value="<?php echo htmlspecialchars($name); ?>"
                            required
                            placeholder="Write Your Name"
                            maxlength="100"
                        />
                        <?php if (isset($errors['name'])): ?>
                            <p class="error-text"><?php echo htmlspecialchars($errors['name']); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            inputmode="email"
                            value="<?php echo htmlspecialchars($email); ?>"
                            required
                            placeholder="Write Your Email"
                            maxlength="100"
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
                            pattern="[0-9]{10,15}"
                            title="10-15 digit phone number"
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
                                pattern=".{8,}"
                                title="8 characters minimum"
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
                                pattern=".{8,}"
                                title="8 characters minimum"
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
                        <span>Register as Retailer</span>
                    </button>

                    <p class="signup-link">Already have an account? <a href="retailer_login.php">Retailer Login</a></p>
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
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = this.querySelector('#password');
            const confirmPassword = this.querySelector('#confirm_password');
            
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
        });
    </script>
</body>
</html>