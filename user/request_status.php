<?php
session_start();
require_once '../db_connect.php'; // Ensure database connection is correct

// Security check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirect to login if no user is logged in
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Fetch user request history including price and services
$query = "
    SELECT 
        b.booking_id,
        b.pickup_date,
        b.delivery_date,
        b.status,
        b.created_at,
        b.total_price,
        c.company_name,
        GROUP_CONCAT(DISTINCT st.service_type_name SEPARATOR ', ') AS services
    FROM Booking b
    LEFT JOIN Company c ON b.company_id = c.company_id
    LEFT JOIN BookingDetail bd ON b.booking_id = bd.booking_id
    LEFT JOIN ServiceForItem sfi ON bd.service_for_item_id = sfi.service_for_item_id
    LEFT JOIN ServiceType st ON sfi.servicetype_id = st.service_type_id
    WHERE b.user_id = ?
    GROUP BY b.booking_id
    ORDER BY b.created_at DESC;

";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$requests = $result->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_requests = count($requests);
$completed_requests = count(array_filter($requests, function ($req) {
    return strtolower($req['status']) === 'completed';
}));
$pending_requests = $total_requests - $completed_requests;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Status | LaundroSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        .content {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .stats-container {
            display: flex;
            justify-content: space-around;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 30%;
        }

        .stat-card h3 {
            margin: 0 0 10px;
            color: #8c2f6f;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
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

        .status {
            font-weight: bold;
            text-transform: capitalize;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .status.pending {
            background-color: #f39c12;
            color: white;
        }

        .status.completed {
            background-color: #2ecc71;
            color: white;
        }

        .status.cancelled {
            background-color: #e74c3c;
            color: white;
        }

        .empty-message {
            text-align: center;
            color: #7f8c8d;
            margin-top: 20px;
        }

        .new-service-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #8c2f6f;
            color: white;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .new-service-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.3);
        }

        .new-service-btn i {
            font-size: 24px;
        }
    </style>
</head>
<body>
    <?php include 'user_navbar.php'; ?>

    <div class="content">
        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Requests</h3>
                <p><?php echo $total_requests; ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed Requests</h3>
                <p><?php echo $completed_requests; ?></p>
            </div>
            <div class="stat-card">
                <h3>Pending Requests</h3>
                <p><?php echo $pending_requests; ?></p>
            </div>
        </div>

        <h1>Your Laundry Requests</h1>

        <?php if ($total_requests > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Pickup Date</th>
                        <th>Delivery Date</th>
                        
                        <th>Price</th>
                        <th>Status</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($request['company_name']); ?></td>
                            <td><?php echo date('l, F j, Y, g:i A', strtotime($request['pickup_date'])); ?></td>
                            <td><?php echo date('l, F j, Y, g:i A', strtotime($request['delivery_date'])); ?></td>

                            <td>₵<?php echo number_format($request['total_price'], 2); ?></td>
                            <td>
                                <span class="status <?php echo strtolower(str_replace(' ', '_', $request['status'])); ?>">
                                    <?php echo htmlspecialchars($request['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('l, F j, Y, g:i A', strtotime($request['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="empty-message">You have not made any laundry requests yet.</p>
        <?php endif; ?>
    </div>

    <a href="laundry_request.php" class="new-service-btn" title="Request New Service">
        <i class="fas fa-plus"></i>
    </a>
</body>
</html>
