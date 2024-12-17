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

// Handle addition of new service type
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_service_type'])) {
    // Retrieve and sanitize input
    $service_type_name = trim($_POST['service_type_name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    // Validate inputs
    if (empty($service_type_name)) {
        $error_message = "Service Type Name is required.";
    } else {
        // Check if the service type already exists
        $check_query = "SELECT COUNT(*) AS count FROM servicetype WHERE service_type_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $service_type_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_data = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($check_data['count'] > 0) {
            $error_message = "Service Type Name already exists. Please choose a different name.";
        } else {
            // Insert new service type
            $insert_query = "INSERT INTO servicetype (service_type_name, description, created_at) VALUES (?, ?, NOW())";
            $insert_stmt = $conn->prepare($insert_query);
            $insert_stmt->bind_param("ss", $service_type_name, $description);

            if ($insert_stmt->execute()) {
                $success_message = "Service Type added successfully.";
            } else {
                $error_message = "Failed to add Service Type. Please try again.";
            }
            $insert_stmt->close();
        }
    }
}

// Fetch all service types
$query = "SELECT service_type_id, service_type_name, description, created_at FROM servicetype ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);

// Handle delete action
if (isset($_GET['delete'])) {
    $service_type_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($service_type_id) {
        // Check if the service type is associated with any services
        $check_query = "SELECT COUNT(*) AS count FROM service WHERE service_type_id = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("i", $service_type_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_data = $check_result->fetch_assoc();
        $check_stmt->close();

        if ($check_data['count'] > 0) {
            $error_message = "Cannot delete this service type as it is associated with existing services.";
        } else {
            // Delete the service type with prepared statements
            $deleteQuery = "DELETE FROM servicetype WHERE service_type_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $service_type_id);
            if ($stmt->execute()) {
                $success_message = "Service Type deleted successfully.";
            } else {
                $error_message = "Failed to delete the Service Type. Please try again.";
            }
            $stmt->close();
            header("Location: service_management.php");
            exit();
        }
    } else {
        $error_message = "Invalid Service Type ID.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management | LaundroSphere</title>
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

        /* Add Service Type Form */
        .add-service-type {
            margin-bottom: 30px;
        }

        .add-service-type h3 {
            margin-bottom: 15px;
            color: #3498db;
        }

        .add-service-type form {
            display: flex;
            flex-direction: column;
        }

        .add-service-type label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #3498db;
        }

        .add-service-type input[type="text"],
        .add-service-type textarea {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 15px;
            font-size: 1rem;
            resize: vertical;
        }

        .add-service-type button {
            align-self: flex-start;
            padding: 10px 20px;
            background-color: #3498db;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .add-service-type button:hover {
            background-color: #4a148c;
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
            transition: background-color 0.3s ease;
        }

        table .actions a:hover {
            background-color: #c0392b;
        }

        .no-records {
            text-align: center;
            color: #888;
        }

        /* Success and Error Messages */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
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
            .main-content {
                margin-left: 0;
                padding: 10px;
            }

            table th, table td {
                padding: 8px;
                font-size: 14px;
            }

            table th, table td {
                display: block;
            }

            table th {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            table tr {
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
                background-color: #fff;
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
                color: #333;
            }

            /* Adjust Add Service Type Form for Mobile */
            .add-service-type form {
                flex-direction: column;
            }

            .add-service-type button {
                width: 100%;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(serviceTypeId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to undo this action!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#6a1b9a',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `service_management.php?delete=${serviceTypeId}`;
                }
            });
        }
    </script>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="main-content">
        <h2>Service Management</h2>
        <?php if (!empty($success_message)) { ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php } elseif (!empty($error_message)) { ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php } ?>

        <!-- Add Service Type Form -->
        <div class="add-service-type">
            <h3>Add New Service Type</h3>
            <form method="POST" action="">
                <input type="hidden" name="add_service_type" value="1">
                <label for="service_type_name">Service Type Name</label>
                <input type="text" id="service_type_name" name="service_type_name" placeholder="Enter service type name" required>

                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4" placeholder="Enter description (optional)"></textarea>

                <button type="submit">Add Service Type</button>
            </form>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Service Type Name</th>
                    <th>Description</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['service_type_id']) ?></td>
                            <td><?= htmlspecialchars($row['service_type_name']) ?></td>
                            <td><?= htmlspecialchars($row['description']) ?></td>
                            <td><?= htmlspecialchars(date("F j, Y, g:i a", strtotime($row['created_at']))) ?></td>
                            <td class="actions">
                                <a href="#" onclick="confirmDelete(<?= $row['service_type_id'] ?>)">Delete</a>
                                <!--
                                <a href="edit_service_type.php?id=<?= $row['service_type_id'] ?>">Edit</a>
                                -->
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="no-records">No service types found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
