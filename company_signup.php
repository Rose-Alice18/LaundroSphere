<?php
session_start();
require 'db_connect.php';

$error_message = '';
$success_message = '';

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $company_name = filter_var(trim($_POST['company_name']), FILTER_SANITIZE_STRING);
    $company_email = filter_var(trim($_POST['company_email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $company_phone_number = filter_var(trim($_POST['company_phone_number']), FILTER_SANITIZE_STRING);
    $company_address = filter_var(trim($_POST['company_address']), FILTER_SANITIZE_STRING);

    // Validate inputs
    if (empty($company_name) || empty($company_email) || empty($password) || empty($confirm_password) || empty($company_phone_number) || empty($company_address)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($company_email, FILTER_VALIDATE_EMAIL)) {
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
        $check_email_stmt = $conn->prepare("SELECT company_email FROM Company WHERE company_email = ?");
        $check_email_stmt->bind_param("s", $company_email);
        $check_email_stmt->execute();
        $result = $check_email_stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "Email already registered. Please use a different email or login.";
        } else {
            // Hash password and insert into database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO Company (company_name, company_email, company_phone_number, company_address, password, status) 
                VALUES (?, ?, ?, ?, ?, 'Active')"
            );

            if ($stmt) {
                $stmt->bind_param("sssss", $company_name, $company_email, $company_phone_number, $company_address, $hashed_password);

                if ($stmt->execute()) {
                    $success_message = "Registration successful! Welcome to LaundroSphere.";
                    if (isset($_POST['remember_me'])) {
                        setcookie("company_email", $company_email, time() + (86400 * 30), "/"); // Set cookie for 30 days
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
    <title>Company Sign Up | LaundroSphere</title>
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
            background: #800025;
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

        .signup-text {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #555;
            line-height: 1.6;

        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }

        .form-footer a {
            color: #800025;
            text-decoration: none;
            font-weight: bold;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #888;
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
            <h1><i class="fas fa-industry"></i> Company Sign Up</h1>

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
            <form method="POST" action="company_signup.php">
                <div class="form-group">
                    <label for="company_name"><i class="fas fa-building"></i> Company Name</label>
                    <input type="text" name="company_name" id="company_name" required>
                </div>

                <div class="form-group">
                    <label for="company_email"><i class="fas fa-envelope"></i> Company Email</label>
                    <input type="email" name="company_email" id="company_email" required>
                </div>

                <div class="form-group">
                    <label for="company_phone_number"><i class="fas fa-phone"></i> Company Phone Number</label>
                    <input type="tel" name="company_phone_number" id="company_phone_number" required>
                </div>

                <div class="form-group">
                    <label for="company_address"><i class="fas fa-map-marker-alt"></i> Company Address</label>
                    <input type="text" name="company_address" id="company_address" required>
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

            <div class="form-footer">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        <?php endif; ?>
    </div>
</div>

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
