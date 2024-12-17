<?php
session_start();
require_once '../db_connect.php'; // Ensure database connection is correct

// Basic error handling and security checks
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to login if no user is logged in
    exit();
}

$user_id = intval($_SESSION['user_id']); // Ensure user ID is an integer

// Fetch user's first name and other details
$user_query = "SELECT fname, email, phone_number FROM user WHERE user_id = ?";
$user_stmt = $conn->prepare($user_query);
if ($user_stmt) {
    $user_stmt->bind_param('i', $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    $user_first_name = htmlspecialchars($user_data['fname']); // Sanitize output
    $user_stmt->close();
} else {
    die("Error fetching user details.");
}

// Fetch user statistics from the database
$query = "
    SELECT
        COUNT(CASE WHEN status = 'Completed' THEN 1 END) AS completed_orders,
        COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending_orders,
        COUNT(CASE WHEN status = 'InProgress' THEN 1 END) AS inprogress_orders,
        (SELECT COUNT(feedback_id) FROM feedback WHERE customer_id = ?) AS feedback_count,
        (SELECT COUNT(company_id) FROM company WHERE status = 'Active') AS available_companies,
        COALESCE(SUM(b.total_price), 0) AS total_spent
    FROM booking b
    WHERE b.user_id = ?;
";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();
    $stmt->close();
} else {
    die("Error fetching user statistics.");
}

// Fetch recent orders
$recent_orders_query = "
    SELECT booking_id, pickup_date AS booking_date, status, total_price
    FROM booking
    WHERE user_id = ? 
    ORDER BY pickup_date DESC 
    LIMIT 5
";
$recent_orders_stmt = $conn->prepare($recent_orders_query);
if ($recent_orders_stmt) {
    $recent_orders_stmt->bind_param('i', $user_id);
    $recent_orders_stmt->execute();
    $recent_orders_result = $recent_orders_stmt->get_result();
    $recent_orders = $recent_orders_result->fetch_all(MYSQLI_ASSOC);
    $recent_orders_stmt->close();
} else {
    die("Error fetching recent orders.");
}

// Fetch laundry habits for the pie chart dynamically including all items
$pie_chart_query = "
    SELECT i.item_name, SUM(bd.quantity) AS quantity
    FROM bookingdetail bd
    INNER JOIN serviceforitem sfi ON bd.service_for_item_id = sfi.service_for_item_id
    INNER JOIN item i ON sfi.item_id = i.item_id
    WHERE bd.booking_id IN (SELECT booking_id FROM booking WHERE user_id = ?)
    GROUP BY i.item_id, i.item_name
";
$pie_chart_stmt = $conn->prepare($pie_chart_query);
if ($pie_chart_stmt) {
    $pie_chart_stmt->bind_param('i', $user_id);
    $pie_chart_stmt->execute();
    $pie_chart_result = $pie_chart_stmt->get_result();
    $pie_chart_data = $pie_chart_result->fetch_all(MYSQLI_ASSOC);
    $pie_chart_stmt->close();
} else {
    die("Error fetching pie chart data.");
}

// Prepare data for Chart.js
$item_names = [];
$item_quantities = [];

