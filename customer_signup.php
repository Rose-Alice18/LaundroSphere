<?php
session_start();
require 'db_connect.php';

$error_message = '';
$success_message = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $first_name = filter_var(trim($_POST['first_name']), FILTER_SANITIZE_STRING);
    $last_name = filter_var(trim($_POST['last_name']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $phone_number = filter_var(trim($_POST['phone_number']), FILTER_SANITIZE_STRING);

    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password) || empty($confirm_password) || empty($phone_number)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error_message = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error_message = "Password must contain at least one number.";
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error_message = "Password must contain at least one special character.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } else {
        // Check if email already exists
        $check_email_stmt = $conn->prepare("SELECT email FROM User WHERE email = ?");
        $check_email_stmt->bind_param("s", $email);
        $check_email_stmt->execute();
        $result = $check_email_stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Email already registered. Please use a different email or login.";
        } else {
            // Hash password and insert into User table
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO User (fname, lname, email, password, phone_number, role) VALUES (?, ?, ?, ?, ?, 'customer')");

            if ($stmt) {
                $stmt->bind_param("sssss", $first_name, $last_name, $email, $hashed_password, $phone_number);

                if ($stmt->execute()) {
                    $success_message = "Registration successful! Welcome to LaundroSphere.";

                    // Remember Me functionality
                    if (isset($_POST['remember_me'])) {
                        setcookie("customer_email", $email, time() + (86400 * 30), "/"); // Set cookie for 30 days
                    }

                    header("Location: login.php");
                    exit;
                } else {
                    $error_message = "Registration failed. Please try again.";
                }

                $stmt->close();
            } else {
                $error_message = "Error preparing database query.";
            }
        }
        $check_email_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Sign Up | LaundroSphere</title>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
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
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 15px 30px;
            position: fixed;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: white;
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

        .signup-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 6rem 2rem 2rem 2rem;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
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
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #800020;
            outline: none;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .signup-btn {
            width: 100%;
            padding: 1rem;
            background: #923D41;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .signup-btn:hover {
            background: rgb(114, 47, 55);
        }

        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .error-message {
            background: #fee;
            color: #c00;
        }

        .success-message {
            background: #efe;
            color: #070;
        }

        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #666;
        }

        .login-link a {
            color: #800020;
            text-decoration: none;
            font-weight: 600;
        }

        .signup-text {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #555;
            line-height: 1.6;
        }

        .company-signup-text {
            text-align: center;
            margin-top: 1.5rem;
            color: #555;
            line-height: 1.6;
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 8px;
        }

        .company-signup-text a {
            color: #800020;
            text-decoration: none;
            font-weight: 600;
        }


        /* Footer */
        footer {
            background-color: black;
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

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            color: #ccc;
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: #BC1E4A;
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

        .footer-section {
            padding: 0 20px;
        }

        .footer-section h3 {
            color: rgb(114, 47, 55);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .footer-section p {
            margin-bottom: 15px;
            color: #ccc;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: rgb(114, 47, 55);
        }

    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">LaundroSphere</div>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
        </ul>
    </nav>

    <div class="signup-container">
        <div class="form-container">
            <h1><i class="fas fa-washing-machine"></i> Customer Sign Up</h1>
            
            <div class="signup-text">
                Join LaundroSphere and experience hassle-free laundry services! Create your account to schedule pickups, track your orders, and enjoy personalized washing solutions.
            </div>

            <?php if ($error_message): ?>
                <div class="message error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="message success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (!$success_message): ?>
                <form method="POST" action="customer_signup.php">
                    <div class="form-group">
                        <label for="first_name"><i class="fas fa-user"></i> First Name</label>
                        <input type="text" name="first_name" id="first_name" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name"><i class="fas fa-user"></i> Last Name</label>
                        <input type="text" name="last_name" id="last_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="email" id="email" required>
                    </div>

                    <div class="form-group">
                        <label for="phone_number"><i class="fas fa-phone"></i> Phone Number</label>
                        <input type="tel" name="phone_number" id="phone_number" required>
                    </div>

                    <div class="form-group" style="position: relative;">
                        <label for="password"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <span toggle="#password" class="fa fa-fw fa-eye field-icon" onclick="togglePasswordVisibility('password', this)" 
                              style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%); cursor: pointer;"></span>
                    </div>

                    <div class="form-group" style="position: relative;">
                        <label for="confirm_password"><i class="fas fa-lock"></i> Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <span toggle="#confirm_password" class="fa fa-fw fa-eye field-icon" onclick="togglePasswordVisibility('confirm_password', this)" 
                              style="position: absolute; top: 50%; right: 20px; transform: translateY(-50%); cursor: pointer;"></span>
                    </div>

                    

                    <button type="submit" class="signup-btn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
            <?php endif; ?>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>

            <div class="company-signup-text">
                Are you a company looking to partner with LaundroSphere? <a href="company_signup.php">Sign up for a business account</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About LaundroSphere</h3>
                <p>Connecting customers with top-quality laundry services across the community.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#companies">Laundry Companies</a></li>
                    <li><a href="signup.php">Register</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: support@laundrosphere.com</p>
                <p>Phone: +1 (234) 567-8900</p>
            </div>

            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 LaundroSphere. All rights reserved.</p>
        </div>
    </footer>

    <script>
        function togglePasswordVisibility(fieldId, icon) {
            var passwordField = document.getElementById(fieldId);
            if (passwordField.type === "password") {
                passwordField.type = "text";
                icon.classList.remove("fa-eye");
                icon.classList.add("fa-eye-slash");
            } else {
                passwordField.type = "password";
                icon.classList.remove("fa-eye-slash");
                icon.classList.add("fa-eye");
            }
        }
    </script>
</body>
</html>

