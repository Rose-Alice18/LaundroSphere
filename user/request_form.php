<?php
session_start();
require_once '../db_connect.php';

// Check if the user is logged in and the company ID is provided
if (!isset($_SESSION['user_id']) || !isset($_GET['company_id'])) {
    header('Location: laundry_request.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);
$company_id = intval($_GET['company_id']);

// Fetch distinct items
$item_query = "SELECT DISTINCT i.item_id, i.item_name FROM Item i";
$item_result = $conn->query($item_query);
$items = $item_result->fetch_all(MYSQLI_ASSOC);

// Fetch available service types for the company
$service_query = "
    SELECT DISTINCT st.service_type_id, st.service_type_name 
    FROM ServiceType st 
    INNER JOIN ServiceForItem sfi ON st.service_type_id = sfi.servicetype_id
    WHERE sfi.company_id = ?";
$stmt = $conn->prepare($service_query);
$stmt->bind_param('i', $company_id);
$stmt->execute();
$services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_date = $_POST['pickup_date'];
    $delivery_date = $_POST['delivery_date'];
    $delivery_location = trim($_POST['delivery_location']);
    $service_type_id = intval($_POST['service_type']);
    $total_price = 0;

    // Validate date logic
    if (strtotime($pickup_date) < strtotime('tomorrow')) {
        $error_message = "Pickup date cannot be today or in the past.";
    } elseif (strtotime($delivery_date) < strtotime($pickup_date . ' +2 days')) {
        $error_message = "Delivery date must be at least 2 days after the pickup date.";
    } else {
        // Insert Booking
        $booking_query = "INSERT INTO Booking (user_id, company_id, pickup_date, delivery_date, delivery_location, status, total_price, created_at) 
                          VALUES (?, ?, ?, ?, ?, 'Pending', 0, NOW())";
        $stmt = $conn->prepare($booking_query);
        $stmt->bind_param('iisss', $user_id, $company_id, $pickup_date, $delivery_date, $delivery_location);
        $stmt->execute();
        $booking_id = $stmt->insert_id;

        // Fetch prices for the selected service type
        $price_query = "
            SELECT i.item_id, sfi.price
            FROM ServiceForItem sfi
            INNER JOIN Item i ON sfi.item_id = i.item_id
            WHERE sfi.company_id = ? AND sfi.servicetype_id = ?";
        $stmt = $conn->prepare($price_query);
        $stmt->bind_param('ii', $company_id, $service_type_id);
        $stmt->execute();
        $prices = [];
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $prices[$row['item_id']] = $row['price'];
        }
        $stmt->close();

        // Insert BookingDetails
        $detail_query = "INSERT INTO BookingDetail (booking_id, service_for_item_id, quantity) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($detail_query);
        foreach ($items as $item) {
            $item_id = $item['item_id'];
            $quantity = intval($_POST["item_$item_id"] ?? 0);
            if ($quantity > 0 && isset($prices[$item_id])) {
                $price = $prices[$item_id];
                $total_price += $quantity * $price;
                $stmt->bind_param('iii', $booking_id, $service_type_id, $quantity);
                $stmt->execute();
            }
        }
        $stmt->close();

        // Update total price in Booking table
        $update_query = "UPDATE Booking SET total_price = ? WHERE booking_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('di', $total_price, $booking_id);
        $stmt->execute();
        $stmt->close();

        $success_message = "Request submitted successfully. Total: ₵" . number_format($total_price, 2);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laundry Request Form | LaundroSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }

        .form-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: bold;
            display: block;
        }

        input, select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .btn {
            display: block;
            width: 100%;
            background-color: #8c2f6f;
            color: #fff;
            border: none;
            border-radius: 5px;
            padding: 10px;
            font-size: 1rem;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #6e234e;
        }

        .info {
            color: #8c2f6f;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
    </style>
    <script>
        function setDeliveryDateRestriction() {
            const pickupDateInput = document.getElementById('pickup_date');
            const deliveryDateInput = document.getElementById('delivery_date');
            
            pickupDateInput.addEventListener('change', function () {
                const pickupDate = new Date(this.value);
                const minDeliveryDate = new Date(pickupDate);
                minDeliveryDate.setDate(minDeliveryDate.getDate() + 2);
                
                deliveryDateInput.min = minDeliveryDate.toISOString().split('T')[0];
            });

            // Set minimum pickup date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            pickupDateInput.min = tomorrow.toISOString().split('T')[0];
        }

        window.onload = setDeliveryDateRestriction;
    </script>
</head>
<body>
    <?php include "user_navbar.php"; ?>
    <div class="form-container">
        <h2>Laundry Request Form</h2>
        <?php if (isset($success_message)): ?>
            <div class="message success"><?= htmlspecialchars($success_message) ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="message error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        <p class="info">Note: Delivery date must be at least 2 days after the pickup date.</p>
        
        <form method="POST">
            <div class="form-group">
                <label for="service_type">Select Service Type</label>
                <select name="service_type" id="service_type" required>
                    <option value="">-- Select Service Type --</option>
                    <?php foreach ($services as $service): ?>
                        <option value="<?= $service['service_type_id'] ?>"><?= htmlspecialchars($service['service_type_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="pickup_date">Pickup Date</label>
                <input type="date" name="pickup_date" id="pickup_date" required>
            </div>

            <div class="form-group">
                <label for="delivery_date">Delivery Date</label>
                <input type="date" name="delivery_date" id="delivery_date" required>
            </div>

            <div class="form-group">
                <label for="delivery_location">Delivery Location</label>
                <input type="text" name="delivery_location" id="delivery_location" required>
            </div>

            <h3>Clothing Items</h3>
            <?php foreach ($items as $item): ?>
                <div class="form-group">
                    <label><?= htmlspecialchars($item['item_name']) ?> (Quantity):</label>
                    <input type="number" name="item_<?= $item['item_id'] ?>" min="0" placeholder="Enter quantity">
                </div>
            <?php endforeach; ?>

            <button type="submit" class="btn">Submit Request</button>
        </form>
    </div>
</body>
</html>
