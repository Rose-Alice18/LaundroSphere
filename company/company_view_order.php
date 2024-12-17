<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['company_id'])) {
    header('Location: ../login.php');
    exit();
}

$company_id = intval($_SESSION['company_id']);
$success_message = $error_message = "";

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = intval($_POST['booking_id']);
    $new_status = htmlspecialchars(trim($_POST['new_status']));

    // Validate allowed statuses
    $allowed_statuses = ['Pending', 'InProgress', 'Completed', 'Cancelled'];
    if (!in_array($new_status, $allowed_statuses)) {
        $error_message = "Invalid status selected.";
    } else {
        // Update booking status
        $update_status_sql = "UPDATE booking SET status = ? WHERE booking_id = ? AND company_id = ?";
        $stmt = $conn->prepare($update_status_sql);
        $stmt->bind_param("sii", $new_status, $booking_id, $company_id);

        if ($stmt->execute()) {
            $success_message = "Order status updated successfully.";
        } else {
            $error_message = "Failed to update order status. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch orders for the company
$sql = "SELECT b.booking_id, b.pickup_date, b.delivery_location, b.status, b.created_at, 
               u.fname, u.lname 
        FROM booking b 
        JOIN user u ON b.user_id = u.user_id 
        WHERE b.company_id = ? 
        ORDER BY b.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Orders | LaundroSphere</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        header {
            position: fixed;
            top: 0;
            width: 100%;
            background-color: #333;
            color: #fff;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }
        header .nav-buttons a {
            color: #fff;
            text-decoration: none;
            margin: 0 10px;
            padding: 5px 10px;
            background: #444;
            border-radius: 4px;
        }
        header .nav-buttons a:hover {
            background: #555;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 50px;
            width: 250px;
            height: calc(100% - 50px);
            background-color: #222;
            padding: 20px 0;
            overflow-y: auto;
            z-index: 500;
        }
        .sidebar a {
            color: #ddd;
            text-decoration: none;
            display: block;
            padding: 10px 20px;
            border-bottom: 1px solid #333;
        }
        .sidebar a:hover, .sidebar a.active {
            background-color: #444;
            color: #fff;
        }
        .content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
            flex: 1;
        }
        .content h2 {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: #fff;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #f4f4f4;
        }
        .btn {
            padding: 8px 12px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius:  4px;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
        .status-select {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
<body>
    <header>
        <div>
            <h1>LaundroSphere</h1>
        </div>
        <div>
            <a href="../logout.php" class="btn">Logout</a>
        </div>
    </header>

    <?php include 'company_navbar.php'; ?>

    <div class="content">
        <h2>View Orders and Update Status</h2>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success_message); ?></div>
        <?php elseif (!empty($error_message)): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Pickup Date</th>
                    <th>Delivery Location</th>
                    <th>Booking Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                        <td><?= htmlspecialchars($row['pickup_date']); ?></td>
                        <td><?= htmlspecialchars($row['delivery_location']); ?></td>
                        <td><?= htmlspecialchars($row['created_at']); ?></td>
                        <td><?= ucfirst(htmlspecialchars($row['status'])); ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="booking_id" value="<?= intval($row['booking_id']); ?>">
                                <select name="new_status" class="status-select">
                                    <option value="Pending" <?= $row['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="InProgress" <?= $row['status'] === 'InProgress' ? 'selected' : ''; ?>>In Progress</option>
                                    <option value="Completed" <?= $row['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            
                                </select>
                                <button type="submit" name="update_status" class="btn">Update</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>