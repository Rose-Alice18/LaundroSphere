<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['company_id'])) {
    header('Location: ../login.php');
    exit();
}

$company_id = intval($_SESSION['company_id']);
$message = "";

// Handle order deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])) {
    $booking_id = intval($_POST['booking_id']);

    // Validate the order status before deletion
    $check_query = "SELECT status FROM booking WHERE booking_id = ? AND company_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param('ii', $booking_id, $company_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    $stmt->close();

    if ($order && $order['status'] === 'Completed') {
        $delete_query = "DELETE FROM booking WHERE booking_id = ? AND company_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('ii', $booking_id, $company_id);
        if ($stmt->execute()) {
            $message = "Order deleted successfully.";
        } else {
            $message = "Failed to delete the order. Please try again.";
        }
        $stmt->close();
    } else {
        $message = "Only completed orders can be deleted.";
    }
}

// Fetch orders for the company
$query = "
    SELECT 
        b.booking_id, 
        b.delivery_location AS delivery_address,
        b.status AS booking_status, 
        b.pickup_date AS booking_date, 
        b.total_price,
        u.fname, u.lname
    FROM booking b
    JOIN user u ON b.user_id = u.user_id
    WHERE b.company_id = ?
    ORDER BY b.created_at DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $company_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_orders = count($orders);
$completed_orders = count(array_filter($orders, function ($order) {
    return $order['booking_status'] === 'Completed';
}));
$pending_orders = $total_orders - $completed_orders;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Status | LaundroSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
            animation: fadeIn 1s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            width: 30%;
        }
        .stat-card h3 {
            margin: 0 0 10px 0;
            color: #8c2f6f;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            animation: tableSlideIn 0.8s ease-out;
        }

        @keyframes tableSlideIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        table th, table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #8c2f6f;
            color: white;
            font-weight: 600;
        }
        table tr:last-child td {
            border-bottom: none;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        .btn-delete {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-delete:hover {
            background-color: #c0392b;
        }
        .empty-message {
            text-align: center;
            color: #7f8c8d;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'company_navbar.php'; ?>
    <div class="content">
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <p><?= htmlspecialchars($total_orders); ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed Orders</h3>
                <p><?= htmlspecialchars($completed_orders); ?></p>
            </div>
            <div class="stat-card">
                <h3>Uncompleted Orders</h3>
                <p><?= htmlspecialchars($pending_orders); ?></p>
            </div>
        </div>

        <h1>Order Status</h1>
        <?php if (!empty($message)): ?>
            <p class="alert alert-info"><?= htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if (count($orders) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Delivery Location</th>
                        <th>Price (GHS)</th>
                        <th>Status</th>
                        <th>Booking Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['fname'] . ' ' . $order['lname']); ?></td>
                            <td><?= htmlspecialchars($order['delivery_address']); ?></td>
                            <td><?= number_format($order['total_price'], 2); ?></td>
                            <td><?= htmlspecialchars($order['booking_status']); ?></td>
                            <td><?= date('l, F j, Y', strtotime($order['booking_date'])); ?></td>
                            <td>
                                <?php if ($order['booking_status'] === 'Completed'): ?>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete this completed order?');">
                                        <input type="hidden" name="booking_id" value="<?= $order['booking_id']; ?>">
                                        <button type="submit" name="delete_order" class="btn-delete">Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span><i>Company is working on this order</i></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-message">No orders found.</p>
        <?php endif; ?>
    </div>
</body>
</html>