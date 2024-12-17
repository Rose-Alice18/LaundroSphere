<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

include "admin_navbar.php";
include '../db_connect.php'; // Database connection

// Handle delete action
if (isset($_GET['delete'])) {
    $comment_id = intval($_GET['delete']); // Secure input

    // Check if the comment is marked as read
    $check_query = "SELECT is_read FROM comment WHERE comment_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $comment = $result->fetch_assoc();
    $stmt->close();

    if ($comment && $comment['is_read'] == 1) {
        // Proceed with deletion
        $delete_query = "DELETE FROM comment WHERE comment_id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $comment_id);
        if ($stmt->execute()) {
            $success_message = "Comment deleted successfully.";
        } else {
            $error_message = "Failed to delete the comment. Please try again.";
        }
        $stmt->close();
    } else {
        $error_message = "Only comments marked as read can be deleted.";
    }
}

// Fetch all comments with associated company, user, and order details
$query = "
    SELECT c.comment_id, c.pickup_delivery_comments, c.clothing_condition, c.additional_comments, 
           c.is_read, c.booking_id, c.created_at,
           IFNULL(u.fname, 'N/A') AS user_fname, IFNULL(u.lname, 'N/A') AS user_lname,
           com.company_name
    FROM comment c
    LEFT JOIN user u ON c.user_id = u.user_id
    LEFT JOIN company com ON c.company_id = com.company_id
    ORDER BY c.created_at DESC";

$result = $conn->query($query);

if (!$result) {
    die("Error fetching comments: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments Management</title>
    <link rel="stylesheet" href="css/admin_styles.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .success-message, .error-message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: center;
        }

        .success-message {
            background-color: #2ecc71;
            color: #fff;
        }

        .error-message {
            background-color: #e74c3c;
            color: #fff;
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

        .actions a {
            background-color: #e74c3c;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
        }

        .actions a:hover {
            background-color: #c0392b;
        }

        .no-records {
            text-align: center;
            color: #888;
        }
    </style>
    <script>
        function confirmDelete(commentId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Only read comments can be deleted. This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#3498db',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `comment_management.php?delete=${commentId}`;
                }
            });
        }
    </script>
</head>
<body>
    <div class="main-content">
        <h2>Comments Management</h2>

        <!-- Success/Error Messages -->
        <?php if (isset($success_message)): ?>
            <div class="success-message"><?= htmlspecialchars($success_message); ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Comments Table -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Company</th>
                    <th>User</th>
                    <th>Order ID</th>
                    <th>Comments</th>
                    <th>Clothing Condition</th>
                    <th>Additional Comments</th>
                    <th>Read</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['comment_id']); ?></td>
                            <td><?= htmlspecialchars($row['company_name']); ?></td>
                            <td><?= htmlspecialchars($row['user_fname'] . ' ' . $row['user_lname']); ?></td>
                            <td><?= htmlspecialchars($row['booking_id']); ?></td>
                            <td><?= htmlspecialchars($row['pickup_delivery_comments']); ?></td>
                            <td><?= htmlspecialchars($row['clothing_condition']); ?></td>
                            <td><?= htmlspecialchars($row['additional_comments']); ?></td>
                            <td><?= $row['is_read'] ? 'Yes' : 'No'; ?></td>
                            <td><?= htmlspecialchars($row['created_at']); ?></td>
                            <td class="actions">
                                <?php if ($row['is_read'] == 1): ?>
                                    <a href="#" onclick="confirmDelete(<?= $row['comment_id']; ?>)">Delete</a>
                                <?php else: ?>
                                    <span style="color: #888;">Cannot delete</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="10" class="no-records">No comments found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
