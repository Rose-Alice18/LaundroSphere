<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect if not logged in
    exit();
}

$user_id = intval($_SESSION['user_id']); // Ensure user ID is an integer
include '../db_connect.php'; // Database connection

// Fetch user data from the database
$sql = "SELECT fname, lname, phone_number, email FROM User WHERE user_id = ?";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Error fetching user data.");
}

// Handle form submission for profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = htmlspecialchars(trim($_POST['fname']));
    $lname = htmlspecialchars(trim($_POST['lname']));
    $phone_number = htmlspecialchars(trim($_POST['phone_number']));
    $password = trim($_POST['password']);

    // Basic validation
    if (empty($fname) || empty($lname) || empty($phone_number)) {
        $error_message = "All fields are required except for the password.";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $fname) || !preg_match('/^[a-zA-Z\s]+$/', $lname)) {
        $error_message = "First and Last Name should contain only letters and spaces.";
    } elseif (!preg_match('/^\+?\d{10,15}$/', $phone_number)) {
        $error_message = "Invalid phone number format.";
    } elseif (!empty($password) && strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        // If a new password is provided, hash it; otherwise, retain the old password
        if (!empty($password)) {
            $password_hashed = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE User SET fname = ?, lname = ?, phone_number = ?, password = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ssssi", $fname, $lname, $phone_number, $password_hashed, $user_id);
        } else {
            $update_sql = "UPDATE User SET fname = ?, lname = ?, phone_number = ? WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("sssi", $fname, $lname, $phone_number, $user_id);
        }

        if ($update_stmt->execute()) {
            $success_message = "Profile updated successfully.";
            $update_stmt->close();
        } else {
            $error_message = "Failed to update profile. Please try again.";
        }

        // Refresh user data
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | LaundroSphere</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        
        
        .content {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-card {
            background-color: white;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .btn-save {
            background-color: #8c2f6f;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-save:hover {
            background-color: var(--secondary-color);
        }

        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            color: white;
        }

        .message.success {
            background-color: #28a745;
        }

        .message.error {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include 'user_navbar.php'; ?>
    

    

    <div class="content">
        <h2>Update Your Profile</h2>

        <?php if (isset($success_message)): ?>
            <div class="message success"><?= htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <form method="POST" action="user_profile.php">
                <div class="mb-3">
                    <label for="fname" class="form-label">First Name</label>
                    <input type="text" name="fname" id="fname" class="form-control" value="<?= htmlspecialchars($user['fname']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="lname" class="form-label">Last Name</label>
                    <input type="text" name="lname" id="lname" class="form-control" value="<?= htmlspecialchars($user['lname']); ?>" required>
                </div>
                
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Phone Number</label>
                    <input type="text" name="phone_number" id="phone_number" class="form-control" value="<?= htmlspecialchars($user['phone_number']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" name="email" id="email" class="form-control" value="<?= htmlspecialchars($user['email']); ?>"readonly>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">New Password (Leave empty to retain old password)</label>
                    <input type="password" name="password" id="password" class="form-control">
                </div>
                <button type="submit" class="btn-save w-100">Update Profile</button>
            </form>
        </div>
        <div class="reminder">
            <p>Remember to click "Update Profile" to save any changes you have made.</p>
        </div>
    </div>
</body>
</html>
