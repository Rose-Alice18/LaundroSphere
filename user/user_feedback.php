<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to login if not logged in
    exit();
}

$user_id = intval($_SESSION['user_id']); // Ensure user ID is an integer

// Fetch companies linked to user's bookings
$sql = "
    SELECT DISTINCT c.company_id, c.company_name
    FROM booking b
    INNER JOIN bookingdetail bd ON b.booking_id = bd.booking_id
    INNER JOIN serviceforitem sfi ON bd.service_for_item_id = sfi.service_for_item_id
    INNER JOIN company c ON sfi.company_id = c.company_id
    WHERE b.user_id = ?
";
$stmt = $conn->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $companies = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    die("Error fetching company data.");
}

// Handle feedback submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $feedback = htmlspecialchars(trim($_POST["feedback_text"] ?? ''));
    $company_id = intval($_POST["company_name"] ?? 0);

    if (!empty($feedback) && $company_id > 0) {
        $sql_insert = "INSERT INTO feedback (customer_id, company_id, feedback_text, created_at) VALUES (?, ?, ?, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        if ($stmt_insert) {
            $stmt_insert->bind_param("iis", $user_id, $company_id, $feedback);
            if ($stmt_insert->execute()) {
                $feedback_message = "Thank you for your feedback!";
                $feedback_type = "success";
            } else {
                $feedback_message = "There was an error submitting your feedback. Please try again.";
                $feedback_type = "error";
            }
            $stmt_insert->close();
        } else {
            $feedback_message = "Failed to prepare feedback submission.";
            $feedback_type = "error";
        }
    } else {
        $feedback_message = "Please fill in all required fields.";
        $feedback_type = "error";
    }
}

// Fetch user's feedback history
$sql_history = "
    SELECT f.feedback_text, f.created_at, c.company_name
    FROM feedback f
    INNER JOIN company c ON f.company_id = c.company_id
    WHERE f.customer_id = ?
    ORDER BY f.created_at DESC
