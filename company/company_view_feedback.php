<?php 
// Start the session and check if the company is logged in
session_start();
if (!isset($_SESSION['company_id'])) {
    header('Location: ../login.php'); // Redirect if not logged in
    exit();
}

$company_id = intval($_SESSION['company_id']);
include '../db_connect.php'; // Database connection

// Fetch feedback for the logged-in company
$sql = "SELECT f.feedback_id, f.feedback_text, f.created_at, f.is_read, u.fname, u.lname 
        FROM feedback f 
        JOIN user u ON f.customer_id = u.user_id 
        WHERE f.company_id = ? 
        ORDER BY f.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $company_id);
$stmt->execute();
$result = $stmt->get_result();

// Handle feedback deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_feedback'])) {
        $feedback_id = intval($_POST['feedback_id']);

        // Check if feedback is marked as read before deletion
        $check_sql = "SELECT is_read FROM feedback WHERE feedback_id = ? AND company_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ii", $feedback_id, $company_id);
        $check_stmt->execute();
        $feedback = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($feedback && $feedback['is_read'] == 1) {
            $delete_sql = "DELETE FROM feedback WHERE feedback_id = ? AND company_id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("ii", $feedback_id, $company_id);

            if ($stmt->execute()) {
                $success_message = "Feedback deleted successfully.";
            } else {
                $error_message = "Failed to delete feedback. Please try again.";
            }

            $stmt->close();
        } else {
            $error_message = "Only feedback marked as read can be deleted.";
        }

        header("Location: company_view_feedback.php");
        exit();
    }

    // Handle marking feedback as read
    if (isset($_POST['mark_as_read'])) {
        $feedback_id = intval($_POST['feedback_id']);

        $mark_read_sql = "UPDATE feedback SET is_read = 1 WHERE feedback_id = ? AND company_id = ?";
        $stmt = $conn->prepare($mark_read_sql);
        $stmt->bind_param("ii", $feedback_id, $company_id);

        if ($stmt->execute()) {
            $success_message = "Feedback marked as read.";
        } else {
            $error_message = "Failed to mark feedback as read. Please try again.";
        }

        $stmt->close();
        header("Location: company_view_feedback.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback | LaundroSphere</title>
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
        .content {
            margin-left: 250px;
            margin-top: 60px;
            padding: 20px;
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
        .btn-delete {
            background: #dc3545;
        }
        .btn-delete:hover {
            background: #b02a37;
        }
        .btn-read {
            background: #28a745;
        }
        .btn-read:hover {
            background: #218838;
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
        <h2>Feedback</h2>

        <?php if (isset($success_message)) { ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php } elseif (isset($error_message)) { ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php } ?>

        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>Feedback</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                        <td><?php echo htmlspecialchars($row['feedback_text']); ?></td>
                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                        <td>
                            <?php if ($row['is_read'] == 0) { ?>
                                <form method="POST" action="company_view_feedback.php" style="display: inline;">
                                    <input type="hidden" name="feedback_id" value="<?php echo htmlspecialchars($row['feedback_id']); ?>">
                                    <button type="submit" name="mark_as_read" class="btn btn-read">Mark as Read</button>
                                </form>
                            <?php } else { ?>
                                <form method="POST" action="company_view_feedback.php" onsubmit="return confirm('Are you sure you want to delete this feedback?');" style="display: inline;">
                                    <input type="hidden" name="feedback_id" value="<?php echo htmlspecialchars($row['feedback_id']); ?>">
                                    <button type="submit" name="delete_feedback" class="btn btn-delete">Delete</button>
                                </form>
                            <?php } ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</body>
</html>





