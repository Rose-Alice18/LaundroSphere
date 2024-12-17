<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../db_connect.php';

// Verify if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../login.php');
    exit();
}

$admin_id = intval($_SESSION['admin_id']);

// 1. Fetch Metrics
$metrics = [
    'total_revenue' => 0,
    'active_orders' => 0,
    'new_customers' => 0,
    'service_utilization' => 0,
];

// Total revenue (Completed bookings across all companies)
$sql = "SELECT SUM(total_price) AS total_revenue FROM booking WHERE status = 'Completed'";
$result = $conn->query($sql);
if ($result) {
    $metrics['total_revenue'] = $result->fetch_assoc()['total_revenue'] ?? 0;
} else {
    // Handle query error
    $metrics['total_revenue'] = 0;
}

// Active Orders (Pending + InProgress across all companies)
$sql = "SELECT COUNT(*) AS active_orders FROM booking WHERE status IN ('Pending', 'InProgress')";
$result = $conn->query($sql);
if ($result) {
    $metrics['active_orders'] = $result->fetch_assoc()['active_orders'] ?? 0;
} else {
    // Handle query error
    $metrics['active_orders'] = 0;
}

// New Customers (Current month)
$sql = "SELECT COUNT(*) AS new_customers FROM user WHERE role = 'customer' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
$result = $conn->query($sql);
if ($result) {
    $metrics['new_customers'] = $result->fetch_assoc()['new_customers'] ?? 0;
} else {
    // Handle query error
    $metrics['new_customers'] = 0;
}

// Service Utilization (Count of unique service types across all companies)
$sql = "SELECT COUNT(DISTINCT servicetype_id) AS service_utilization FROM serviceforitem";
$result = $conn->query($sql);
if ($result) {
    $metrics['service_utilization'] = $result->fetch_assoc()['service_utilization'] ?? 0;
} else {
    // Handle query error
    $metrics['service_utilization'] = 0;
}

// 2. Fetch Chart Data (Order Status & Service Types)

// Order Status Data
$order_status_data = [];
$sql = "SELECT status, COUNT(*) AS count FROM booking GROUP BY status";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $order_status_data[$row['status']] = $row['count'];
    }
}

// Service Types Usage Data
$service_types_data = [];
$sql = "
    SELECT st.service_type_name, COUNT(si.service_for_item_id) AS count
    FROM servicetype st
    JOIN serviceforitem si ON st.service_type_id = si.servicetype_id
    GROUP BY st.service_type_name
";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $service_types_data[$row['service_type_name']] = $row['count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>LaundroSphere | Admin Dashboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --background-light: #f4f7fa;
            --text-dark: #333;
            --text-muted: #6c757d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
            color: var(--text-dark);
            line-height: 1.6;
        }

        .main-content {
            margin-left: 250px; /* Adjust this to match your admin_navbar.php width */
            padding: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                margin: 20px;
                padding: 10px;
            }
        }

        h1, h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 25px rgba(0,0,0,0.15);
        }

        .card-icon {
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 3rem;
            opacity: 0.1;
            color: var(--primary-color);
        }

        .card-metric {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .card-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .charts-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }

        .chart-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        /* Responsive Design */
        @media (max-width: 1200px) {
            .dashboard-cards, .charts-container {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .dashboard-cards, .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Include the admin navbar -->
    <?php include 'admin_navbar.php'; ?>

    <div class="main-content">
        <h1>Welcome to your Dashboard, Admin!</h1>

        <div class="dashboard-cards">
            <div class="card">
                <i class="fas fa-dollar-sign card-icon"></i>
                <p class="card-label">Total Revenue</p>
                <h2 class="card-metric">GHS <?= number_format($metrics['total_revenue'], 2); ?></h2>
            </div>
            <div class="card">
                <i class="fas fa-shopping-cart card-icon"></i>
                <p class="card-label">Active Orders</p>
                <h2 class="card-metric"><?= htmlspecialchars($metrics['active_orders']); ?></h2>
            </div>
            <div class="card">
                <i class="fas fa-users card-icon"></i>
                <p class="card-label">New Customers</p>
                <h2 class="card-metric"><?= htmlspecialchars($metrics['new_customers']); ?></h2>
            </div>
            <div class="card">
                <i class="fas fa-wrench card-icon"></i>
                <p class="card-label">Service Types</p>
                <h2 class="card-metric"><?= htmlspecialchars($metrics['service_utilization']); ?></h2>
            </div>
        </div>

        <div class="charts-container">
            <div class="chart-card">
                <h3>Order Status</h3>
                <canvas id="orderStatusChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Service Types</h3>
                <canvas id="serviceTypesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Order Status Chart Data
        const orderStatusData = {
            labels: <?= json_encode(array_keys($order_status_data)); ?>,
            datasets: [{
                label: 'Order Status',
                data: <?= json_encode(array_values($order_status_data)); ?>,
                backgroundColor: [
                    '#8c2f6f', // e.g., Completed
                    '#f39c12', // e.g., Pending
                    '#3498db'  // e.g., InProgress
                ],
                hoverOffset: 4
            }]
        };

        // Service Types Chart Data
        const serviceTypesData = {
            labels: <?= json_encode(array_keys($service_types_data)); ?>,
            datasets: [{
                label: 'Service Types',
                data: <?= json_encode(array_values($service_types_data)); ?>,
                backgroundColor: [
                    '#6a11cb',
                    '#2575fc',
                    '#ffbe0b',
                    '#28a745',
                    '#e74c3c',
                    '#95a5a6'
                ],
                borderWidth: 1
            }]
        };

        // Initialize Order Status Pie Chart
        new Chart(document.getElementById('orderStatusChart'), {
            type: 'pie',
            data: orderStatusData,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    title: {
                        display: false,
                        text: 'Order Status Distribution'
                    }
                }
            },
        });

        // Initialize Service Types Bar Chart
        new Chart(document.getElementById('serviceTypesChart'), {
            type: 'bar',
            data: serviceTypesData,
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision:0
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                    },
                    title: {
                        display: false,
                        text: 'Service Types Utilization'
                    }
                }
            },
        });
    </script>
</body>

</html>
