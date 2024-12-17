<?php
// Start the session and check if the company is logged in
session_start();
if (!isset($_SESSION['company_id'])) {
    header('Location: ../login.php'); // Redirect if not logged in
    exit();
}

$company_id = intval($_SESSION['company_id']);
include '../db_connect.php'; // Database connection

// Fetch customers who have booked the company's services
$sql = "
    SELECT DISTINCT u.user_id, u.fname, u.lname, u.email, COUNT(b.booking_id) AS total_bookings, 
                    MIN(b.pickup_date) AS first_booking, MAX(b.delivery_date) AS last_booking
    FROM booking b
    JOIN user u ON b.user_id = u.user_id
    WHERE b.company_id = ?
    GROUP BY u.user_id, u.fname, u.lname, u.email
    ORDER BY u.fname ASC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch total customers and booking statistics
$stats_sql = "
    SELECT 
        COUNT(DISTINCT u.user_id) AS total_customers,
        COUNT(b.booking_id) AS total_bookings,
        MAX(b.delivery_date) AS last_booking
    FROM booking b
    JOIN user u ON b.user_id = u.user_id
    WHERE b.company_id = ?";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $company_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Customers | LaundroSphere</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
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
        .sidebar {
            position: fixed;
            left: 0;
            top: 50px;
            width: 250px;
            height: 100%;
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
        }
        .stats-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .stat-card {
            background-color: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            flex: 1;
            margin: 0 10px;
        }
        .stat-card h3 {
            margin: 0 0 10px;
            color: #8c2f6f;
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
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <header>
        <div>
            <h1>LaundroSphere</h1>
        </div>
    </header>
    <?php include 'company_navbar.php'; ?>

    <div class="content">
        <h2>Customers Analytics</h2>

        <div class="stats-container">
            <div class="stat-card">
                <h3>Total Customers</h3>
                <p><?= htmlspecialchars($stats['total_customers']); ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Bookings</h3>
                <p><?= htmlspecialchars($stats['total_bookings']); ?></p>
            </div>
            <div class="stat-card">
                <h3>Last Booking</h3>
                <p><?= $stats['last_booking'] ? htmlspecialchars(date('Y-m-d', strtotime($stats['last_booking']))) : 'N/A'; ?></p>
            </div>
        </div>

        <h3>Customers List</h3>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Total Bookings</th>
                    <th>First Booking</th>
                    <th>Last Booking</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                        <td><?= htmlspecialchars($row['email']); ?></td>
                        <td><?= htmlspecialchars($row['total_bookings']); ?></td>
                        <td><?= htmlspecialchars($row['first_booking']); ?></td>
                        <td><?= htmlspecialchars($row['last_booking']); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>