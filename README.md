LaundroSphere Business Logic Documentation

Addressed to: Dr. Govindha Yeluripati

Creator: Roseline Tsatsu  
Class of: 2026  
Contact: 0591765158  
Email: roseline.tsatsu@ashesi.edu.gh  

---

Overview
LaundroSphere is an innovative web application designed to provide a seamless platform for managing laundry services. It connects customers with service providers, allowing for efficient bookings, secure payments, and a transparent feedback mechanism. 

The application prioritizes user-friendliness, secure transactions, and accountability through feedback and performance tracking. 

---

Navigation Overview

Accessing the Application
1. Homepage:
   - Users can log in or register as a new customer or service provider.
   - First-time users can create an account through a simple registration process.

2. Dashboard:
   - Customers: View and manage bookings, submit feedback, and access service history.
   - Service Providers: Manage service offerings, track customer feedback, and view earnings.
   - Administrators: Oversee user activities, bookings, and payment logs through an admin panel.

3. Feedback Section:
   - Customers can submit feedback for specific companies they’ve used services from.
   - Feedback history allows customers to review all previously submitted feedback.

4. Admin Panel:
   - Manage all users, services, bookings, and payments.
   - Generate reports on system usage and evaluate service performance.
  
   
**Note:**
     Admin panel can only be accessed by
   email: laundryadmin@gmail.com
   password: adminpassword

**All other Users can register with a new email to access the web app** 

---

Business Logic

Service Transactions

1. Booking a Service:
   - Customers browse services offered by various providers.
   - Once a service is selected, a booking is created, and a confirmation is sent to both the customer and service provider.

2. Pricing Logic:
   - Pricing is determined based on the **`ServiceForItem`** table in the database.
     - Each service is linked to a specific company and item (e.g., washing shirts, ironing trousers).
     - The **price** column defines the cost for each service.
   - Total cost for a booking is calculated by aggregating the prices of all selected services.

3. Payment Processing:
   - Customers can pay securely via integrated payment gateways.
   - Payment records are stored in the **`Payment`** table, linked to the booking ID.
   - Real-time updates ensure the status of bookings (e.g., in progress, pending).

4. Feedback Management:
   - Feedback records are stored in the `Feedback` table.
   - Customers can only submit feedback for services they’ve booked, ensuring authenticity.
   - Companies can review customer feedback for performance improvements.

Service Management Logic

1. Customer Side:
   - Access a personalized dashboard to manage bookings and payments.
   - View service history and feedback records for transparency.

2. Service Provider Side:
   - Update service offerings and prices via the dashboard.
   - View bookings associated with their company.

3. Administrator Side:
   - Add, update, or delete user accounts, services, and companies.
   - Monitor and audit feedback submissions and service usage.

---

Database Schema (LaundroSphereDB)

 Core Tables:

1. Users:
   - Stores user information, including roles (customer, provider, admin).
   
2. Company:
   - Stores details of registered laundry service providers.

3. Booking:
   - Tracks customer bookings with references to associated services and payments.

4. ServiceForItem:
   - Links specific services (e.g., washing, ironing) to companies and their respective prices.

5. Payment:
   - Logs payment transactions, including timestamps and amounts.

6. Feedback:
   - Stores customer feedback linked to specific companies and services.

---

Administrator Logic

1. User Management:
   - Create, edit, or delete user accounts.
   - Assign roles to users (e.g., customer, provider, admin).

2. Service Oversight:
   - Approve or reject new service offerings by providers.
   - Monitor bookings and ensure compliance with platform rules.

3. System Reports:
   - Generate reports on user activity, bookings, payments, and feedback trends.
   - Analyze data to improve platform efficiency and user satisfaction.

---

**Note Again:**
     Admin panel can only be accessed by
   email: laundryadmin@gmail.com
   password: adminpassword

**All other Users can register with a new email to access the web app** 



## Business Profile

**Application Name:** LaundroSphere  
**Founder:** Roseline Tsatsu  
**Contact Email:** roseline.tsatsu@ashesi.edu.gh  
**Target Users:** Customers, Laundry Service Providers, and Administrators.  

---

## Contact for Further Inquiries
For additional information, please feel free to reach out:
- **Name:** Roseline Tsatsu
- **Class of:** 2026
- **Phone:** 0591765158
- **Email:** roseline.tsatsu@ashesi.edu.gh