";
$stmt_history = $conn->prepare($sql_history);
if ($stmt_history) {
    $stmt_history->bind_param("i", $user_id);
    $stmt_history->execute();
    $result_history = $stmt_history->get_result();
    $feedbacks = $result_history->fetch_all(MYSQLI_ASSOC);
    $stmt_history->close();
} else {
    $feedback_error = "Error fetching feedback history.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback | LaundroSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #357ab7;
            --background-color: #f3f4f6;
            --white: #ffffff;
            --text-color: #333;
            --error-color: #f44336;
            --success-color: #4CAF50;
            --table-header-bg: #8c2f6f;
            --table-header-text: #ffffff;
            --table-row-bg: #ffffff;
            --table-row-alt-bg: #f9f9f9;
            --table-border: #ddd;
        }

        

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-color);
            line-height: 1.6;
            color: var(--text-color);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
            100% {
                transform: scale(1);
            }
        }

        .dashboard-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            background-color: var(--background-color);
        }

        .dashboard-main {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 600px;
            animation: slideIn 0.6s ease-out;
            position: relative;
            overflow: hidden;
        }

        .section-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .section-header h1 {
            color: #8c2f6f;
            font-weight: 600;
        }

        .feedback-guidance {
            background-color: #f9f9f9;
            border-left: 4px solid #8c2f6f;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }

        label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: var(--text-color);
        }

        select, textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        select:focus, textarea:focus {
            outline: none;
            border-color: #8c2f6f;
            box-shadow: 0 0 0 2px rgba(74, 144, 226, 0.2);
        }

        .btn {
            display: block;
            width: 100%;
            padding: 15px;
            background-color: #8c2f6f;
            color: var(--white);
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: var(--secondary-color);
            animation: pulse 0.5s;
        }

        .feedback-message {
            text-align: center;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: 600;
        }

        .feedback-message.success {
            color: var(--success-color);
            background-color: rgba(76, 175, 80, 0.1);
        }

        .feedback-message.error {
            color: var(--error-color);
            background-color: rgba(244, 67, 54, 0.1);
        }

        .feedback-history {
            margin-top: 40px;
        }

        .feedback-history h2 {
            color: #8c2f6f;
            margin-bottom: 20px;
            text-align: center;
        }

        .feedback-history table {
            width: 100%;
            border-collapse: collapse;
        }

        .feedback-history th, .feedback-history td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--table-border);
        }

        .feedback-history th {
            background-color: var(--table-header-bg);
            color: var(--table-header-text);
        }

        .feedback-history tr:nth-child(even) {
            background-color: var(--table-row-alt-bg);
        }

        .feedback-history tr:hover {
            background-color: #f1f1f1;
        }

        .no-feedback {
            text-align: center;
            color: #666;
            font-style: italic;
            margin-top: 20px;
        }

        @media (max-width: 768px) {
            .dashboard-main {
                padding: 20px;
                margin: 20px 10px;
                max-width: 100%; /* Ensure it takes full width on smaller screens */
            }

            .feedback-history table, .feedback-history th, .feedback-history td {
                display: block;
            }

            .feedback-history th {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            .feedback-history tr {
                margin-bottom: 15px;
                border: 1px solid var(--table-border);
                border-radius: 8px;
                padding: 10px;
                background-color: var(--white);
            }

            .feedback-history td {
                border: none;
                position: relative;
                padding-left: 50%;
            }

            .feedback-history td:before {
                content: attr(data-label);
                position: absolute;
                left: 15px;
                font-weight: 600;
                color: var(--text-color);
            }
        }

        textarea::placeholder {
            color: #999;
        }
    </style>
</head>
<body>
    <?php include 'user_navbar.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-main">
            <div class="section-header">
                <h1>Customer Feedback</h1>
            </div>

            <?php if (isset($feedback_message)): ?>
                <div class="feedback-message <?= $feedback_type; ?>">
                    <?= htmlspecialchars($feedback_message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="feedbackForm">
                <label for="company_name">Select Company</label>
                <select name="company_name" id="company_name" required>
                    <option value="" disabled selected>
                        <?= empty($companies) ? 'No companies available' : 'Choose a company'; ?>
                    </option>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?= htmlspecialchars($company['company_id']); ?>" <?= (isset($_POST['company_name']) && $_POST['company_name'] == $company['company_id']) ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($company['company_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="feedback_text">Your Feedback</label>
                <textarea name="feedback_text" id="feedback_text" rows="6" placeholder="Write your feedback..." required><?= isset($_POST['feedback_text']) ? htmlspecialchars($_POST['feedback_text']) : ''; ?></textarea>

                <button type="submit" class="btn">
                    <i class="fas fa-paper-plane"></i> Submit Feedback
                </button>
            </form>

            <div class="feedback-history">
                <h2>Your Feedback History</h2>
                <?php if (isset($feedback_error)): ?>
                    <div class="feedback-message error">
                        <?= htmlspecialchars($feedback_error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($feedbacks)): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Company Name</th>
                                <th>Feedback</th>
                                <th>Date Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($feedbacks as $fb): ?>
                                <tr>
                                    <td data-label="Company Name"><?= htmlspecialchars($fb['company_name']); ?></td>
                                    <td data-label="Feedback"><?= nl2br(htmlspecialchars($fb['feedback_text'])); ?></td>
                                    <td data-label="Date Submitted"><?= htmlspecialchars(date("F j, Y, g:i a", strtotime($fb['created_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-feedback">You have not submitted any feedback yet.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('feedbackForm');
            const companySelect = document.getElementById('company_name');
            const feedbackTextarea = document.getElementById('feedback_text');

            // Dynamically adjust textarea height
            feedbackTextarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = this.scrollHeight + 'px';
            });

            // Adjust textarea height on page load if there's pre-filled content
            if (feedbackTextarea.value) {
                feedbackTextarea.style.height = 'auto';
                feedbackTextarea.style.height = feedbackTextarea.scrollHeight + 'px';
            }
        });
    </script>
</body>
</html>
