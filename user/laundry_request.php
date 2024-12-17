<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../db_connect.php';

// Ensure the user is logged in as a customer
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = intval($_SESSION['user_id']); // Ensure user ID is an integer

// Fetch active laundry companies
$company_sql = "SELECT company_id, company_name, company_address, company_email, company_phone_number FROM Company WHERE status = 'Active'";
$company_result = $conn->query($company_sql);

// Fetch user's first name for personalization
$user_query = "SELECT fname FROM User WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
if ($user_stmt) {
    $user_stmt->bind_param('i', $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_first_name = htmlspecialchars($user_data['fname'] ?? 'Customer');
} else {
    die("Error fetching user data.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laundry Requests | LaundroSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Lato', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
            line-height: 1.6;
            color: #333;
        }

        /* Fade-in animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .dashboard-container {
            padding: 20px;
            margin-left: 250px; /* Adjust based on your side panel's width */
            animation: fadeIn 1s ease-out;
        }

        .companies {
            padding: 80px 20px;
            background-color: #fff;
            text-align: center;
        }

        .companies-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .company-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            animation: fadeIn 0.7s ease-out;
        }

        .company-card:hover {
            transform: translateY(-10px);
        }

        .btn {
            padding: 10px 20px;
            background-color: #8c2f6f;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background-color: #6e234e;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            border-radius: 10px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover, .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .modal-header {
            font-size: 24px;
            margin-bottom: 15px;
        }

        .modal-body {
            font-size: 18px;
            line-height: 1.6;
        }

        .empty-state {
            text-align: center;
            padding: 50px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
            animation: fadeIn 1s ease-out;
        }

        .empty-state i {
            font-size: 5em;
            color: #8c2f6f;
            margin-bottom: 20px;
        }

        .empty-state h2 {
            margin-bottom: 15px;
            color: #333;
        }

        .empty-state p {
            color: #666;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>
    <?php include "user_navbar.php"; ?>

    <div class="dashboard-container">
        <div class="companies">
            <h2>Choose from our trusted laundry partners, <?php echo $user_first_name; ?></h2>
            <?php if ($company_result->num_rows > 0): ?>
                <div class="companies-container">
                    <?php while ($company = $company_result->fetch_assoc()): ?>
                        <div class="company-card">
                            <h3><?php echo htmlspecialchars($company['company_name']); ?></h3>
                            <p><?php echo htmlspecialchars($company['company_address']); ?></p>
                            <p>Email: <?php echo htmlspecialchars($company['company_email']); ?></p>
                            <p>Phone: <?php echo htmlspecialchars($company['company_phone_number']); ?></p>
                            <button class="btn more-info" data-company-id="<?php echo $company['company_id']; ?>">More Info</button>
                            <a href="request_form.php?company_id=<?php echo $company['company_id']; ?>" class="btn">Make a Request</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-soap"></i>
                    <h2>No Laundry Services Available</h2>
                    <p>We're sorry, but there are currently no active laundry services in your area. Please check back later.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div id="companyInfoModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div class="modal-header" id="modal-header">Company Details</div>
            <div class="modal-body" id="modal-body"></div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('companyInfoModal');
        const closeModal = document.querySelector('.close');
        const modalBody = document.getElementById('modal-body');

        document.querySelectorAll('.btn.more-info').forEach(button => {
            button.addEventListener('click', () => {
                const companyId = button.getAttribute('data-company-id');
                fetch(`get_company_info.php?company_id=${companyId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.services.length === 0) {
                            modalBody.innerHTML = `
                                <p><strong>Oops!</strong> This company has no services listed. 
                                Please try booking another company.</p>`;
                        } else {
                            let serviceList = '<ul>';
                            data.services.forEach(service => {
                                serviceList += `<li>
                                    <strong>${service.service_type_name}</strong>: 
                                    ${service.item_name} - ₵${parseFloat(service.price).toFixed(2)}
                                </li>`;
                            });
                            serviceList += '</ul>';

                            modalBody.innerHTML = `
                                <p><strong>Company Name:</strong> ${data.company_name}</p>
                                <p><strong>Address:</strong> ${data.company_address}</p>
                                <p><strong>Quote:</strong> ${data.short_quote || 'N/A'}</p>
                                <p><strong>Services and Prices:</strong></p>
                                ${serviceList}`;
                        }
                        modal.style.display = 'block';
                    })
                    .catch(() => {
                        modalBody.innerHTML = `
                            <p class="error-message">
                                <strong>Oops!</strong> Something went wrong. 
                                Please try again or choose another company.</p>`;
                        modal.style.display = 'block';
                    });
            });
        });

        closeModal.addEventListener('click', () => modal.style.display = 'none');
        window.addEventListener('click', event => {
            if (event.target === modal) modal.style.display = 'none';
        });
    });

    </script>
</body>
</html>