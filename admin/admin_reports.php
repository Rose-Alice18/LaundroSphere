<?php
// Enable error reporting for debugging (remove in production)
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

// Function to validate date format
function validate_date($date)
{
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : null;
}

// Handle delete action for orders
if (isset($_GET['delete_order'])) {
    $booking_id = filter_input(INPUT_GET, 'delete_order', FILTER_VALIDATE_INT);
    if ($booking_id) {
        // Fetch the booking to check its status and company existence
        $check_query = "
            SELECT b.status, b.company_id
            FROM bookings b
            WHERE b.booking_id = ?
        ";
        $check_stmt = $conn->prepare($check_query);
        if ($check_stmt) {
            $check_stmt->bind_param("i", $booking_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            if ($check_result->num_rows > 0) {
                $booking = $check_result->fetch_assoc();
                $status = strtolower($booking['status']);
                $company_id = $booking['company_id'];

                // Check if company exists
                $company_check_query = "SELECT company_id FROM company WHERE company_id = ?";
                $company_check_stmt = $conn->prepare($company_check_query);
                if ($company_check_stmt) {
                    $company_check_stmt->bind_param("i", $company_id);
                    $company_check_stmt->execute();
                    $company_check_result = $company_check_stmt->get_result();
                    $company_exists = $company_check_result->num_rows > 0;
                    $company_check_stmt->close();
                } else {
                    $company_exists = false;
                }

                // Allow deletion if status is 'completed' or company does not exist
                if ($status === 'completed' || !$company_exists) {
                    // Delete the booking
                    $delete_query = "DELETE FROM bookings WHERE booking_id = ?";
                    $delete_stmt = $conn->prepare($delete_query);
                    if ($delete_stmt) {
                        $delete_stmt->bind_param("i", $booking_id);
                        if ($delete_stmt->execute()) {
                            $success_message = "Order (Booking ID: $booking_id) deleted successfully.";
                        } else {
                            $error_message = "Failed to delete the order. Please try again.";
                        }
                        $delete_stmt->close();
                    } else {
                        $error_message = "Failed to prepare the delete statement.";
                    }
                } else {
                    $error_message = "Cannot delete the order. Only completed orders or orders from non-existent companies can be deleted.";
                }
            } else {
                $error_message = "Order not found.";
            }
            $check_stmt->close();
        } else {
            $error_message = "Failed to prepare the booking check statement.";
        }
    } else {
        $error_message = "Invalid order ID.";
    }

    // Redirect to avoid resubmission
    header("Location: admin_reports.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Reports | LaundroSphere</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Base Styles */
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
            margin-left: 280px; /* Adjust this value based on your sidebar's width */
            transition: margin-left 0.3s ease;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin: 20px;
                padding: 10px;
            }
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        /* Tab Styles */
        .tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .tabs button {
            background-color: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .tabs button.active, .tabs button:hover {
            background-color: #2980b9;
        }

        /* Filter Form Styles */
        .filter-form {
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
        }
        .filter-form label {
            display: flex;
            flex-direction: column;
            font-weight: bold;
            color: #333;
        }
        .filter-form input[type="date"], .filter-form input[type="text"], .filter-form select {
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            min-width: 150px;
        }
        .filter-form button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .filter-form button:hover {
            background-color: #2980b9;
        }

        /* Alert Styles */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        .alert-success {
            background-color: #dff0d8;
            color: #3c763d;
        }
        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
        }

        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            table-layout: fixed;
            word-wrap: break-word;
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
        .actions {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .actions a {
            background-color: #e74c3c;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: bold;
        }
        .actions a:hover {
            background-color: #c0392b;
        }
        .no-records {
            text-align: center;
            color: #888;
            padding: 20px 0;
        }
        @media (max-width: 768px) {
            table th, table td {
                padding: 8px;
                font-size: 14px;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Function to handle tab switching
        function openTab(evt, tabName) {
            var i, tabcontent, tablinks;
            tabcontent = document.getElementsByClassName("tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
            }
            tablinks = document.getElementsByClassName("tab-button");
            for (i = 0; i < tablinks.length; i++) {
                tablinks[i].className = tablinks[i].className.replace(" active", "");
            }
            document.getElementById(tabName).style.display = "block";
            evt.currentTarget.className += " active";
        }

        // Function to handle delete confirmation
        function confirmDelete(orderId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to undo this action!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#3498db',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `admin_reports.php?delete_order=${orderId}`;
                }
            });
        }

        // Initialize the first tab
        window.onload = function() {
            document.getElementsByClassName('tab-button')[0].click();
        }
    </script>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="main-content">
        <h2>Admin Reports</h2>
        
        <!-- Display Success or Error Messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message); ?>
            </div>
        <?php elseif (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Tabs for Different Report Categories -->
        <div class="tabs">
            <button class="tab-button" onclick="openTab(event, 'Orders')">Orders</button>
            <button class="tab-button" onclick="openTab(event, 'ServiceTypes')">Service Types</button>
            <button class="tab-button" onclick="openTab(event, 'Customers')">Customers</button>
            <button class="tab-button" onclick="openTab(event, 'Companies')">Companies</button>
            <button class="tab-button" onclick="openTab(event, 'Comments')">Comments</button>
            <button class="tab-button" onclick="openTab(event, 'Feedback')">Feedback</button>
        </div>

        <!-- Orders Report Tab -->
        <div id="Orders" class="tab-content" style="display:none;">
            <!-- Filter Form -->
            <form method="POST" class="filter-form">
                <label>
                    From Date:
                    <input type="date" name="from_date_orders" value="<?= isset($_POST['from_date_orders']) ? htmlspecialchars($_POST['from_date_orders']) : ''; ?>" required>
                </label>
                <label>
                    To Date:
                    <input type="date" name="to_date_orders" value="<?= isset($_POST['to_date_orders']) ? htmlspecialchars($_POST['to_date_orders']) : ''; ?>" required>
                </label>
                <label>
                    Search:
                    <input type="text" name="search_term_orders" placeholder="Search by Order ID or Customer Name" value="<?= isset($_POST['search_term_orders']) ? htmlspecialchars($_POST['search_term_orders']) : ''; ?>">
                </label>
                <button type="submit" name="filter_orders">Filter Orders</button>
            </form>

            <?php
                // Handle filter for Orders
                $from_date_orders = '';
                $to_date_orders = '';
                $search_term_orders = '';

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_orders'])) {
                    $from_date_orders = validate_date($_POST['from_date_orders'] ?? '');
                    $to_date_orders = validate_date($_POST['to_date_orders'] ?? '');
                    $search_term_orders = trim($_POST['search_term_orders'] ?? '');

                    if (!$from_date_orders || !$to_date_orders || strtotime($from_date_orders) > strtotime($to_date_orders)) {
                        echo "<div class='alert alert-danger'>Invalid date range. Please ensure the 'From' date is earlier than the 'To' date.</div>";
                    } else {
                        // Fetch Orders based on filters
                        $order_query = "
                            SELECT 
                                b.booking_id,
                                CONCAT(u.fname, ' ', u.lname) AS customer_name,
                                c.company_name,
                                b.status,
                                b.pickup_date,
                                b.delivery_date,
                                b.total_price,
                                b.created_at
                            FROM booking b
                            LEFT JOIN users u ON b.user_id = u.user_id
                            LEFT JOIN company c ON b.company_id = c.company_id
                            WHERE b.created_at BETWEEN ? AND ?
                        ";
                        if (!empty($search_term_orders)) {
                            $order_query .= " AND (b.booking_id LIKE ? OR CONCAT(u.fname, ' ', u.lname) LIKE ?)";
                        }
                        $order_query .= " ORDER BY b.created_at DESC";

                        $order_stmt = $conn->prepare($order_query);
                        if ($order_stmt) {
                            if (!empty($search_term_orders)) {
                                $search_wildcard_orders = "%$search_term_orders%";
                                $order_stmt->bind_param("ssss", $from_date_orders, $to_date_orders, $search_wildcard_orders, $search_wildcard_orders);
                            } else {
                                $order_stmt->bind_param("ss", $from_date_orders, $to_date_orders);
                            }
                            $order_stmt->execute();
                            $order_result = $order_stmt->get_result();
                        } else {
                            echo "<div class='alert alert-danger'>Failed to prepare orders query: " . htmlspecialchars($conn->error) . "</div>";
                        }
                    }
                }
            ?>

            <!-- Orders Table -->
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Company Name</th>
                        <th>Status</th>
                        <th>Pickup Date</th>
                        <th>Delivery Date</th>
                        <th>Total Price (GHS)</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_orders']) && empty($error_message)) {
                            if (isset($order_result) && $order_result->num_rows > 0) {
                                while ($order = $order_result->fetch_assoc()) {
                                    // Determine if the order can be deleted
                                    $can_delete = false;
                                    if (strtolower($order['status']) === 'completed') {
                                        $can_delete = true;
                                    } else {
                                        // Check if the company exists
                                        $company_id = $order['company_id'];
                                        if ($company_id) {
                                            $company_check_query = "SELECT company_id FROM company WHERE company_id = ?";
                                            $company_check_stmt = $conn->prepare($company_check_query);
                                            if ($company_check_stmt) {
                                                $company_check_stmt->bind_param("i", $company_id);
                                                $company_check_stmt->execute();
                                                $company_check_result = $company_check_stmt->get_result();
                                                if ($company_check_result->num_rows === 0) {
                                                    $can_delete = true;
                                                }
                                                $company_check_stmt->close();
                                            }
                                        } else {
                                            // If company_id is NULL or invalid, allow deletion
                                            $can_delete = true;
                                        }
                                    }
                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($order['booking_id']); ?></td>
                                        <td><?= htmlspecialchars($order['customer_name']); ?></td>
                                        <td><?= htmlspecialchars($order['company_name'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars(ucfirst($order['status'])); ?></td>
                                        <td><?= htmlspecialchars($order['pickup_date']); ?></td>
                                        <td><?= htmlspecialchars($order['delivery_date']); ?></td>
                                        <td><?= number_format($order['total_price'], 2); ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($order['created_at']))); ?></td>
                                        <td class="actions">
                                            <?php if ($can_delete): ?>
                                                <a href="#" onclick="confirmDelete(<?= htmlspecialchars($order['booking_id']); ?>)">Delete</a>
                                            <?php else: ?>
                                                <a href="#" style="background-color: #95a5a6; cursor: not-allowed;">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='9' class='no-records'>No orders found for the selected date range.</td></tr>";
                            }
                            if (isset($order_stmt)) { $order_stmt->close(); }
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Service Types Report Tab -->
        <div id="ServiceTypes" class="tab-content" style="display:none;">
            <!-- Filter Form -->
            <form method="POST" class="filter-form">
                <label>
                    From Date:
                    <input type="date" name="from_date_servicetypes" value="<?= isset($_POST['from_date_servicetypes']) ? htmlspecialchars($_POST['from_date_servicetypes']) : ''; ?>" required>
                </label>
                <label>
                    To Date:
                    <input type="date" name="to_date_servicetypes" value="<?= isset($_POST['to_date_servicetypes']) ? htmlspecialchars($_POST['to_date_servicetypes']) : ''; ?>" required>
                </label>
                <label>
                    Search:
                    <input type="text" name="search_term_servicetypes" placeholder="Search by Service Type Name" value="<?= isset($_POST['search_term_servicetypes']) ? htmlspecialchars($_POST['search_term_servicetypes']) : ''; ?>">
                </label>
                <button type="submit" name="filter_servicetypes">Filter Service Types</button>
            </form>

            <?php
                // Handle filter for Service Types
                $from_date_servicetypes = '';
                $to_date_servicetypes = '';
                $search_term_servicetypes = '';

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_servicetypes'])) {
                    $from_date_servicetypes = validate_date($_POST['from_date_servicetypes'] ?? '');
                    $to_date_servicetypes = validate_date($_POST['to_date_servicetypes'] ?? '');
                    $search_term_servicetypes = trim($_POST['search_term_servicetypes'] ?? '');

                    if (!$from_date_servicetypes || !$to_date_servicetypes || strtotime($from_date_servicetypes) > strtotime($to_date_servicetypes)) {
                        echo "<div class='alert alert-danger'>Invalid date range. Please ensure the 'From' date is earlier than the 'To' date.</div>";
                    } else {
                        // Fetch Service Types based on filters
                        $servicetype_query = "
                            SELECT st.service_type_id, st.service_type_name, st.created_at, COUNT(s.service_id) AS service_count
                            FROM servicetype st
                            LEFT JOIN service s ON st.service_type_id = s.service_type_id
                            WHERE st.created_at BETWEEN ? AND ?
                        ";
                        if (!empty($search_term_servicetypes)) {
                            $servicetype_query .= " AND st.service_type_name LIKE ?";
                        }
                        $servicetype_query .= " GROUP BY st.service_type_id, st.service_type_name, st.created_at ORDER BY st.created_at DESC";

                        $servicetype_stmt = $conn->prepare($servicetype_query);
                        if ($servicetype_stmt) {
                            if (!empty($search_term_servicetypes)) {
                                $search_wildcard_servicetypes = "%$search_term_servicetypes%";
                                $servicetype_stmt->bind_param("sss", $from_date_servicetypes, $to_date_servicetypes, $search_wildcard_servicetypes);
                            } else {
                                $servicetype_stmt->bind_param("ss", $from_date_servicetypes, $to_date_servicetypes);
                            }
                            $servicetype_stmt->execute();
                            $servicetype_result = $servicetype_stmt->get_result();
                        } else {
                            echo "<div class='alert alert-danger'>Failed to prepare service types query: " . htmlspecialchars($conn->error) . "</div>";
                        }
                    }
                }
            ?>

            <!-- Service Types Table -->
            <table>
                <thead>
                    <tr>
                        <th>Service Type ID</th>
                        <th>Service Type Name</th>
                        <th>Number of Services</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_servicetypes']) && empty($error_message)) {
                            if (isset($servicetype_result) && $servicetype_result->num_rows > 0) {
                                while ($stype = $servicetype_result->fetch_assoc()) {
                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($stype['service_type_id']); ?></td>
                                        <td><?= htmlspecialchars($stype['service_type_name']); ?></td>
                                        <td><?= htmlspecialchars($stype['service_count']); ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($stype['created_at']))); ?></td>
                                    </tr>
                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='4' class='no-records'>No service types found for the selected date range.</td></tr>";
                            }
                            if (isset($servicetype_stmt)) { $servicetype_stmt->close(); }
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Customers Report Tab -->
        <div id="Customers" class="tab-content" style="display:none;">
            <!-- Filter Form -->
            <form method="POST" class="filter-form">
                <label>
                    From Date:
                    <input type="date" name="from_date_customers" value="<?= isset($_POST['from_date_customers']) ? htmlspecialchars($_POST['from_date_customers']) : ''; ?>" required>
                </label>
                <label>
                    To Date:
                    <input type="date" name="to_date_customers" value="<?= isset($_POST['to_date_customers']) ? htmlspecialchars($_POST['to_date_customers']) : ''; ?>" required>
                </label>
                <label>
                    Search:
                    <input type="text" name="search_term_customers" placeholder="Search by Name or Email" value="<?= isset($_POST['search_term_customers']) ? htmlspecialchars($_POST['search_term_customers']) : ''; ?>">
                </label>
                <button type="submit" name="filter_customers">Filter Customers</button>
            </form>

            <?php
                // Handle filter for Customers
                $from_date_customers = '';
                $to_date_customers = '';
                $search_term_customers = '';

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_customers'])) {
                    $from_date_customers = validate_date($_POST['from_date_customers'] ?? '');
                    $to_date_customers = validate_date($_POST['to_date_customers'] ?? '');
                    $search_term_customers = trim($_POST['search_term_customers'] ?? '');

                    if (!$from_date_customers || !$to_date_customers || strtotime($from_date_customers) > strtotime($to_date_customers)) {
                        echo "<div class='alert alert-danger'>Invalid date range. Please ensure the 'From' date is earlier than the 'To' date.</div>";
                    } else {
                        // Fetch Customers based on filters
                        $customer_query = "
                            SELECT u.user_id, CONCAT(u.fname, ' ', u.lname) AS customer_name, u.email, u.status, u.created_at
                            FROM users u
                            WHERE u.role != 'admin' AND u.created_at BETWEEN ? AND ?
                        ";
                        if (!empty($search_term_customers)) {
                            $customer_query .= " AND (CONCAT(u.fname, ' ', u.lname) LIKE ? OR u.email LIKE ?)";
                        }
                        $customer_query .= " ORDER BY u.created_at DESC";

                        $customer_stmt = $conn->prepare($customer_query);
                        if ($customer_stmt) {
                            if (!empty($search_term_customers)) {
                                $search_wildcard_customers = "%$search_term_customers%";
                                $customer_stmt->bind_param("ssss", $from_date_customers, $to_date_customers, $search_wildcard_customers, $search_wildcard_customers);
                            } else {
                                $customer_stmt->bind_param("ss", $from_date_customers, $to_date_customers);
                            }
                            $customer_stmt->execute();
                            $customer_result = $customer_stmt->get_result();
                        } else {
                            echo "<div class='alert alert-danger'>Failed to prepare customers query: " . htmlspecialchars($conn->error) . "</div>";
                        }
                    }
                }
            ?>

            <!-- Customers Table -->
            <table>
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Registered At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_customers']) && empty($error_message)) {
                            if (isset($customer_result) && $customer_result->num_rows > 0) {
                                while ($customer = $customer_result->fetch_assoc()) {
                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($customer['user_id']); ?></td>
                                        <td><?= htmlspecialchars($customer['customer_name']); ?></td>
                                        <td><?= htmlspecialchars($customer['email']); ?></td>
                                        <td><?= htmlspecialchars(ucfirst($customer['status'])); ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($customer['created_at']))); ?></td>
                                    </tr>
                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='5' class='no-records'>No customers found for the selected date range.</td></tr>";
                            }
                            if (isset($customer_stmt)) { $customer_stmt->close(); }
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Companies Report Tab -->
        <div id="Companies" class="tab-content" style="display:none;">
            <!-- Filter Form -->
            <form method="POST" class="filter-form">
                <label>
                    From Date:
                    <input type="date" name="from_date_companies" value="<?= isset($_POST['from_date_companies']) ? htmlspecialchars($_POST['from_date_companies']) : ''; ?>" required>
                </label>
                <label>
                    To Date:
                    <input type="date" name="to_date_companies" value="<?= isset($_POST['to_date_companies']) ? htmlspecialchars($_POST['to_date_companies']) : ''; ?>" required>
                </label>
                <label>
                    Search:
                    <input type="text" name="search_term_companies" placeholder="Search by Company Name or Email" value="<?= isset($_POST['search_term_companies']) ? htmlspecialchars($_POST['search_term_companies']) : ''; ?>">
                </label>
                <button type="submit" name="filter_companies">Filter Companies</button>
            </form>

            <?php
                // Handle filter for Companies
                $from_date_companies = '';
                $to_date_companies = '';
                $search_term_companies = '';

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_companies'])) {
                    $from_date_companies = validate_date($_POST['from_date_companies'] ?? '');
                    $to_date_companies = validate_date($_POST['to_date_companies'] ?? '');
                    $search_term_companies = trim($_POST['search_term_companies'] ?? '');

                    if (!$from_date_companies || !$to_date_companies || strtotime($from_date_companies) > strtotime($to_date_companies)) {
                        echo "<div class='alert alert-danger'>Invalid date range. Please ensure the 'From' date is earlier than the 'To' date.</div>";
                    } else {
                        // Fetch Companies based on filters
                        $company_query = "
                            SELECT c.company_id, c.company_name, c.company_email, c.status, c.created_at
                            FROM company c
                            WHERE c.created_at BETWEEN ? AND ?
                        ";
                        if (!empty($search_term_companies)) {
                            $company_query .= " AND (c.company_name LIKE ? OR c.company_email LIKE ?)";
                        }
                        $company_query .= " ORDER BY c.created_at DESC";

                        $company_stmt = $conn->prepare($company_query);
                        if ($company_stmt) {
                            if (!empty($search_term_companies)) {
                                $search_wildcard_companies = "%$search_term_companies%";
                                $company_stmt->bind_param("ssss", $from_date_companies, $to_date_companies, $search_wildcard_companies, $search_wildcard_companies);
                            } else {
                                $company_stmt->bind_param("ss", $from_date_companies, $to_date_companies);
                            }
                            $company_stmt->execute();
                            $company_result = $company_stmt->get_result();
                        } else {
                            echo "<div class='alert alert-danger'>Failed to prepare companies query: " . htmlspecialchars($conn->error) . "</div>";
                        }
                    }
                }
            ?>

            <!-- Companies Table -->
            <table>
                <thead>
                    <tr>
                        <th>Company ID</th>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Registered At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_companies']) && empty($error_message)) {
                            if (isset($company_result) && $company_result->num_rows > 0) {
                                while ($company = $company_result->fetch_assoc()) {
                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($company['company_id']); ?></td>
                                        <td><?= htmlspecialchars($company['company_name']); ?></td>
                                        <td><?= htmlspecialchars($company['company_email']); ?></td>
                                        <td><?= htmlspecialchars(ucfirst($company['status'])); ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($company['created_at']))); ?></td>
                                        <td class="actions">
                                            <a href="#" onclick="confirmDeleteCompany(<?= htmlspecialchars($company['company_id']); ?>)">Delete</a>
                                        </td>
                                    </tr>
                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='6' class='no-records'>No companies found for the selected date range.</td></tr>";
                            }
                            if (isset($company_stmt)) { $company_stmt->close(); }
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Comments Report Tab -->
        <div id="Comments" class="tab-content" style="display:none;">
            <!-- Filter Form -->
            <form method="POST" class="filter-form">
                <label>
                    From Date:
                    <input type="date" name="from_date_comments" value="<?= isset($_POST['from_date_comments']) ? htmlspecialchars($_POST['from_date_comments']) : ''; ?>" required>
                </label>
                <label>
                    To Date:
                    <input type="date" name="to_date_comments" value="<?= isset($_POST['to_date_comments']) ? htmlspecialchars($_POST['to_date_comments']) : ''; ?>" required>
                </label>
                <label>
                    Search:
                    <input type="text" name="search_term_comments" placeholder="Search by Customer Name or Order ID" value="<?= isset($_POST['search_term_comments']) ? htmlspecialchars($_POST['search_term_comments']) : ''; ?>">
                </label>
                <button type="submit" name="filter_comments">Filter Comments</button>
            </form>

            <?php
                // Handle filter for Comments
                $from_date_comments = '';
                $to_date_comments = '';
                $search_term_comments = '';

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_comments'])) {
                    $from_date_comments = validate_date($_POST['from_date_comments'] ?? '');
                    $to_date_comments = validate_date($_POST['to_date_comments'] ?? '');
                    $search_term_comments = trim($_POST['search_term_comments'] ?? '');

                    if (!$from_date_comments || !$to_date_comments || strtotime($from_date_comments) > strtotime($to_date_comments)) {
                        echo "<div class='alert alert-danger'>Invalid date range. Please ensure the 'From' date is earlier than the 'To' date.</div>";
                    } else {
                        // Fetch Comments based on filters
                        $comment_query = "
                            SELECT 
                                cm.comment_id,
                                CONCAT(u.fname, ' ', u.lname) AS customer_name,
                                b.booking_id,
                                cm.pickup_delivery_comments,
                                cm.clothing_condition,
                                cm.additional_comments,
                                cm.created_at
                            FROM comment cm
                            LEFT JOIN bookings b ON cm.booking_id = b.booking_id
                            LEFT JOIN users u ON b.user_id = u.user_id
                            WHERE cm.created_at BETWEEN ? AND ?
                        ";
                        if (!empty($search_term_comments)) {
                            $comment_query .= " AND (CONCAT(u.fname, ' ', u.lname) LIKE ? OR b.booking_id LIKE ?)";
                        }
                        $comment_query .= " ORDER BY cm.created_at DESC";

                        $comment_stmt = $conn->prepare($comment_query);
                        if ($comment_stmt) {
                            if (!empty($search_term_comments)) {
                                $search_wildcard_comments = "%$search_term_comments%";
                                $comment_stmt->bind_param("ssss", $from_date_comments, $to_date_comments, $search_wildcard_comments, $search_wildcard_comments);
                            } else {
                                $comment_stmt->bind_param("ss", $from_date_comments, $to_date_comments);
                            }
                            $comment_stmt->execute();
                            $comment_result = $comment_stmt->get_result();
                        } else {
                            echo "<div class='alert alert-danger'>Failed to prepare comments query: " . htmlspecialchars($conn->error) . "</div>";
                        }
                    }
                }
            ?>

            <!-- Comments Table -->
            <table>
                <thead>
                    <tr>
                        <th>Comment ID</th>
                        <th>Customer Name</th>
                        <th>Order ID</th>
                        <th>Pickup/Delivery Comments</th>
                        <th>Clothing Condition</th>
                        <th>Additional Comments</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_comments']) && empty($error_message)) {
                            if (isset($comment_result) && $comment_result->num_rows > 0) {
                                while ($comment = $comment_result->fetch_assoc()) {
                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($comment['comment_id']); ?></td>
                                        <td><?= htmlspecialchars($comment['customer_name'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($comment['booking_id'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($comment['pickup_delivery_comments']); ?></td>
                                        <td><?= htmlspecialchars($comment['clothing_condition']); ?></td>
                                        <td><?= htmlspecialchars($comment['additional_comments'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($comment['created_at']))); ?></td>
                                    </tr>
                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='7' class='no-records'>No comments found for the selected date range.</td></tr>";
                            }
                            if (isset($comment_stmt)) { $comment_stmt->close(); }
                        }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Feedback Report Tab -->
        <div id="Feedback" class="tab-content" style="display:none;">
            <!-- Filter Form -->
            <form method="POST" class="filter-form">
                <label>
                    From Date:
                    <input type="date" name="from_date_feedback" value="<?= isset($_POST['from_date_feedback']) ? htmlspecialchars($_POST['from_date_feedback']) : ''; ?>" required>
                </label>
                <label>
                    To Date:
                    <input type="date" name="to_date_feedback" value="<?= isset($_POST['to_date_feedback']) ? htmlspecialchars($_POST['to_date_feedback']) : ''; ?>" required>
                </label>
                <label>
                    Search:
                    <input type="text" name="search_term_feedback" placeholder="Search by Feedback ID or Customer Name" value="<?= isset($_POST['search_term_feedback']) ? htmlspecialchars($_POST['search_term_feedback']) : ''; ?>">
                </label>
                <button type="submit" name="filter_feedback">Filter Feedback</button>
            </form>

            <?php
                // Handle filter for Feedback
                $from_date_feedback = '';
                $to_date_feedback = '';
                $search_term_feedback = '';

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_feedback'])) {
                    $from_date_feedback = validate_date($_POST['from_date_feedback'] ?? '');
                    $to_date_feedback = validate_date($_POST['to_date_feedback'] ?? '');
                    $search_term_feedback = trim($_POST['search_term_feedback'] ?? '');

                    if (!$from_date_feedback || !$to_date_feedback || strtotime($from_date_feedback) > strtotime($to_date_feedback)) {
                        echo "<div class='alert alert-danger'>Invalid date range. Please ensure the 'From' date is earlier than the 'To' date.</div>";
                    } else {
                        // Fetch Feedback based on filters
                        $feedback_query = "
                            SELECT 
                                f.feedback_id,
                                CONCAT(u.fname, ' ', u.lname) AS customer_name,
                                c.company_name,
                                f.feedback_text,
                                f.created_at
                            FROM feedback f
                            LEFT JOIN users u ON f.customer_id = u.user_id
                            LEFT JOIN company c ON f.company_id = c.company_id
                            WHERE f.created_at BETWEEN ? AND ?
                        ";
                        if (!empty($search_term_feedback)) {
                            $feedback_query .= " AND (f.feedback_id LIKE ? OR CONCAT(u.fname, ' ', u.lname) LIKE ?)";
                        }
                        $feedback_query .= " ORDER BY f.created_at DESC";

                        $feedback_stmt = $conn->prepare($feedback_query);
                        if ($feedback_stmt) {
                            if (!empty($search_term_feedback)) {
                                $search_wildcard_feedback = "%$search_term_feedback%";
                                $feedback_stmt->bind_param("ssss", $from_date_feedback, $to_date_feedback, $search_wildcard_feedback, $search_wildcard_feedback);
                            } else {
                                $feedback_stmt->bind_param("ss", $from_date_feedback, $to_date_feedback);
                            }
                            $feedback_stmt->execute();
                            $feedback_result = $feedback_stmt->get_result();
                        } else {
                            echo "<div class='alert alert-danger'>Failed to prepare feedback query: " . htmlspecialchars($conn->error) . "</div>";
                        }
                    }
                }
            ?>

            <!-- Feedback Table -->
            <table>
                <thead>
                    <tr>
                        <th>Feedback ID</th>
                        <th>Customer Name</th>
                        <th>Company Name</th>
                        <th>Feedback</th>
                        <th>Created At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter_feedback']) && empty($error_message)) {
                            if (isset($feedback_result) && $feedback_result->num_rows > 0) {
                                while ($feedback = $feedback_result->fetch_assoc()) {
                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($feedback['feedback_id']); ?></td>
                                        <td><?= htmlspecialchars($feedback['customer_name'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($feedback['company_name'] ?? 'N/A'); ?></td>
                                        <td><?= htmlspecialchars($feedback['feedback_text']); ?></td>
                                        <td><?= htmlspecialchars(date('F j, Y, g:i a', strtotime($feedback['created_at']))); ?></td>
                                    </tr>
                    <?php
                                }
                            } else {
                                echo "<tr><td colspan='5' class='no-records'>No feedback found for the selected date range.</td></tr>";
                            }
                            if (isset($feedback_stmt)) { $feedback_stmt->close(); }
                        }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Function to handle company deletion confirmation
        function confirmDeleteCompany(companyId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Deleting a company will affect associated orders. Ensure it's necessary!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#3498db',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Implement deletion logic for companies if needed
                    // Currently, deletion is only handled for orders
                    Swal.fire(
                        'Deleted!',
                        'Company has been deleted.',
                        'success'
                    )
                }
            });
        }
    </script>
</body>
</html>
