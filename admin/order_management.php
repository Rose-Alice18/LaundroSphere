<?php
// Enable error reporting for debugging (Remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

include '../db_connect.php'; // Database connection

$success_message = "";
$error_message = "";

// Handle the search functionality
$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Fetch all bookings/orders with customer and company names
if (!empty($search_term)) {
    $orders_query = "
        SELECT 
            b.booking_id, 
            CONCAT(u.fname, ' ', u.lname) AS customer_name, 
            c.company_name, 
            b.status, 
            b.created_at
        FROM booking b
        JOIN user u ON b.user_id = u.user_id
        JOIN company c ON b.company_id = c.company_id
        WHERE (u.fname LIKE ? OR u.lname LIKE ? OR c.company_name LIKE ?)
        ORDER BY b.created_at DESC
    ";
    $orders_stmt = $conn->prepare($orders_query);
    if (!$orders_stmt) {
        die('MySQL prepare error: ' . mysqli_error($conn));
    }
    $search_wildcard = "%$search_term%";
    $orders_stmt->bind_param('sss', $search_wildcard, $search_wildcard, $search_wildcard);
} else {
    $orders_query = "
        SELECT 
            b.booking_id, 
            CONCAT(u.fname, ' ', u.lname) AS customer_name, 
            c.company_name, 
            b.status, 
            b.created_at
        FROM booking b
        JOIN user u ON b.user_id = u.user_id
        JOIN company c ON b.company_id = c.company_id
        ORDER BY b.created_at DESC
    ";
    $orders_stmt = $conn->prepare($orders_query);
    if (!$orders_stmt) {
        die('MySQL prepare error: ' . mysqli_error($conn));
    }
}

$orders_stmt->execute();
$orders_result = $orders_stmt->get_result();

// Handle delete action
if (isset($_GET['delete_order'])) {
    $booking_id = filter_input(INPUT_GET, 'delete_order', FILTER_VALIDATE_INT);
    if ($booking_id) {
        // Verify if the order is completed before deletion
        $verify_query = "SELECT status FROM booking WHERE booking_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("i", $booking_id);
        $verify_stmt->execute();
        $verify_result = $verify_stmt->get_result();
        $order = $verify_result->fetch_assoc();
        $verify_stmt->close();

        if ($order && strtolower($order['status']) === 'completed') {
            // Delete the order with prepared statements
            $deleteQuery = "DELETE FROM booking WHERE booking_id = ?";
            $delete_stmt = $conn->prepare($deleteQuery);
            $delete_stmt->bind_param("i", $booking_id);
            if ($delete_stmt->execute()) {
                $success_message = "Order deleted successfully.";
            } else {
                $error_message = "Failed to delete the order. Please try again.";
            }
            $delete_stmt->close();
            header("Location: orders_management.php");
            exit();
        } else {
            $error_message = "Only completed orders can be deleted.";
        }
    } else {
        $error_message = "Invalid order ID.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management | LaundroSphere</title>
    <link rel="stylesheet" href="css/admin_styles.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        .main-content {
            margin: 40px;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-left: 300px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .search-bar {
            margin-bottom: 20px;
            text-align: center;
        }

        .search-bar input[type="text"] {
            padding: 10px;
            width: 300px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .search-bar button {
            padding: 10px 20px;
            font-size: 16px;
            margin-left: 10px;
            border: none;
            background-color: #3498db;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }

        .search-bar button:hover {
            background-color: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #3498db;
            color: #fff;
        }

        table td {
            background-color: #f9f9f9;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        table .actions {
            display: flex;
            justify-content: center;
        }

        table .actions a {
            background-color: #e74c3c;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
            margin-right: 8px;
            cursor: pointer;
        }

        table .actions a:hover {
            background-color: #c0392b;
        }

        .no-records {
            text-align: center;
            color: #888;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        @media (max-width: 768px) {
            table th, table td {
                padding: 8px;
                font-size: 14px;
            }

            .search-bar input[type="text"] {
                width: 80%;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(orderId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You can only delete completed orders. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#3498db',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `orders_management.php?delete_order=${orderId}`;
                }
            });
        }
    </script>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="main-content">
        <h2>Orders Management</h2>

        <!-- Search Form -->
        <div class="search-bar">
            <form method="GET" action="orders_management.php">
                <input type="text" name="search" placeholder="Search by customer or company name..." value="<?php echo htmlspecialchars($search_term); ?>" />
                <button type="submit">Search</button>
            </form>
        </div>

        <!-- Display Success or Error Messages -->
        <?php if (!empty($success_message)) { ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php } elseif (!empty($error_message)) { ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php } ?>

        <!-- Orders Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer Name</th>
                    <th>Company Name</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($orders_result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($orders_result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['booking_id']) ?></td>
                            <td><?= htmlspecialchars($row['customer_name']) ?></td>
                            <td><?= htmlspecialchars($row['company_name']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                            <td><?= htmlspecialchars(date('F j, Y, g:i A', strtotime($row['created_at']))) ?></td>
                            <td class="actions">
                                <?php if (strtolower($row['status']) === 'completed'): ?>
                                    <a href="#" onclick="confirmDelete(<?= $row['booking_id'] ?>)">Delete</a>
                                <?php else: ?>
                                    <!-- No Delete Option for Non-Completed Orders -->
                                    <span style="color: #888;">Cannot delete: Order is being handled by company</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-records">No orders found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
