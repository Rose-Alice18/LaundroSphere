<?php
session_start();
require_once '../db_connect.php'; // Adjusted path for project structure

// Ensure the user is logged in as a customer
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to login if not authenticated
    exit();
}

$user_id = intval($_SESSION['user_id']);

// Fetch comments related to the user's specific bookings
$sql_comments = "
    SELECT 
        c.comment_id, 
        c.pickup_delivery_comments, 
        c.clothing_condition, 
        c.additional_comments, 
        c.is_read, 
        co.company_name, 
        b.pickup_date, 
        b.delivery_date 
    FROM Comment c
    INNER JOIN Booking b ON c.booking_id = b.booking_id
    INNER JOIN Company co ON b.company_id = co.company_id
    WHERE b.user_id = ?
    ORDER BY b.pickup_date DESC
";
$stmt = $conn->prepare($sql_comments);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$comments = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Mark a comment as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    $comment_id = intval($_POST['comment_id']);
    $mark_read_sql = "UPDATE Comment SET is_read = 1 WHERE comment_id = ?";
    $stmt = $conn->prepare($mark_read_sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $stmt->close();
    header("Location: company_comments.php");
    exit();
}

// Delete a comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']);
    $delete_sql = "DELETE FROM Comment WHERE comment_id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $comment_id);
    $stmt->execute();
    $stmt->close();
    header("Location: company_comments.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Comments | LaundroSphere</title>
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    
    <style>
        /* Original styles maintained */
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #2c3e50;
            --background-color: #f3f4f6;
            --card-bg: #ffffff;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            color: var(--secondary-color);
            line-height: 1.6;
        }

        /* Slide-in Animations */
        @keyframes slideInFromBottom {
            0% {
                opacity: 0;
                transform: translateY(50px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .comments-wrapper {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 15px;
            /* Ensure the content stays clear of the navbar */
            padding-top: 70px; /* Adjust based on navbar height */
            margin-left: 300px; /* Adjust this based on your side panel's width */
            position: relative;
        }
        /* Ensure responsiveness for smaller screens */
        @media (max-width: 768px) {
            .comments-wrapper {
                margin-left: 0; /* Reset margin for mobile screens */
                padding-top: 100px; /* Adjust to ensure content is visible below the navbar */
            }
        }

        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 30px;
        }

        .page-header h2 {
            margin: 0;
            font-weight: 600;
            color: #8c2f6f;
        }

        .comments-icon {
            color: #8c2f6f;
            margin-right: 15px;
            font-size: 2.5rem;
        }

        .comment-card {
            background-color: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;

            /* Apply the slide-in animation */
            animation: slideInFromBottom 0.5s ease-out;
        }


        .comment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.12);
        }

        .comment-header {
            background-color: #8c2f6f;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .comment-header .company-name {
            font-weight: 600;
            font-size: 1.1rem;
        }

        .comment-body {
            padding: 20px;
        }

        .btn-action {
            margin-top: 10px;
            font-size: 0.9rem;
        }

        .btn-mark-read {
            background-color: #28a745;
            color: white;
        }

        .btn-mark-read:hover {
            background-color: #218838;
        }

        .btn-delete {
            background-color: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background-color: #b02a37;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0; /* Remove margin for small screens */
            }
            .comments-wrapper {
                padding: 0 10px;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .comments-icon {
                display: none;
            }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(commentId) {
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
                    document.getElementById(`delete-form-${commentId}`).submit();
                }
            });
        }
    </script>
</head>
<body>
    <?php include 'user_navbar.php'; ?>

    <div class="comments-wrapper">
        <div class="page-header">
            <div class="d-flex align-items-center">
                <i class="fas fa-comments comments-icon"></i>
                <h2>Comments on Your Orders</h2>
            </div>
        </div>

        <?php if (count($comments) > 0): ?>
            <?php foreach ($comments as $comment): ?>
                <div class="comment-card">
                    <div class="comment-header">
                        <span class="company-name">
                            <i class="fas fa-building mr-2"></i>
                            <?= htmlspecialchars($comment['company_name']); ?>
                        </span>
                        <span>
                            <!--?= date('l, F j, Y g:i A', strtotime($comment['booking_date'])); ?-->
                        </span>
                    </div>
                    <div class="comment-body">
                        <p><strong>Pickup & Delivery Comments:</strong> <?= htmlspecialchars($comment['pickup_delivery_comments']); ?></p>
                        <p><strong>Clothing Condition:</strong> <?= htmlspecialchars($comment['clothing_condition']); ?></p>
                        <?php if (!empty($comment['additional_comments'])): ?>
                            <p><strong>Additional Notes:</strong> <?= htmlspecialchars($comment['additional_comments']); ?></p>
                        <?php endif; ?>

                        <div class="d-flex">
                            <?php if ($comment['is_read'] == 0): ?>
                                <form method="POST" style="margin-right: 10px;">
                                    <input type="hidden" name="comment_id" value="<?= $comment['comment_id']; ?>">
                                    <button type="submit" name="mark_as_read" class="btn btn-mark-read btn-action">Mark as Read</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($comment['is_read'] == 1): ?>
                                <form id="delete-form-<?= $comment['comment_id']; ?>" method="POST" style="display: inline;">
                                    <input type="hidden" name="comment_id" value="<?= $comment['comment_id']; ?>">
                                    <button type="button" onclick="confirmDelete(<?= $comment['comment_id']; ?>)" class="btn btn-delete">Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state text-center">
                <h3>No Comments Found</h3>
                <p>You haven't received any comments for your orders yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Optional: Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
