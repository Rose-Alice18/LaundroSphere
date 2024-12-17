<?php
// Admin Navbar PHP file
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #6a11cb;
            --secondary-color: #2575fc;
            --background-light: #f4f7fa;
            --text-dark: #333;
            --text-muted: #6c757d;
            --sidebar-bg: linear-gradient(to bottom right, #6a11cb, #2575fc);
            --transition-speed: 0.3s;
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

        .admin-container {
            display: flex;
           
        }

        .sidebar {
            background: var(--sidebar-bg);
            color: white;
            width: 250px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            display: flex;
            flex-direction: column;
            transition: width var(--transition-speed) ease;
            overflow: hidden;
        }

        .sidebar-wrapper {
            flex-grow: 1;
            overflow-y: auto;
            padding: 0 20px 20px;
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.3) transparent;
        }

        .sidebar-wrapper::-webkit-scrollbar {
            width: 8px;
        }

        .sidebar-wrapper::-webkit-scrollbar-thumb {
            background-color: rgba(255,255,255,0.3);
            border-radius: 4px;
        }

        .sidebar.minimized {
            width: 80px;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            position: sticky;
            top: 0;
            background: rgba(0,0,0,0.1);
            z-index: 10;
        }

        .sidebar-header h2 {
            display: flex;
            align-items: center;
            margin: 0;
            white-space: nowrap;
            opacity: 1;
            transition: opacity var(--transition-speed) ease;
        }

        .sidebar.minimized .sidebar-header h2 {
            opacity: 0;
            width: 0;
        
        }

        .sidebar-toggle {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 10px;
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            position: relative;
            z-index: 20;
        }

        .sidebar-toggle:hover {
            background: rgba(255,255,255,0.3);
        }

        .sidebar-nav {
            list-style: none;
            padding: 0;
        }

        .sidebar-nav > li {
            margin-bottom: 10px;
        }

        .sidebar-nav a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 8px;
            transition: all var(--transition-speed) ease;
            position: relative;
        }

        .sidebar-nav a i {
            margin-right: 15px;
            min-width: 25px;
            text-align: center;
        }

        .sidebar-nav a:hover, 
        .sidebar-nav a.active {
            background-color: rgba(255,255,255,0.2);
            color: white;
        }

        .sidebar.minimized .sidebar-nav a span {
            display: none;
        }

        .sidebar.minimized .sidebar-nav a {
            justify-content: center;
        }

        /* Dropdown Styles */
        .dropdown {
            position: relative;
        }

        .dropdown-trigger {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .dropdown-content {
            max-height: 0;
            overflow: hidden;
            background-color: rgba(0,0,0,0.1);
            border-radius: 8px;
            transition: max-height 0.3s ease;
        }

        .dropdown:hover .dropdown-content {
            max-height: 500px; /* Adjust as needed */
        }

        .dropdown-content a {
            padding: 10px 20px;
            color: rgba(255,255,255,0.8);
            display: block;
        }

        .dropdown-content a:hover {
            background-color: rgba(255,255,255,0.2);
        }

        .dropdown-icon {
            transition: transform var(--transition-speed) ease;
        }

        .dropdown:hover .dropdown-icon {
            transform: rotate(180deg);
        }

        

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <h2>LaundroSphere</h2>
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <div class="sidebar-wrapper">
                <ul class="sidebar-nav">
                    <li><a href="admin_index.php" class=""><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a></li>

                    <a href="../index.php"><i class="fas fa-home"></i> Home</a>
                    
                    <li><a href="company_management.php"><i class="fas fa-user"></i> <span>Companies</span></a></li>

                    <a href="customer_management.php"><i class="fas fa-user"></i> <span>Customers</span></a>

                    <li><a href="feedback_management.php"><i class="fas fa-comment"></i> <span>Feedback</span></a></li>

                    <li><a href="comment_management.php"><i class="fas fa-comments"></i> <span>Comments</span></a></li>
                    
                    <li><a href="order_management.php"><i class="fas fa-shopping-cart"></i> <span>Orders</span></a></li>

                    <li><a href="service_management.php"><i class="fas fa-wrench"></i> <span>Services</span></a></li>
                    
                    <!--li><a href="#all_users_management.php"><i class="fas fa-wrench"></i> <span>All Users</span></a></li-->
                    
                    
                    <!--li><a href="admin_reports.php"><i class="fas fa-chart-line"></i> <span>Reports</span></a></li-->

                    <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a></li>
                </ul>
            </div>
        </div>

        
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const content = document.getElementById('content');
            const sidebarToggle = document.getElementById('sidebarToggle');

            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('minimized');
                content.classList.toggle('full-width');
            });

            // Highlight current page
            const currentPath = window.location.pathname.split('/').pop();
            const menuItems = document.querySelectorAll('.sidebar-nav a');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href === currentPath) {
                    item.classList.add('active');
                }
            });
        });
    </script>
</body>
</html>