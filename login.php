<?php
session_start();
require 'db_connect.php';

$error_message = '';

// Handle Remember Me (populate email field if cookie exists)
$remembered_email = '';
if (isset($_COOKIE['email'])) {
    $remembered_email = $_COOKIE['email'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $remember_me = isset($_POST['remember_me']);

    if (empty($email) || empty($password)) {
        $error_message = "Please fill in all fields.";
    } else {
        $role = null;
        $user = null;

        // Check for admin role
        $stmt = $conn->prepare("SELECT admin_id, email, password FROM admin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $role = 'admin';
        } else {
            // Check for company role
            $stmt = $conn->prepare("SELECT company_id, company_email AS email, password FROM Company WHERE company_email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $role = 'company';
            } else {
                // Check for customer role
                $stmt = $conn->prepare("SELECT user_id, email, password FROM User WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $role = 'customer';
                }
            }
        }

        if ($role && $user) {
            // Verify the password
            if (password_verify($password, $user['password'])) {
                // Set session variables and redirect based on role
                if ($role === 'admin') {
                    $_SESSION['admin_id'] = $user['admin_id'];
                    header("Location: ./admin/admin_index.php");
                } elseif ($role === 'company') {
                    $_SESSION['company_id'] = $user['company_id'];
                    header("Location: ./company/company_index.php");
                } elseif ($role === 'customer') {
                    $_SESSION['user_id'] = $user['user_id'];
                    header("Location: ./user/user_index.php");
                }

                // Handle Remember Me (set cookie if checked)
                if ($remember_me) {
                    setcookie('email', $email, time() + (86400 * 30), "/"); // 30 days expiry
                } else {
                    setcookie('email', '', time() - 3600, "/"); // Delete the cookie
                }
                exit();
            } else {
                $error_message = "Invalid email or password.";
            }
        } else {
            $error_message = "Account not found.";
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
    <title>Login | LaundroSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            margin: 0;
            font-family: 'Lato', sans-serif;
            background: linear-gradient(135deg, #4B1F24 0%, #9B2C3D 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 15px 30px;
            position: fixed;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
            text-decoration: none;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 20px;
            margin: 0;
            padding: 0;
        }

        .nav-links li a {
            text-decoration: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-links li a:hover {
            background-color: #800020;
            transform: translateY(-2px);
        }

        .main-content {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 100px 20px 40px;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            backdrop-filter: blur(10px);
        }

        h1 {
            color: #800020;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: #800020;
            outline: none;
            box-shadow: 0 0 0 3px rgba(128, 0, 32, 0.1);
        }

        .login-btn {
            width: 100%;
            padding: 1rem;
            background: #800020;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .login-btn:hover {
            background: rgb(114, 47, 55);
        }

        .error-message {
            background: #fee;
            color: #c00;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .signup-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .signup-link a {
            color: #800020;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-link a:hover {
            text-decoration: underline;
        }

        /* Keep the eye icon on the right */
        .form-control .fas.fa-eye,
        .form-control .fas.fa-eye-slash {
        position: absolute;
        right: 1rem;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #718096;
        }

        footer {
            background-color: #333;
            color: white;
            padding: 60px 20px 20px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer-section {
            padding: 0 20px;
        }

        .footer-bottom {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">LaundroSphere</a>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="customer_signup.php"><i class="fas fa-user-plus"></i> Sign Up</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <div class="form-container">
            <h1><i class="fas fa-washing-machine"></i> LaundroSphere Login</h1>
            <?php if ($error_message): ?>
                <div class="error-message">
                    <?= htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Password</label>
                    <input type="password" name="password" id="password" required>
                    <span toggle="#password" class="fa fa-fw fa-eye field-icon" onclick="togglePasswordVisibility('password', this)" 
                            style="position: absolute; top: 50%; right: 20px; transform: translateY(30%); cursor: pointer;"></span>
                </div>

                <!-- Forgot Password Link -->
                <div class="form-group">
                    <input type="checkbox" id="remember_me" name="remember_me" <?php if ($remembered_email) echo 'checked'; ?>>
                    <label for="remember_me">Remember Me</label>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>
            <div class="signup-link">
                Don't have an account? <a href="customer_signup.php">Sign up here</a>
            </div>
        </div>

        <!-- JavaScript to toggle password visibility -->
    <script>
        function togglePasswordVisibility() {
            var passwordField = document.getElementById("password");
            var eyeIcon = document.querySelector(".field-icon");

            if (passwordField.type === "password") {
                passwordField.type = "text"; // Show password
                eyeIcon.classList.remove("fa-eye"); // Change icon to open eye
                eyeIcon.classList.add("fa-eye-slash"); // Change icon to closed eye
            } else {
                passwordField.type = "password"; // Hide password
                eyeIcon.classList.remove("fa-eye-slash"); // Change icon to closed eye
                eyeIcon.classList.add("fa-eye"); // Change icon to open eye
            }
        }
    </script>
    </div>
</body>
</html>