foreach ($pie_chart_data as $item) {
    $item_names[] = htmlspecialchars($item['item_name']);
    $item_quantities[] = intval($item['quantity']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaundroSphere - User Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Owl Carousel CSS and JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        
        :root {
            --primary-color: #4a90e2;
            --secondary-color: #8c2f6f;
            --wine-color: #722f37;
            --table-header-bg: #8c2f6f;
            --table-header-text: #ffffff;
            --table-row-alt-bg: #f9f9f9;
            --table-border: #ddd;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        .content-wrapper {
            transition: margin-left 0.3s ease;
            margin-left: 280px;
            opacity: 1;
        }

        .content-wrapper.full-width {
            margin-left: 60px;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-out;
        }

        .responsive-grid {
            transition: all 0.3s ease;
        }

        .content-wrapper.full-width .responsive-grid {
            grid-template-columns: repeat(3, 1fr);
        }

        .transformation-section {
            background: linear-gradient(135deg, #722f37, #8c2f6f);
            transition: all 0.3s ease;
        }

        @media (max-width: 768px) {
            .content-wrapper {
                margin-left: 0 !important;
            }
            .responsive-grid {
                grid-template-columns: 1fr !important;
            }
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #722f37;
            border-radius: 2px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-color);
        }

        /* Custom Styles for Recent Orders */
        .recent-orders {
            max-height: 300px;
            overflow-y: auto;
        }

        .recent-orders .order {
            border-bottom: 1px solid var(--table-border);
            padding: 12px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: background-color 0.3s;
        }

        .recent-orders .order:hover {
            background-color: #f9f9f9;
        }

        .order-status {
            padding: 6px 12px;
            border-radius: 9999px;
            text-transform: capitalize;
            font-weight: 600;
            font-size: 0.875rem;
        }

        /* Status Colors */
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-inprogress {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4a90e2',
                        secondary: '#8c2f6f'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <?php include 'user_navbar.php'; ?>

    <!-- Main Content with ID for responsive tracking -->
    <div class="content-wrapper container mx-auto px-4 py-8 max-w-screen-xl">
        <div class="container mx-auto px-4 py-8 max-w-screen-xl">
            <!-- Welcome Section -->
            <div class="bg-white shadow-md rounded-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">
                            Welcome, <?php echo $user_first_name; ?>! 
                        </h2>
                        
                        <p class="text-gray-600">Manage your laundry requests, track order statuses, view feedback, and much more from one place.</p>

                    </div>
                    <div id="datetime" class="text-right text-sm text-gray-500 mt-2 md:mt-0"></div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white p-4 rounded-lg shadow-md transform transition hover:scale-105">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3 text-2xl"></i>
                        <div>
                            <h3 class="text-lg font-semibold"><?php echo $stats['completed_orders']; ?></h3>
                            <p class="text-gray-500">Completed Orders</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-hourglass-half text-yellow-500 mr-3 text-2xl"></i>
                        <div>
                            <h3 class="text-lg font-semibold"><?php echo $stats['pending_orders']; ?></h3>
                            <p class="text-gray-500">Pending Orders</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-coins text-purple-500 mr-3 text-2xl"></i>
                        <div>
                            <h3 class="text-lg font-semibold">₵<?php echo number_format($stats['total_spent'], 2); ?></h3>
                            <p class="text-gray-500">Total Spent</p>
                        </div>
                    </div>
                </div>
                <div class="bg-white p-4 rounded-lg shadow-md">
                    <div class="flex items-center">
                        <i class="fas fa-building text-blue-500 mr-3 text-2xl"></i>
                        <div>
                            <h3 class="text-lg font-semibold"><?php echo $stats['available_companies']; ?></h3>
                            <p class="text-gray-500">Active Companies</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Dashboard Insights -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Laundry Habits Chart -->
                <div class="bg-white p-3 rounded-lg shadow-md" style="height: 300px;">
                    <h3 class="text-xl font-semibold mb-4">Your Laundry Habits</h3>
                    <canvas id="laundryHabitsChart"></canvas>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white p-3 rounded-lg shadow-md recent-orders" style="height: 300px;">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold">Recent Orders</h3>
                        <a href="request_status.php" class="text-blue-500 hover:underline">View All</a>
                    </div>
                    <div id="recentOrdersList">
                        <?php if (!empty($recent_orders)): ?>
                            <?php foreach($recent_orders as $order): ?>
                                <?php
                                    // Map status to corresponding classes
                                    $status = strtolower($order['status']);
                                    $status_class = '';
                                    switch ($status) {
                                        case 'completed':
                                            $status_class = 'status-completed';
                                            break;
                                        case 'pending':
                                            $status_class = 'status-pending';
                                            break;
                                        case 'inprogress':
                                            $status_class = 'status-inprogress';
                                            break;
                                        default:
                                            $status_class = 'bg-gray-200 text-gray-800';
                                            break;
                                    }
                                ?>
                                <div class="order">
                                    <div>
                                        <p class="font-medium">Order #<?php echo htmlspecialchars($order['booking_id']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars(date("F j, Y", strtotime($order['booking_date']))); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <span class="order-status <?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                        <p class="font-semibold">₵<?php echo number_format($order['total_price'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-feedback">You have no recent orders.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Automated Carousel Section with Gradient Overlay -->
            <div class="bg-gradient-to-r from-primary to-secondary rounded-lg shadow-lg mt-6 overflow-hidden">
                <div class="p-6 text-white">
                    <h3 class="text-xl font-semibold mb-4 flex items-center">
                        <i class="fas fa-tshirt mr-3"></i> 
                        Transforming Your Laundry Experience
                    </h3>
                    <!-- Convert to Owl Carousel -->
                    <div class="before-after-carousel owl-carousel owl-theme">
                        <div class="item relative group">
                            <img src="../images/What's the Best Way to Get Grease Stains Out of Clothes_ _ America's Test Kitchen.jpeg" 
                                class="w-full h-48 object-cover rounded-lg transform transition group-hover:scale-105" 
                                alt="Before Cleaning">
                            <div class="absolute inset-0 bg-black opacity-20 group-hover:opacity-40 transition"></div>
                            <span class="absolute bottom-2 left-2 bg-white text-primary px-2 py-1 rounded text-sm">Before</span>
                        </div>
                        <div class="item relative group">
                            <img src="../images/Screenshot 2024-12-11 141645.png" 
                                class="w-full h-48 object-cover rounded-lg transform transition group-hover:scale-105" 
                                alt="After Cleaning">
                            <div class="absolute inset-0 bg-black opacity-20 group-hover:opacity-40 transition"></div>
                            <span class="absolute bottom-2 left-2 bg-white text-primary px-2 py-1 rounded text-sm">After</span>
                        </div>
                        <div class="item relative group">
                            <img src="../images/How to care for your bed linen, with eco-friendly dry cleaners BLANC.jpeg" 
                                class="w-full h-48 object-cover rounded-lg transform transition group-hover:scale-105" 
                                alt="Laundry Transformation">
                            <div class="absolute inset-0 bg-black opacity-20 group-hover:opacity-40 transition"></div>
                            <span class="absolute bottom-2 left-2 bg-white text-primary px-2 py-1 rounded text-sm">Transformation</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enhanced Navigation Section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition transform hover:-translate-y-2">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-calendar-check text-secondary text-3xl mr-4"></i>
                        <h3 class="text-xl font-semibold text-gray-800">Know Your way Around</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Track and manage all your laundry orders in one place.</p>
                    <a href="#view_orders.php" class="block text-center bg-secondary text-white py-2 rounded hover:bg-green-600 transition">
                        Manage Orders
                    </a>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition transform hover:-translate-y-2">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-comment-alt text-secondary text-3xl mr-4"></i>
                        <h3 class="text-xl font-semibold text-gray-800">Provide a Testimonial</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Share your experience and help us improve our services.</p>
                    <a href="#give_comments.php" class="block text-center bg-secondary text-white py-2 rounded hover:bg-green-600 transition">
                        Give Comments
                    </a>
                </div>
                
                <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-xl transition transform hover:-translate-y-2">
                    <div class="flex items-center mb-4">
                        <i class="fas fa-cogs text-secondary text-3xl mr-4"></i>
                        <h3 class="text-xl font-semibold text-gray-800">Manage Services</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Customize and explore our wide range of laundry services.</p>
                    <a href="#manage_services.php" class="block text-center bg-secondary text-white py-2 rounded hover:bg-green-600 transition">
                        Explore Services
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery (required for Owl Carousel) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Owl Carousel JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    
    <script>
        // Add sidebar toggle responsiveness
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const contentWrapper = document.querySelector('.content-wrapper');
            const responsiveGrids = document.querySelectorAll('.responsive-grid');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    contentWrapper.classList.toggle('full-width');
                });
            }
        });

        // Real-time date and time
        function updateDateTime() {
            const now = new Date();
            document.getElementById('datetime').textContent = now.toLocaleString('en-US', {
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric', 
                hour: '2-digit', 
                minute: '2-digit'
            });
        }
        updateDateTime();
        setInterval(updateDateTime, 60000);

        // Laundry Habits Chart
        const ctx = document.getElementById('laundryHabitsChart').getContext('2d');
        const laundryHabitsChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($item_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($item_quantities); ?>,
                    backgroundColor: [
                        '#4a90e2',
                        '#8c2f6f',
                        '#f39c12',
                        '#2ecc71',
                        '#e74c3c',
                        '#9b59b6',
                        '#1abc9c',
                        '#34495e',
                        '#e67e22',
                        '#7f8c8d'
                    ],
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((acc, val) => acc + val, 0);
                                const value = context.parsed;
                                const percentage = total > 0 ? ((value / total) * 100).toFixed(2) : 0;
                                return `${context.label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });

        // Owl Carousel Initialization
        $(document).ready(function () {
            $('.before-after-carousel').owlCarousel({
                loop: true,
                margin: 10,
                nav: true,
                dots: false,
                autoplay: true,
                autoplayTimeout: 3000,
                responsive: {
                    0: { items: 1 },
                    768: { items: 2 },
                    992: { items: 3 }
                },
                navText: [
                    '<i class="fas fa-chevron-left text-wine"></i>',
                    '<i class="fas fa-chevron-right text-wine"></i>'
                ]
            });
        });
    </script>
</body>
</html>
