<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>LaundroSphere - Elevate Your Laundry Business</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-color: #2C7DA0;
      --secondary-color: #61A5C2;
      --background-light: #F8FBFD;
      --text-dark: #333;
      --accent-color: #468FAF;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', Arial, sans-serif;
      background-color: var(--background-light);
      color: var(--text-dark);
      line-height: 1.6;
    }

    .gradient-background {
      background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
      color: white;
    }

    

    

    nav {
      background-color: rgba(255,255,255,0.1);
      padding: 1rem;
      text-align: center;
    }

    nav ul {
      list-style: none;
      display: flex;
      justify-content: center;
      gap: 2rem;
    }

    nav a {
      color: white;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    nav a:hover {
      color: var(--accent-color);
      transform: translateY(-2px);
    }

    .container {
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
      margin-left: 300px; /* Default for side panel open */
      transition: margin-left 0.3s ease;
    }

    .section-card {
      background-color: white;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0,0,0,0.1);
      padding: 2rem;
      margin-bottom: 2rem;
      transition: all 0.3s ease;
    }

    .section-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.15);
    }

    .section-card h2 {
      color: var(--primary-color);
      margin-bottom: 1rem;
      border-bottom: 3px solid var(--secondary-color);
      padding-bottom: 0.5rem;
    }

    .feature-list, .benefits-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
      margin-top: 1rem;
    }

    .feature-item, .benefit-card {
      display: flex;
      align-items: center;
      gap: 1rem;
      background-color: var(--background-light);
      padding: 1rem;
      border-radius: 8px;
    }

    .feature-item i, .benefit-card i {
      color: var(--primary-color);
      font-size: 1.5rem;
    }

    .action-buttons {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin-top: 1.5rem;
    }

    .btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.75rem 1.5rem;
      background-color: var(--primary-color);
      color: white;
      text-decoration: none;
      border-radius: 8px;
      transition: all 0.3s ease;
      font-weight: 600;
    }

    .btn:hover {
      background-color: var(--secondary-color);
      transform: translateY(-3px);
      box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .testimonials {
      background-color: var(--background-light);
      padding: 2rem;
      border-radius: 12px;
    }

    .testimonial {
      background-color: white;
      padding: 1.5rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    footer {
      background-color: var(--text-dark);
      color: white;
      text-align: center;
      padding: 1.5rem;
    }

    .empowerment-quote {
      background-color: var(--background-light);
      border-left: 5px solid var(--primary-color);
      padding: 1.5rem;
      font-style: italic;
      margin: 2rem 0;
      position: relative;
    }

    .empowerment-quote::before {
      content: '"';
      position: absolute;
      font-size: 4rem;
      color: var(--primary-color);
      opacity: 0.2;
      top: -20px;
      left: 10px;
    }

    .success-metrics {
      display: flex;
      justify-content: space-around;
      background-color: var(--background-light);
      padding: 2rem;
      border-radius: 12px;
    }

    .metric {
      text-align: center;
      padding: 1rem;
    }

    .metric-number {
      font-size: 2.5rem;
      color: var(--primary-color);
      font-weight: bold;
    }

    .benefit-card {
      border-top: 4px solid var(--primary-color);
      box-shadow: 0 10px 25px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
    }

    .benefit-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0,0,0,0.12);
    }

    @media (max-width: 768px) {
      nav ul {
        flex-direction: column;
        gap: 1rem;
      }
    }
  </style>
