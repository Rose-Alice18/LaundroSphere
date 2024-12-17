<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['company_id'])) {
    header('Location: ../login.php');
    exit();
}

$company_id = intval($_SESSION['company_id']);

// Fetch company data
$sql_company = "
    SELECT company_name, company_email, company_address, company_phone_number, short_code, status 
    FROM company 
    WHERE company_id = ?";
$stmt = $conn->prepare($sql_company);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
$company = $result->fetch_assoc();
$stmt->close();

if (!$company) {
    die("Company not found.");
}

// Fetch services provided by the company (removing duplicates using DISTINCT)
$sql_company_services = "
    SELECT DISTINCT st.service_type_name 
    FROM serviceforitem sfi
    JOIN servicetype st ON sfi.servicetype_id = st.service_type_id
    WHERE sfi.company_id = ?";
$stmt = $conn->prepare($sql_company_services);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$services_result = $stmt->get_result();
$company_services = $services_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Input sanitization
    $company_name = htmlspecialchars(trim($_POST['company_name'] ?? ''));
    $company_address = htmlspecialchars(trim($_POST['company_address'] ?? ''));
    $company_phone_number = htmlspecialchars(trim($_POST['company_phone_number'] ?? ''));
    $short_code = htmlspecialchars(trim($_POST['short_code'] ?? ''));
    $status = isset($_POST['toggle_availability']) ? 'Active' : 'Inactive';
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($company_name) || empty($company_address) || empty($company_phone_number)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!preg_match('/^\+?\d{10,15}$/', $company_phone_number)) {
        $error_message = "Invalid phone number format.";
    } else {
        // Prepare SQL for updating profile
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_query = "
                UPDATE company 
                SET company_name = ?, company_address = ?, company_phone_number = ?, short_code = ?, status = ?, password = ? 
                WHERE company_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param(
                "ssssssi",
                $company_name,
                $company_address,
                $company_phone_number,
                $short_code,
                $status,
                $hashed_password,
                $company_id
            );
        } else {
            $update_query = "
                UPDATE company 
                SET company_name = ?, company_address = ?, company_phone_number = ?, short_code = ?, status = ? 
                WHERE company_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param(
                "sssssi",
                $company_name,
                $company_address,
                $company_phone_number,
                $short_code,
                $status,
                $company_id
            );
        }

        if ($stmt->execute()) {
            $success_message = "Profile updated successfully.";
            // Reload updated data
            $company['company_name'] = $company_name;
            $company['company_address'] = $company_address;
            $company['company_phone_number'] = $company_phone_number;
            $company['short_code'] = $short_code;
            $company['status'] = $status;
        } else {
            $error_message = "Failed to update profile. Please try again.";
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
    <title>Company Profile | LaundroSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #357abd;
            --background-color: #f3f4f6;
            --card-background: #ffffff;
            --text-color: #333;
            --border-radius: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-color);
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            margin-left: 300px; /* Default for side panel open */
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0;
            }
        }

        .card {
            background-color: var(--card-background);
            border-radius: var(--border-radius);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .form-group label {
            font-weight: bold;
            display: block;
            margin-bottom: 8px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }

        .btn {
            padding: 12px 20px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: var(--secondary-color);
        }

        .services-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }

        .service-card {
            background-color: var(--primary-color);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-switch .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.4s;
            border-radius: 34px;
        }

        .toggle-switch .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: 0.4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: var(--primary-color);
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .reminder {
            margin-top: 20px;
            font-style: italic;
            color: #888;
        }
    </style>
</head>
<body>
    <?php include 'company_navbar.php'; ?>
    <div class="container">
        <h1>Company Profile</h1>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message); ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Profile Section -->
        <div class="card">
            <form method="POST" action="company_profile.php">
                <div class="form-group">
                    <label for="company_name">Company Name</label>
                    <input type="text" name="company_name" class="form-control" value="<?= htmlspecialchars($company['company_name']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="company_address">Company Address</label>
                    <input type="text" name="company_address" class="form-control" value="<?= htmlspecialchars($company['company_address']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="company_phone_number">Phone Number</label>
                    <input type="text" name="company_phone_number" class="form-control" value="<?= htmlspecialchars($company['company_phone_number']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="short_code">Short Quote</label>
                    <textarea name="short_code" class="form-control" rows="3" required><?= htmlspecialchars($company['short_code']); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="text" name="email" id="email" class="form-control" value="<?= htmlspecialchars($company['company_email']); ?>"readonly>
                </div>

                <div class="form-group">
                    <label for="password">New Password (Leave blank to keep current password)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <div class="form-group">
                    <label for="toggle_availability">Availability</label>
                    <p>Your visibility will determine if customers can place orders. Toggle your status below:</p>
                    <label class="toggle-switch">
                        <input type="checkbox" name="toggle_availability" <?= $company['status'] === 'Active' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
                <button type="submit" name="update_profile" class="btn">Update Profile</button>
            </form>
        </div>

        <!-- Services Section -->
        <div class="card">
            <h3>Current Services</h3>
            <p>To fully update services, visit <strong>Service Management</strong> in the dashboard.</p>
            <div class="services-grid">
                <?php foreach ($company_services as $service): ?>
                    <div class="service-card"><?= htmlspecialchars($service['service_type_name']); ?></div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="reminder">
            <p>Remember to click "Update Profile" to save any changes you have made.</p>
        </div>
    </div>
</body>
</html>

