<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['company_id'])) {
    header("Location: ../login.php");
    exit();
}

$company_id = intval($_SESSION['company_id']);

// Initialize filters
$from_date = '';
$to_date = '';
$filter_type = '';
$results = [];
$error_message = '';

// Sanitize and validate date input
function validate_date($date)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
}

// Filter logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $from_date = validate_date($_POST['from_date']);
    $to_date = validate_date($_POST['to_date']);

    if (!$from_date || !$to_date || strtotime($from_date) > strtotime($to_date)) {
        $error_message = "Invalid date range. Please ensure the 'From' date is earlier than the 'To' date.";
    } else {
        if (isset($_POST['filter_orders'])) {
            $filter_type = 'orders';
            $query = "SELECT b.booking_id, u.fname, u.lname, b.pickup_date, b.delivery_date, b.status, b.total_price, b.created_at 
                      FROM booking b
                      JOIN user u ON b.user_id = u.user_id
                      WHERE b.company_id = ? AND b.pickup_date BETWEEN ? AND ? 
                      ORDER BY b.pickup_date DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iss', $company_id, $from_date, $to_date);
        } elseif (isset($_POST['filter_feedback'])) {
            $filter_type = 'feedback';
            $query = "SELECT feedback_id, feedback_text, created_at 
                      FROM feedback
                      WHERE company_id = ? AND created_at BETWEEN ? AND ?
                      ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iss', $company_id, $from_date, $to_date);
        } elseif (isset($_POST['filter_comments'])) {
            $filter_type = 'comments';
            $query = "SELECT comment_id, pickup_delivery_comments, clothing_condition, additional_comments, created_at
                      FROM comment
                      WHERE company_id = ? AND created_at BETWEEN ? AND ?
                      ORDER BY created_at DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iss', $company_id, $from_date, $to_date);
        }

        if ($stmt) {
            $stmt->execute();
            $results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Report | LaundroSphere</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f7f6;
        }
        .container {
            max-width: 2000px;
            margin: 0 auto;
            padding: 75px;
            margin-left: 280px; /* Adjust this value based on your side panel's width */
            transition: margin-left 0.3s ease; /* Smooth adjustment if the panel toggles */
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0; /* Reset margin for smaller screens where the side panel might be hidden */
            }
        }

        
        form {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        form label {
            margin-right: 10px;
        }
        form input[type="date"] {
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        form button {
            padding: 10px 20px;
            background-color: #4a90e2;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        form button:hover {
            background-color: #357abd;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        table th {
            background-color: #4a90e2;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .no-records {
            text-align: center;
            color: #888;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <?php include 'company_navbar.php'; ?>
    <div class="container">
        <h1>Company Reports</h1>

        <?php if ($error_message): ?>
            <p class="error-message"><?= htmlspecialchars($error_message); ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>From: <input type="date" name="from_date" value="<?= htmlspecialchars($from_date); ?>" required></label>
            <label>To: <input type="date" name="to_date" value="<?= htmlspecialchars($to_date); ?>" required></label>
            <button type="submit" name="filter_orders">Filter Orders</button>
            <button type="submit" name="filter_feedback">Filter Feedback</button>
            <button type="submit" name="filter_comments">Filter Comments</button>
        </form>

        <?php if ($filter_type === 'orders'): ?>
            <h2>Filtered Orders</h2>
            <?php if ($results): ?>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Client</th><th>Pickup Date</th><th>Delivery Date</th><th>Status</th><th>Total Price (GHS)</th><th>Created At</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $order): ?>
                            <tr>
                                <td><?= $order['booking_id']; ?></td>
                                <td><?= htmlspecialchars($order['fname'] . ' ' . $order['lname']); ?></td>
                                <td><?= htmlspecialchars($order['pickup_date']); ?></td>
                                <td><?= htmlspecialchars($order['delivery_date']); ?></td>
                                <td><?= htmlspecialchars($order['status']); ?></td>
                                <td><?= number_format($order['total_price'], 2); ?></td>
                                <td><?= htmlspecialchars($order['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-records">No orders found for the selected date range.</p>
            <?php endif; ?>

        <?php elseif ($filter_type === 'feedback'): ?>
            <h2>Filtered Feedback</h2>
            <?php if ($results): ?>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Feedback</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $feedback): ?>
                            <tr>
                                <td><?= $feedback['feedback_id']; ?></td>
                                <td><?= htmlspecialchars($feedback['feedback_text']); ?></td>
                                <td><?= htmlspecialchars($feedback['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-records">No feedback found for the selected date range.</p>
            <?php endif; ?>

        <?php elseif ($filter_type === 'comments'): ?>
            <h2>Filtered Comments</h2>
            <?php if ($results): ?>
                <table>
                    <thead>
                        <tr><th>ID</th><th>Pickup/Delivery Comments</th><th>Clothing Condition</th><th>Additional Comments</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $comment): ?>
                            <tr>
                                <td><?= $comment['comment_id']; ?></td>
                                <td><?= htmlspecialchars($comment['pickup_delivery_comments']); ?></td>
                                <td><?= htmlspecialchars($comment['clothing_condition']); ?></td>
                                <td><?= htmlspecialchars($comment['additional_comments'] ?? 'N/A'); ?></td>
                                <td><?= htmlspecialchars($comment['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-records">No comments found for the selected date range.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
