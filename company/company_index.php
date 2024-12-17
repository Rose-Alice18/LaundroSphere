<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['company_id'])) {
    header('Location: ../login.php');
    exit();
}

$company_id = intval($_SESSION['company_id']);

// Fetch company name
$company_query = "SELECT company_name FROM Company WHERE company_id = ?";
$company_stmt = $conn->prepare($company_query);
$company_stmt->bind_param('i', $company_id);
$company_stmt->execute();
$company_result = $company_stmt->get_result();
$company_data = $company_result->fetch_assoc();
$company_name = $company_data['company_name'] ?? 'Company';
$company_stmt->close();

// Fetch statistics
$stats_query = "
    SELECT
        COUNT(CASE WHEN b.status = 'completed' THEN 1 END) AS completed_orders,
        COUNT(CASE WHEN b.status = 'pending' THEN 1 END) AS pending_orders,
        (SELECT COUNT(feedback_id) FROM Feedback WHERE company_id = ?) AS feedback_count,
        (SELECT COUNT(DISTINCT b.user_id) FROM Booking b WHERE b.company_id = ?) AS total_customers
    FROM Booking b
    WHERE b.company_id = ?;
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param('iii', $company_id, $company_id, $company_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$stats = $stats_result->fetch_assoc();
$stats_stmt->close();

$completed_orders = $stats['completed_orders'] ?? 0;
$pending_orders = $stats['pending_orders'] ?? 0;
$feedback_count = $stats['feedback_count'] ?? 0;
$total_customers = $stats['total_customers'] ?? 0;

// Fetch order breakdown (items and services)
$breakdown_query = "
    SELECT 
        i.item_name, 
        st.service_type_name, 
        SUM(bd.quantity) AS total_quantity, 
        SUM(bd.quantity * sfi.price) AS total_earnings
    FROM bookingdetail bd
    INNER JOIN booking b ON bd.booking_id = b.booking_id
    INNER JOIN serviceforitem sfi ON bd.service_for_item_id = sfi.service_for_item_id
    INNER JOIN item i ON sfi.item_id = i.item_id
    INNER JOIN service s ON sfi.service_id = s.service_id
    INNER JOIN servicetype st ON s.service_type_id = st.service_type_id
    WHERE b.company_id = ?
    AND MONTH(b.created_at) = MONTH(CURDATE()) 
    AND YEAR(b.created_at) = YEAR(CURDATE())
    GROUP BY i.item_name, st.service_type_name
";
$breakdown_stmt = $conn->prepare($breakdown_query);
$breakdown_stmt->bind_param('i', $company_id);
$breakdown_stmt->execute();
$breakdown_result = $breakdown_stmt->get_result();

$breakdown_data = [];
$labels = [];
$data = [];
$backgroundColors = [];
$colorPalette = ['#6a1b9a', '#4a90e2', '#f1c40f', '#2ecc71', '#e74c3c', '#8e44ad', '#3498db', '#1abc9c'];

$colorIndex = 0;
while ($row = $breakdown_result->fetch_assoc()) {
    $breakdown_data[] = $row;
    $labels[] = htmlspecialchars($row['item_name'] . ' - ' . $row['service_type_name']);
    $data[] = (float)$row['total_earnings'];
    $backgroundColors[] = $colorPalette[$colorIndex % count($colorPalette)];
    $colorIndex++;
}
$breakdown_stmt->close();

// Fetch recent orders
$recent_orders_query = "
    SELECT 
        b.booking_id, 
        CONCAT(u.fname, ' ', u.lname) AS customer_name, 
        b.status, 
        b.created_at,
        SUM(bd.quantity) AS total_items
    FROM Booking b
    JOIN User u ON b.user_id = u.user_id
    JOIN BookingDetail bd ON b.booking_id = bd.booking_id
    WHERE b.company_id = ?
    GROUP BY b.booking_id
    ORDER BY b.created_at DESC
    LIMIT 5;
";
$recent_orders_stmt = $conn->prepare($recent_orders_query);
$recent_orders_stmt->bind_param('i', $company_id);
$recent_orders_stmt->execute();
$recent_orders_result = $recent_orders_stmt->get_result();
$recent_orders = $recent_orders_result->fetch_all(MYSQLI_ASSOC);
$recent_orders_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaundroSphere - Company Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #6a1b9a;
            --secondary-color: #4a90e2;
            --background-light: #f4f6f9;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: dark blue;
            padding: 20px 50px; /* Reduce top and bottom padding (first value) */
            border-radius: 15px;
            margin: 50px auto 20px; /* Top margin, horizontal centering, bottom margin */
            width: 100%;
            
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }



        .dashboard-header h1 {
            font-size: 3rem;
            font-weight: 700;
        }

        .dashboard-header .welcome-text {
            font-size: 1.5rem;
            opacity: 0.9;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-header h3 {
            color: var(--primary-color);
            font-weight: 600;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .recent-orders {
            margin-top: 20px;
        }

        .order-row {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        .order-row:last-child {
            border-bottom: none;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-completed {
            background-color: rgba(46, 204, 113, 0.1);
            color: #2ecc71;
        }

        .status-pending {
            background-color: rgba(241, 196, 15, 0.1);
            color: #f1c40f;
        }

        .tips-section {
            margin-top: 30px;
            background: #f9f9f9;
            border-left: 5px solid var(--primary-color);
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .tips-section h3 {
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                margin-left: 0;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        .tips-section ul {
            list-style: none;
            padding: 0;
        }

        .tips-section ul li {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .tips-section ul li i {
            color: var(--secondary-color);
            margin-right: 10px;
        }

        .tips-section .btn {
            display: inline-block;
            margin-top: 15px;
            padding: 10px 20px;
            color: white;
            background-color: var(--primary-color);
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-size: 1rem;
            transition: background-color 0.3s ease;
        }

        .tips-section .btn:hover {
            background-color: var(--secondary-color);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                margin-left: 0;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const dashboardContainer = document.querySelector('.dashboard-container');

            sidebarToggle.addEventListener('click', function () {
                const sidebar = document.getElementById('sidebar');
                if (sidebar.classList.contains('minimized')) {
                    dashboardContainer.style.marginLeft = '60px';
                } else {
                    dashboardContainer.style.marginLeft = '280px';
                }
            });
        });
    </script>
</head>


<body>
    <?php include 'company_navbar.php'; ?>

    <div class="dashboard-container" id="content">
        <div class="dashboard-header">
            <div>
                
                <p class="welcome-text">Welcome, <?php echo htmlspecialchars($company_name); ?>!</p>
                <p>Your success is our priority, and we're excited to make your laundry management journey with us, smoother and stress-free! Get started by navigating through the <strong>Tips and Information </strong>section and your dashboard.</p>
            </div>
            <div>
                <i class="fas fa-chart-line fa-3x"></i>
            </div>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <h3>Completed Orders</h3>
                    <i class="fas fa-check-circle" style="color: #2ecc71;"></i>
                </div>
                <div class="stat-value"><?php echo $completed_orders ?? 0; ?></div>
                <div class="stat-label">Total Completed Orders</div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Pending Orders</h3>
                    <i class="fas fa-hourglass-half" style="color: #f1c40f;"></i>
                </div>
                <div class="stat-value"><?php echo $pending_orders ?? 0; ?></div>
                <div class="stat-label">Orders In Progress</div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Customer Feedback</h3>
                    <i class="fas fa-comments" style="color: #3498db;"></i>
                </div>
                <div class="stat-value"><?php echo $feedback_count ?? 0; ?></div>
                <div class="stat-label">Total Feedback Received</div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3>Total Customers</h3>
                    <i class="fas fa-users" style="color: #9b59b6;"></i>
                </div>
                <div class="stat-value"><?php echo $total_customers ?? 0; ?></div>
                <div class="stat-label">Total Order Customers</div>
            </div>
        </div>

        <div class="dashboard-grid" style="margin-top: 20px;">
            <div class="card">
                <div class="card-header">
                    <h3>Order Breakdown</h3>
                </div>
                <div class="chart-container">
                    <canvas id="orderBreakdownChart"></canvas>
                </div>
            </div>

            <div class="card recent-orders">
                <div class="card-header">
                    <h3>Recent Orders</h3>
                    <i class="fas fa-list" style="color: #34495e;"></i>
                </div>
                <?php foreach ($recent_orders as $order): ?>
                    <div class="order-row">
                        <div>
                            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                            <div class="stat-label">
                                <?php echo $order['total_items']; ?> items - <?php echo date('l, F j, Y, g:i A', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div class="status-badge 
                            <?php echo $order['status'] === 'completed' ? 'status-completed' : 'status-pending'; ?>">
                            <?php echo htmlspecialchars($order['status']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="tips-section">
            <h3><i class="fas fa-info-circle"></i> Tips & Information</h3>
            <ul>
                <li><i class="fas fa-check-circle"></i> Keep your company profile updated regularly to attract more customers.</li>
                <li><i class="fas fa-chart-bar"></i> Use the analytics above to understand your business performance and plan accordingly.</li>
                <li><i class="fas fa-cogs"></i> Visit the Service Management section to ensure your services and prices are competitive.</li>
                <li><i class="fas fa-envelope"></i> Engage with customer feedback to build trust and improve your services.</li>
                <li><i class="fas fa-question-circle"></i> For assistance, visit our support center or contact the admin team.</li>
            </ul>
            <a href="info_for_company.php" class="btn">See Additional Information</a>
        </div>

    </div>

    <script>
        // Prepare data for Chart.js
        const labels = <?php echo json_encode($labels); ?>;
        const data = <?php echo json_encode($data); ?>;
        const backgroundColors = <?php echo json_encode($backgroundColors); ?>;

        const ctx = document.getElementById('orderBreakdownChart').getContext('2d');
        const orderBreakdownChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: backgroundColors,
                    borderColor: 'rgba(255, 255, 255, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 20,
                            padding: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.parsed || 0;
                                return label + ': $' + value.toFixed(2);
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>