<?php
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

// Fetch all customers/users
$query = "SELECT user_id, fname, lname, email, phone_number, role, created_at FROM user ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Handle delete action
if (isset($_GET['delete'])) {
    $user_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($user_id) {
        // Prevent deletion of admin users
        $check_admin_query = "SELECT role FROM user WHERE user_id = ?";
        $check_admin_stmt = $conn->prepare($check_admin_query);
        $check_admin_stmt->bind_param("i", $user_id);
        $check_admin_stmt->execute();
        $check_admin_result = $check_admin_stmt->get_result();
        $user_data = $check_admin_result->fetch_assoc();
        $check_admin_stmt->close();

        if ($user_data && strtolower($user_data['role']) === 'admin') {
            $error_message = "Cannot delete an admin user.";
        } else {
            // Delete the user with prepared statements
            $deleteQuery = "DELETE FROM user WHERE user_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                $success_message = "User deleted successfully.";
            } else {
                $error_message = "Failed to delete the user. Please try again.";
            }
            $stmt->close();
            header("Location: customers_management.php");
            exit();
        }
    } else {
        $error_message = "Invalid user ID.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers Management | LaundroSphere</title>
    <link rel="stylesheet" href="css/admin_styles.css">
    <style>
        :root {
            --primary-color: #6a1b9a;
            --secondary-color: #4a90e2;
            --background-light: #f4f6f9;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
            --white: #ffffff;
            --table-header-bg: #8c2f6f;
            --table-header-text: #ffffff;
            --table-row-alt-bg: #f9f9f9;
            --table-border: #ddd;
            --success-color: #4CAF50;
            --error-color: #f44336;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: var(--background-light);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
        }

        .main-content {
            margin: 40px;
            padding: 20px;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-left: 300px;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
            font-weight: 600;
        }

        .alert-success {
            background-color: rgba(76, 175, 80, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            background-color: rgba(244, 67, 54, 0.1);
            color: var(--error-color);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--table-border);
        }

        table th {
            background-color: #3498db;
            color: var(--table-header-text);
        }

        table td {
            background-color: var(--white);
        }

        table tr:nth-child(even) td {
            background-color: var(--table-row-alt-bg);
        }

        table tr:hover td {
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
            transition: background-color 0.3s ease;
        }

        table .actions a:hover {
            background-color: #c0392b;
        }

        .no-records {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin: 20px 10px;
                padding: 15px;
            }

            table th, table td {
                padding: 8px;
                font-size: 14px;
            }

            table, table thead, table tbody, table th, table td, table tr {
                display: block;
            }

            table th {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            table tr {
                margin-bottom: 15px;
                border: 1px solid var(--table-border);
                border-radius: 8px;
                padding: 10px;
                background-color: var(--white);
                position: relative;
            }

            table td {
                border: none;
                position: relative;
                padding-left: 50%;
            }

            table td:before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                font-weight: 600;
                color: var(--text-dark);
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(userId) {
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
                    window.location.href = `customers_management.php?delete=${userId}`;
                }
            });
        }
    </script>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="main-content">
        <h2>Customers Management</h2>
        <?php if (!empty($success_message)) { ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php } elseif (!empty($error_message)) { ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php } ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>First Name</th>
                    <th>Last Name</th>
                    <th>Email</th>
                    <th>Phone Number</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td data-label="ID"><?= htmlspecialchars($row['user_id']) ?></td>
                            <td data-label="First Name"><?= htmlspecialchars($row['fname']) ?></td>
                            <td data-label="Last Name"><?= htmlspecialchars($row['lname']) ?></td>
                            <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                            <td data-label="Phone Number"><?= htmlspecialchars($row['phone_number']) ?></td>
                            <td data-label="Role"><?= htmlspecialchars(ucfirst($row['role'])) ?></td>
                            <td class="actions">
                                <?php if (strtolower($row['role']) !== 'admin'): ?>
                                    <a href="#" onclick="confirmDelete(<?= $row['user_id'] ?>)">Delete</a>
                                <?php else: ?>
                                    <span style="color: #888; font-weight: bold;">N/A</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-records">No customers found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
