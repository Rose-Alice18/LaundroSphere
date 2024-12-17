<?php
session_start();

require_once 'db_connect.php'; // Ensure database connection is correct

// Fetch companies from the database
$companyQuery = "SELECT company_name, company_phone_number, short_code FROM company WHERE status = 'Active'";
$companyResult = $conn->query($companyQuery);

if (!$companyResult) {
    die("Error fetching companies: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaundroSphere - Your Laundry Solution</title>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
    
    <style>
        /* General Styles */
        body {
            margin: 0;
            font-family: 'Lato', sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.9);
            color: white;
            padding: 15px 30px;
            position: fixed;
            width: 100%;
            box-sizing: border-box;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 20px;
        }

        .nav-links li a {
            text-decoration: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-links li a:hover {
            background-color: #722F37;
            transform: translateY(-2px);
        }

        /* Dropdown Styles */
        .nav-links .dropdown {
            position: relative;
        }

        .nav-links .dropdown-toggle {
            text-decoration: none;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .nav-links .dropdown-toggle:hover {
            background-color: #722F37;
            transform: translateY(-2px);
        }

        .nav-links .dropdown-menu {
            position: absolute;
            top: 100%;
            left: 0;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 10px 0;
            min-width: 200px;
            z-index: 1000;
            display: none;
        }

        .nav-links .dropdown:hover .dropdown-menu {
            display: block;
        }

        .nav-links .dropdown-item {
            color: #333;
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            transition: background-color 0.3s;
        }

        .nav-links .dropdown-item:hover {
            background-color: #f4f4f4;
        }

        .nav-links .dropdown-item i {
            margin-right: 10px;
        }

        /* About Section */
        .about {
            padding: 80px 20px;
            background-color: #f9f9f9;
            text-align: center;
        }

        .about-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 50px;
        }

        .about h2 {
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .about-text {
            flex: 1;
            text-align: center;
        }

        .about p {
            font-size: 1.1rem;
            line-height: 1.6;
            color: #555;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Hero Section */
        .hero {
            text-align: center;
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url("images/👕 Laundry Made Easy_ Clothes Washing Service in Singapore! 🌀.jpeg");
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 180px 20px 100px;
            position: relative;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease;
        }

        .hero p {
            font-size: 1.4rem;
            margin: 20px auto;
            max-width: 800px;
            animation: fadeInUp 1s ease 0.2s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        .cta-btn {
            display: inline-block;
            background-color: rgb(114, 47, 55);
            color: white;
            padding: 15px 35px;
            text-decoration: none;
            font-size: 1.2rem;
            border-radius: 30px;
            transition: all 0.3s;
            margin-top: 20px;
            animation: fadeInUp 1s ease 0.4s;
            opacity: 0;
            animation-fill-mode: forwards;
        }

        .cta-btn:hover {
            background-color: #923D41;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.4);
        }

        /* Features Section */
        .features {
            padding: 80px 20px;
            background-color: #fff;
            text-align: center;
        }

        .features-container {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }

        .feature-card {
            text-align: center;
            padding: 30px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .feature-card .feature-image {
            width: 100%; /* Make the image fill the width of its container */
            height: 200px; /* Set a fixed height */
            object-fit: cover; /* Ensure the image covers the area without distorting */
            object-position: center; /* Center the image */
            margin-bottom: 15px; /* Add some space below the image */
            border-radius: 10px; /* Optional: add rounded corners */
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-card i {
            font-size: 2.5rem;
            color: rgb(114, 47, 55);
            margin-bottom: 20px;
        }

        /* Company Section */
        .companies {
            padding: 80px 20px;
            background-color: #f9f9f9;
            text-align: center;
        }

        .companies-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            padding: 20px;
        }

        .company-card {
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.3s;
        }

        .company-card:hover {
            transform: translateY(-10px);
        }

        /* Testimonials Section */
        .testimonials {
            padding: 80px 20px;
            background-color: #fff;
            text-align: center;
        }

        .testimonials-grid {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
        }

        .testimonial-card {
            background-color: #f9f9f9;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(134, 44, 68, 0.1);
            position: relative;
        }

        .testimonial-card .quote-icon {
            position: absolute;
            top: 10px;
            left: 20px;
            color: #862c44;
            font-size: 2rem;
            opacity: 0.2;
        }

        /*** Testimonial ***/
        .testimonial-carousel::before {
            position: absolute;
            content: "";
            top: 0;
            left: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(to right, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0) 100%);
            z-index: 1;
        }

        .testimonial-carousel::after {
            position: absolute;
            content: "";
            top: 0;
            right: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(to left, rgba(255, 255, 255, 1) 0%, rgba(255, 255, 255, 0) 100%);
            z-index: 1;
        }

        @media (min-width: 768px) {
            .testimonial-carousel::before,
            .testimonial-carousel::after {
                width: 200px;
            }
        }

        @media (min-width: 992px) {
            .testimonial-carousel::before,
            .testimonial-carousel::after {
                width: 300px;
            }
        }

        .testimonial-carousel .owl-item .testimonial-text {
            border: 5px solid var(--light);
            transform: scale(.8);
            transition: .5s;
        }

        .testimonial-carousel .owl-item.center .testimonial-text {
            transform: scale(1);
        }

        .testimonial-carousel .owl-nav {
            position: absolute;
            width: 350px;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            justify-content: space-between;
            opacity: 0;
            transition: .5s;
            z-index: 1;
        }

        .testimonial-carousel:hover .owl-nav {
            width: 300px;
            opacity: 1;
        }

        .testimonial-carousel .owl-nav .owl-prev,
        .testimonial-carousel .owl-nav .owl-next {
            position: relative;
            color: var(--primary);
            font-size: 45px;
            transition: .5s;
        }

        .testimonial-carousel .owl-nav .owl-prev:hover,
        .testimonial-carousel .owl-nav .owl-next:hover {
            color: var(--dark);
        }
            

    


        /* Footer */
        footer {
            background-color: black;
            color: white;
            padding: 60px 20px 20px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .social-links {
            display: flex;
            gap: 15px;
        }

        .social-links a {
            color: #ccc;
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .social-links a:hover {
            color: #BC1E4A;
        }

        .footer-section {
            padding: 0 20px;
        }

        .footer-section h3 {
            color: rgb(114, 47, 55);
            margin-bottom: 20px;
            font-size: 1.2rem;
        }

        .footer-section p {
            margin-bottom: 15px;
            color: #ccc;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section ul li a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-section ul li a:hover {
            color: rgb(114, 47, 55);
        }


       
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar {
                padding: 10px 15px;
            }

            .nav-links {
                display: none;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">LaundroSphere</div>
        <ul class="nav-links">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="customer_signup.php"><i class="fas fa-building"></i> Signup as Customer</a></li>
            <li><a href="#companies"><i class="fas fa-building"></i> Laundry Companies</a></li>
            <li><a href="company_signup.php"><i class="fas fa-building"></i> Signup as Company</a></li>
            <li>
                <div class="dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-h"></i> Other</a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#services"><i class="fas fa-soap"></i> Services</a></li>
                        <li><a class="dropdown-item" href="#About"><i class="fas fa-soap"></i> About</a></li>
                        <li><a class="dropdown-item" href="#services"><i class="fas fa-soap"></i> Contact Us</a></li>
                        <li><a class="dropdown-item" href="app_developer_info.html"><i class="fas fa-code"></i> About The App Developer</a></li>
                    </ul>
                </div>
            </li>
            <?php
                if (isset($_SESSION['user_id'])) {
                    echo "<li><a href='./user/user_index.php'><i class='fas fa-user'></i> Dashboard</a></li>";
                } elseif (isset($_SESSION['company_id'])) {
                    echo "<li><a href='./company/company_index.php'><i class='fas fa-user'></i> Dashboard</a></li>";
                } elseif (isset($_SESSION['admin_id'])) {
                    echo "<li><a href='./admin/admin_index.php'><i class='fas fa-user'></i> Dashboard</a></li>";
                } else {
                    echo "<li><a href='login.php'><i class='fas fa-sign-in-alt'></i> Login</a></li>";
                }
                
            ?>
        </ul>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <h1>Laundry Made Easy</h1>
        <p>Connect with trusted laundry companies, book services, and get your clothes cleaned with just a few clicks.</p>
        <a href="customer_signup.php" class="cta-btn">Get Started</a>
    </section>

    <!-- About Section -->
    <section class="about">
            <div class="about-text">
                <h2>About LaundroSphere</h2>
                <p>LaundroSphere was born from a simple idea: making laundry services accessible, convenient, and reliable. We connect customers with top-quality local laundry companies, ensuring your clothes receive the best care possible.</p>
                <p>Our platform simplifies the laundry experience by offering easy booking, transparent pricing, and a wide range of services from trusted local businesses.</p>
            </div>
        </div>
    </section>

    


    <!-- Features Section -->
    <section id="services" class="features">
        <h2>Our Potential Services</h2>
        <div class="features-container">
            <div class="feature-card">
                <img src="images/8 Secret Ingredients You Should Be Adding to Your Laundry.jpeg" alt="Washing" class="feature-image">
                <i class="fas fa-tshirt"></i>
                <h3>Washing</h3>
                <p>Professional cleaning for all your clothing items.</p>
            </div>
            <div class="feature-card">
                <img src="images/12 Essential Laundry Tips and Tricks.jpeg" alt="Ironing Service" class="feature-image">
                <i class="fas fa-shirt"></i>
                <h3>Ironing</h3>
                <p>Crisp, wrinkle-free clothes every time.</p>
            </div>
            <div class="feature-card">
                <img src="https://themewagon.github.io/Freshen/assets/img/bg/bg2.jpg" alt="Dry Cleaning" class="feature-image">
                <i class="fas fa-spray-can"></i>
                <h3>Dry Cleaning</h3>
                <p>Specialized care for delicate and premium fabrics.</p>
            </div>
            <div class="feature-card">
                <img src="images/Hampers4u Laundry Service LLC - Wash & Fold Laundry Delivery.jpeg" alt="Delivery image" class="feature-image">
                <i class="fas fa-truck"></i>
                <h3>Pickup & Delivery</h3>
                <p>Convenient service right to your doorstep.</p>
            </div>
        </div>
    </section>


    <!-- Companies Section -->
    <section id="companies" class="companies" style="background-color: #2c2c2e;">
        <h2 style="color: white;">Our Partner Laundry Companies</h2>
        <div class="companies-carousel owl-carousel owl-theme">
            <?php if ($companyResult->num_rows > 0): ?>
                <?php while ($company = $companyResult->fetch_assoc()): ?>
                    <div class="company-card">
                        <h3><?= htmlspecialchars($company['company_name']) ?></h3>
                        <p><strong>Phone:</strong> <?= htmlspecialchars($company['company_phone_number']) ?></p>
                        <p><strong>Short Quote:</strong> <?= htmlspecialchars($company['short_code']) ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: white;">No active laundry companies available at the moment.</p>
            <?php endif; ?>
        </div>
    </section>



        <!-- Testimonials Section -->
    <section class="testimonials">
        <h2>What Our Customers Say</h2>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <i class="fas fa-quote-left quote-icon"></i>
                <p>"LaundroSphere has transformed my laundry routine. It's so convenient to book and track my laundry online!"</p>
                <p><strong>- Roseline</strong></p>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-left quote-icon"></i>
                <p>"The pickup and delivery service is a game-changer. I can't imagine going back to traditional laundromats."</p>
                <p><strong>- Jeffrey John</strong></p>
            </div>
            <div class="testimonial-card">
                <i class="fas fa-quote-left quote-icon"></i>
                <p>"Excellent service and amazing customer support. LaundroSphere connects me with the best local laundry companies."</p>
                <p><strong>- Shadrack.</strong></p>
            </div>
        </div>
    </section>

     


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.testimonial-carousel').owlCarousel({
                loop: true,
                margin: 10,
                nav: false,
                autoplay: true,
                autoplayTimeout: 2000,
                responsive: {
                    0: { items: 1 },
                    768: { items: 2 },
                    992: { items: 3 }
                }
            });
        });


    
    $(document).ready(function () {
        $('.companies-carousel').owlCarousel({
            loop: true,
            margin: 20,
            nav: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 1500,
            responsive: {
                0: { items: 1 },
                600: { items: 2 },
                1000: { items: 3 }
            },
            navText: [
                '<i class="fas fa-chevron-left" style="color: white;"></i>',
                '<i class="fas fa-chevron-right" style="color: white;"></i>'
            ]
        });
    });

    </script>

    
    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About LaundroSphere</h3>
                <p>Connecting customers with top-quality laundry services across the community.</p>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="#services">Services</a></li>
                    <li><a href="#companies">Laundry Companies</a></li>
                    <li><a href="signup.php">Register</a></li>
                    <li><a href="#login.php">Login</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: laundrosphere@gmail.com</p>
                <p>Phone: + (233) 591-756-158</p>
            </div>
            <div class="footer-section">
                <h3>Follow Us</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 LaundroSphere. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>


    
</body>
</html>