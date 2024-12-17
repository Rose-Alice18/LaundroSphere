<?php
// Start session and check if the company is logged in
session_start();
if (!isset($_SESSION['company_id'])) {
    header('Location: ../login.php');
    exit();
}

$company_id = intval($_SESSION['company_id']);
require_once '../db_connect.php'; // Database connection

// Fetch all orders for the company
$sql_orders = "
    SELECT 
        b.booking_id, 
        b.pickup_date, 
        u.user_id, 
        u.fname, 
        u.lname 
    FROM Booking b
    INNER JOIN User u ON b.user_id = u.user_id
    WHERE b.company_id = ?
    ORDER BY b.pickup_date DESC
";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $company_id);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

// Handle form submission for adding or updating comments
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_comment']) || isset($_POST['update_comment'])) {
        $booking_id = intval($_POST['booking_id']);
        $pickup_delivery_comments = htmlspecialchars(trim($_POST['pickup_delivery_comments']));
        $clothing_condition = htmlspecialchars(trim($_POST['clothing_condition']));
        $additional_comments = htmlspecialchars(trim($_POST['additional_comments'] ?? ''));

        // Fetch user_id linked to the selected booking
        $stmt_fetch_user = $conn->prepare("SELECT user_id FROM Booking WHERE booking_id = ?");
        $stmt_fetch_user->bind_param("i", $booking_id);
        $stmt_fetch_user->execute();
        $user_result = $stmt_fetch_user->get_result();
        $user = $user_result->fetch_assoc();
        $user_id = $user['user_id'] ?? null;
        $stmt_fetch_user->close();

        if ($user_id) {
            if (isset($_POST['send_comment'])) {
                // Insert new comment
                $sql_insert = "
                    INSERT INTO Comment (booking_id, company_id, user_id, pickup_delivery_comments, clothing_condition, additional_comments, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ";
                $stmt_insert = $conn->prepare($sql_insert);
                $stmt_insert->bind_param("iiisss", $booking_id, $company_id, $user_id, $pickup_delivery_comments, $clothing_condition, $additional_comments);
                $stmt_insert->execute();
                $stmt_insert->close();
            } elseif (isset($_POST['update_comment'])) {
                $comment_id = intval($_POST['comment_id']);
                // Update existing comment
                $sql_update = "
                    UPDATE Comment 
                    SET pickup_delivery_comments = ?, clothing_condition = ?, additional_comments = ?
                    WHERE comment_id = ? AND company_id = ?
                ";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("sssii", $pickup_delivery_comments, $clothing_condition, $additional_comments, $comment_id, $company_id);
                $stmt_update->execute();
                $stmt_update->close();
            }
        }

        header("Location: company_give_comments.php");
        exit();
    }

    if (isset($_POST['delete_comment'])) {
        $comment_id = intval($_POST['comment_id']);
        $delete_sql = "DELETE FROM Comment WHERE comment_id = ? AND company_id = ?";
        $stmt_delete = $conn->prepare($delete_sql);
        $stmt_delete->bind_param("ii", $comment_id, $company_id);
        $stmt_delete->execute();
        $stmt_delete->close();

        header("Location: company_give_comments.php");
        exit();
    }
}

// Fetch all comments made by the company
$sql_comments = "
    SELECT 
        c.comment_id, 
        c.booking_id, 
        c.pickup_delivery_comments, 
        c.clothing_condition, 
        c.additional_comments, 
        c.created_at, 
        u.fname, 
        u.lname, 
        b.pickup_date
    FROM Comment c
    INNER JOIN Booking b ON c.booking_id = b.booking_id
    INNER JOIN User u ON b.user_id = u.user_id
    WHERE c.company_id = ?
    ORDER BY c.created_at DESC
";
$stmt_comments = $conn->prepare($sql_comments);
$stmt_comments->bind_param("i", $company_id);
$stmt_comments->execute();
$comments_result = $stmt_comments->get_result();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Give Comments | LaundroSphere</title>
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
        .content h2 {
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
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
    </style>

    <script>
        function editComment(comment) {
            document.getElementById('booking_id').value = comment.booking_id;
            document.getElementById('pickup_delivery_comments').value = comment.pickup_delivery_comments;
            document.getElementById('clothing_condition').value = comment.clothing_condition;
            document.getElementById('additional_comments').value = comment.additional_comments;
            document.querySelector('button[name="send_comment"]').innerText = 'Update Comment';
            document.querySelector('button[name="send_comment"]').setAttribute('name', 'update_comment');
            document.getElementById('comment_id').value = comment.comment_id;
        }
    </script>

</head>
<body>
    <?php include 'company_navbar.php'; ?>
    <div class="content">
        <h2>Give Comments</h2>
        <form action="company_give_comments.php" method="POST">
            <input type="hidden" name="comment_id" id="comment_id">
            <div class="form-group">
                <label for="booking_id">Order</label>
                <select name="booking_id" id="booking_id" required>
                    <option value="">Select an Order</option>
                    <?php while ($order = $orders_result->fetch_assoc()): ?>
                        <option value="<?php echo $order['booking_id']; ?>">
                            <?php echo htmlspecialchars($order['fname'] . ' ' . $order['lname'] . ' - ' . date('F j, Y', strtotime($order['pickup_date']))); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="pickup_delivery_comments">Pickup & Delivery Comments</label>
                <textarea name="pickup_delivery_comments" id="pickup_delivery_comments" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="clothing_condition">Clothing Condition</label>
                <textarea name="clothing_condition" id="clothing_condition" rows="4" required></textarea>
            </div>
            <div class="form-group">
                <label for="additional_comments">Additional Comments</label>
                <textarea name="additional_comments" id="additional_comments" rows="4"></textarea>
            </div>
            <button type="submit" name="send_comment" class="btn btn-primary">Send Comment</button>
        </form>
        
        <h2>Past Given Comments</h2>
        <table>
            <thead>
                <tr>
                    <th>Customer</th>
                    <th>Pickup Date</th>
                    <th>Pickup & Delivery</th>
                    <th>Clothing Condition</th>
                    <th>Additional Comments</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($comment = $comments_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comment['fname'] . ' ' . $comment['lname']); ?></td>
                        <td><?php echo date('F j, Y', strtotime($comment['pickup_date'])); ?></td>
                        <td><?php echo htmlspecialchars($comment['pickup_delivery_comments']); ?></td>
                        <td><?php echo htmlspecialchars($comment['clothing_condition']); ?></td>
                        <td><?php echo htmlspecialchars($comment['additional_comments']); ?></td>
                        <td><?php echo date('F j, Y, g:i A', strtotime($comment['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-primary" onclick="editComment(<?php echo htmlspecialchars(json_encode($comment)); ?>)">Edit</button>
                            <form action="company_give_comments.php" method="POST" style="display:inline;">
                                <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                <button type="submit" name="delete_comment" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <script>
        function editComment(comment) {
            document.getElementById('comment_id').value = comment.comment_id;
            document.getElementById('booking_id').value = comment.booking_id;
            document.getElementById('pickup_delivery_comments').value = comment.pickup_delivery_comments;
            document.getElementById('clothing_condition').value = comment.clothing_condition;
            document.getElementById('additional_comments').value = comment.additional_comments;
            document.querySelector('button[name="send_comment"]').innerText = 'Update Comment';
            document.querySelector('button[name="send_comment"]').setAttribute('name', 'update_comment');
        }
    </script>
</body>
</html>