</head>
<body>
    <?php include 'company_navbar.php'; ?>
  
  
  <!-- Navigation -->
  <nav>
    <ul>
      <li><a href="#business-profile"><i class="fas fa-user-edit"></i> Manage Profile</a></li>
      <li><a href="#service-management"><i class="fas fa-tasks"></i> Service Setup</a></li>
      <li><a href="#customer-insights"><i class="fas fa-chart-line"></i> Customer Analytics</a></li>
      <li><a href="#support"><i class="fas fa-headset"></i> Support</a></li>
    </ul>
  </nav>

  <div class="container">
    <!-- Empowerment Quote -->
    <section class="empowerment-quote">
      At LaundroSphere, we don't just provide a platform – we're your strategic partner in transforming your laundry business from ordinary to extraordinary. Every feature, every insight, every connection is designed to propel you towards unprecedented success.
    </section>

    <!-- Business Profile Section -->
    <section id="business-profile" class="section-card">
      <h2>Craft Your Unique Business Identity</h2>
      <p>Your business profile is more than just information – it's your digital storefront, your first impression to potential customers. We provide you with a comprehensive toolkit to showcase your unique value proposition.</p>
      
      <div class="benefits-grid">
        <div class="benefit-card">
          <i class="fas fa-store"></i>
          <div>
            <h3>Intelligent Profile Optimization</h3>
            <p>Our AI-driven recommendations help you highlight your strengths. From compelling service descriptions to strategic pricing, we ensure your profile stands out in a crowded market.</p>
          </div>
        </div>
        <div class="benefit-card">
          <i class="fas fa-clock"></i>
          <div>
            <h3>Dynamic Visibility Boost</h3>
            <p>Complete profiles receive priority placement in customer searches. Our algorithm rewards businesses that provide comprehensive, accurate information.</p>
          </div>
        </div>
        <div class="benefit-card">
          <i class="fas fa-tags"></i>
          <div>
            <h3>Brand Storytelling</h3>
            <p>Go beyond basic details. Share your laundry service's unique story, values, and commitment to customer satisfaction. Build trust before the first interaction.</p>
          </div>
        </div>
      </div>

      <div class="action-buttons">
        <a href="#" class="btn"><i class="fas fa-magic"></i> Optimize Your Profile</a>
      </div>
    </section>

    <!-- Service Management Section -->
    <section id="service-management" class="section-card">
      <h2>Revolutionize Your Service Offerings</h2>
      <p>In the competitive laundry market, flexibility and innovation are your greatest assets. LaundroSphere empowers you to create a service ecosystem that delights customers and maximizes revenue.</p>
      
      <div class="benefits-grid">
        <div class="benefit-card">
          <i class="fas fa-list-alt"></i>
          <div>
            <h3>Customizable Service Packages</h3>
            <p>Design services that reflect your unique capabilities. From eco-friendly washing to specialized fabric care, create packages that set you apart.</p>
          </div>
        </div>
        <div class="benefit-card">
          <i class="fas fa-truck"></i>
          <div>
            <h3>Dynamic Pricing Strategies</h3>
            <p>Implement intelligent pricing models. Offer volume discounts, loyalty programs, and seasonal promotions with just a few clicks.</p>
          </div>
        </div>
        <div class="benefit-card">
          <i class="fas fa-dollar-sign"></i>
          <div>
            <h3>Seamless Logistics Management</h3>
            <p>Optimize pickup, delivery, and turnaround times. Our routing algorithms help you minimize costs and maximize customer satisfaction.</p>
          </div>
        </div>
      </div>

      <div class="action-buttons">
        <a href="#" class="btn"><i class="fas fa-chart-line"></i> Design Your Services</a>
      </div>
    </section>

    <!-- Customer Insights Section -->
    <section id="customer-insights" class="section-card">
      <h2>Data-Driven Business Intelligence</h2>
      <p>Transform raw data into your most powerful business asset. Our advanced analytics turn every customer interaction into a strategic opportunity.</p>
      
      <div class="success-metrics">
        <div class="metric">
          <div class="metric-number">35%</div>
          <p>Average Revenue Increase</p>
        </div>
        <div class="metric">
          <div class="metric-number">4.7</div>
          <p>Average Customer Satisfaction Rating</p>
        </div>
        <div class="metric">
          <div class="metric-number">82%</div>
          <p>Repeat Customer Rate</p>
        </div>
      </div>

      <div class="benefits-grid">
        <div class="benefit-card">
          <i class="fas fa-chart-bar"></i>
          <div>
            <h3>Predictive Customer Insights</h3>
            <p>Understand customer preferences, predict demand, and proactively adapt your services. Our machine learning models provide unprecedented business intelligence.</p>
          </div>
        </div>
        <div class="benefit-card">
          <i class="fas fa-users"></i>
          <div>
            <h3>Performance Benchmarking</h3>
            <p>Compare your performance anonymously with other LaundroSphere partners. Identify growth opportunities and industry best practices.</p>
          </div>
        </div>
        <div class="benefit-card">
          <i class="fas fa-comments"></i>
          <div>
            <h3>Comprehensive Feedback Management</h3>
            <p>Transform customer reviews into actionable insights. Our sentiment analysis helps you continuously improve your services.</p>
          </div>
        </div>
      </div>

      <div class="action-buttons">
        <a href="#" class="btn"><i class="fas fa-database"></i> Unlock Business Insights</a>
      </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section-card testimonials">
      <h2>Partners Who Transformed Their Business</h2>
      <div class="testimonial">
        <p>"LaundroSphere didn't just provide a platform; they provided a roadmap to success. Our revenue doubled, and we've never been more connected with our customers."</p>
        <strong>- Maria Rodriguez, Clean Dreams Laundry</strong>
      </div>
      <div class="testimonial">
        <p>"The analytics are game-changing. We now make data-driven decisions that have significantly improved our operational efficiency."</p>
        <strong>- Jack Thompson, Urban Wash Solutions</strong>
      </div>
    </section>

    <!-- Support Section -->
    <section id="support" class="section-card">
      <h2>Your Success is Our Mission</h2>
      <p>Our dedicated support team is available 24/7 to ensure your journey with LaundroSphere is smooth, successful, and transformative.</p>
      <div class="action-buttons">
        <a href="#mailto:laundrosphere@gmail.com" class="btn"><i class="fas fa-envelope"></i> Email Success Team</a>
        <a href="tel:+233591765158" class="btn"><i class="fas fa-phone"></i> Schedule Consultation</a>
      </div>
    </section>
  </div>

  <!-- Footer -->
  <footer>
    <p>LaundroSphere: Powering the Future of Laundry Businesses | © 2024 All Rights Reserved</p>
  </footer>
</body>
</html>