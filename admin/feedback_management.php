<?php
session_start();

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: ../login.php");
    exit();
}

$admin_id = $_SESSION['admin_id'];

include '../db_connect.php'; // Database connection

$success_message = '';
$error_message = '';

// Handle delete action
if (isset($_GET['delete'])) {
    $feedback_id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);

    if ($feedback_id) {
        // Check if feedback can be deleted
        $checkQuery = "
            SELECT feedback_id, is_read, company_id 
            FROM feedback 
            WHERE feedback_id = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("i", $feedback_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $feedback = $result->fetch_assoc();
        $stmt->close();

        if ($feedback && ($feedback['is_read'] == 1 || is_null($feedback['company_id']))) {
            $deleteQuery = "DELETE FROM feedback WHERE feedback_id = ?";
            $stmt = $conn->prepare($deleteQuery);
            $stmt->bind_param("i", $feedback_id);
            if ($stmt->execute()) {
                $success_message = "Feedback deleted successfully.";
            } else {
                $error_message = "Failed to delete feedback. Please try again.";
            }
            $stmt->close();
        } else {
            $error_message = "Feedback cannot be deleted unless it is marked as read or the associated company is deleted.";
        }
    }
}

// Fetch all feedback with user and company details
$query = "
    SELECT f.feedback_id, f.feedback_text, f.is_read, f.created_at,
           u.fname, u.lname,
           c.company_name
    FROM feedback f
    JOIN user u ON f.customer_id = u.user_id
    LEFT JOIN company c ON f.company_id = c.company_id
    ORDER BY f.created_at DESC";
$result = $conn->query($query);

if (!$result) {
    die("Error fetching feedback: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feedback Management | LaundroSphere</title>
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
    <script>
        function confirmDelete(feedbackId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This action cannot be undone!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#e74c3c',
                cancelButtonColor: '#8c2f6f',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `feedback_management.php?delete=${feedbackId}`;
                }
            });
        }
    </script>
</head>
<body>
    <?php include 'admin_navbar.php'; ?>
    <div class="main-content">
        <h2>Feedback Management</h2>

        <?php if ($success_message): ?>
            <div class="message success-message"><?= htmlspecialchars($success_message); ?></div>
        <?php elseif ($error_message): ?>
            <div class="message error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Company</th>
                    <th>Feedback</th>
                    <th>Read</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['feedback_id']); ?></td>
                            <td><?= htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                            <td><?= $row['company_name'] ? htmlspecialchars($row['company_name']) : '<em>Company Deleted</em>'; ?></td>
                            <td><?= htmlspecialchars($row['feedback_text']); ?></td>
                            <td><?= $row['is_read'] ? 'Yes' : 'No'; ?></td>
                            <td><?= date('Y-m-d H:i', strtotime($row['created_at'])); ?></td>
                            <td class="actions">
                                <?php if ($row['is_read'] || is_null($row['company_name'])): ?>
                                    <a href="#" onclick="confirmDelete(<?= $row['feedback_id']; ?>)">Delete</a>
                                <?php else: ?>
                                    <span style="color: #888; font-style: italic;">Cannot delete</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="no-records">No feedback found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>
</html>