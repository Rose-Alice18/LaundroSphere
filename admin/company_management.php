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

// Fetch all companies
$query = "SELECT company_id, company_name, company_email, status FROM company ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Handle delete action
if (isset($_GET['delete'])) {
    $company_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($company_id) {
        // Delete the company with prepared statements
        $deleteQuery = "DELETE FROM company WHERE company_id = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $company_id);
        if ($stmt->execute()) {
            $success_message = "Company deleted successfully.";
        } else {
            $error_message = "Failed to delete the company. Please try again.";
        }
        $stmt->close();
        header("Location: company_management.php");
        exit();
    } else {
        $error_message = "Invalid company ID.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Management | LaundroSphere</title>
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
        }

        table .actions a:hover {
            background-color: #c0392b;
        }

        .no-records {
            text-align: center;
            color: #888;
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
        function confirmDelete(companyId) {
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
                    window.location.href = `company_management.php?delete=${companyId}`;
                }
            });
        }
    </script>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="main-content">
        <h2>Company Management</h2>
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
                    <th>Company Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['company_id']) ?></td>
                            <td><?= htmlspecialchars($row['company_name']) ?></td>
                            <td><?= htmlspecialchars($row['company_email']) ?></td>
                            <td><?= htmlspecialchars(ucfirst($row['status'])) ?></td>
                            <td class="actions">
                                <a href="#" onclick="confirmDelete(<?= $row['company_id'] ?>)">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center; color:#888;">No companies found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
