<?php
session_start();
require_once '../db_connect.php';

if (!isset($_SESSION['company_id'])) {
    header('Location: ../login.php');
    exit();
}

$company_id = intval($_SESSION['company_id']);

// Fetch available service types
$service_types_query = "SELECT service_type_id, service_type_name FROM servicetype ORDER BY service_type_name ASC";
$service_types_result = $conn->query($service_types_query);

// Fetch services already added by the company
$company_services_query = "
    SELECT s.service_id, st.service_type_name, st.service_type_id, si.item_id, i.item_name, si.price, si.service_for_item_id
    FROM service s
    JOIN serviceforitem si ON s.service_id = si.service_id
    JOIN servicetype st ON s.service_type_id = st.service_type_id
    JOIN item i ON si.item_id = i.item_id
    WHERE s.company_id = ?
    ORDER BY st.service_type_name, i.item_name ASC";
$stmt = $conn->prepare($company_services_query);
$stmt->bind_param('i', $company_id);
$stmt->execute();
$company_services_result = $stmt->get_result();
$stmt->close();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_service'])) {
        // Existing add service logic remains the same
        $servicetype_id = intval($_POST['servicetype_id']);
        $default_price = floatval($_POST['default_price']);

        if ($servicetype_id === 'other' && !empty($_POST['custom_service_name'])) {
            $custom_service_name = htmlspecialchars(trim($_POST['custom_service_name']));
            // Insert custom service into servicetype table
            $insert_custom_service_query = "INSERT INTO servicetype (service_type_name) VALUES (?)";
            $stmt = $conn->prepare($insert_custom_service_query);
            $stmt->bind_param('s', $custom_service_name);
            $stmt->execute();
            $servicetype_id = $stmt->insert_id;
            $stmt->close();
        }

        // Check for duplicate service type for the company
        $duplicate_check_query = "
            SELECT COUNT(*) AS count
            FROM service
            WHERE service_type_id = ? AND company_id = ?";
        $stmt = $conn->prepare($duplicate_check_query);
        $stmt->bind_param('ii', $servicetype_id, $company_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($result['count'] > 0) {
            $_SESSION['error_message'] = 'This service type has already been added.';
            header('Location: service_management.php');
            exit();
        }

        // Insert service into service table
        $insert_service_query = "INSERT INTO service (company_id, service_type_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_service_query);
        $stmt->bind_param('ii', $company_id, $servicetype_id);
        $stmt->execute();
        $service_id = $stmt->insert_id;
        $stmt->close();

        // Insert default prices for all items into serviceforitem table
        $items_query = "SELECT item_id FROM item";
        $items_result = $conn->query($items_query);

        while ($item = $items_result->fetch_assoc()) {
            $item_id = intval($item['item_id']);
            $insert_serviceforitem_query = "
                INSERT INTO serviceforitem (service_id, item_id, price, company_id, servicetype_id) 
                VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_serviceforitem_query);
            $stmt->bind_param('iidii', $service_id, $item_id, $default_price, $company_id, $servicetype_id);
            $stmt->execute();
            $stmt->close();
        }

        header('Location: service_management.php');
        exit();
    } elseif (isset($_POST['update_price'])) {
        // Existing update price logic remains the same
        $service_for_item_id = intval($_POST['service_for_item_id']);
        $price = floatval($_POST['price']);

        if ($price <= 0) {
            $_SESSION['error_message'] = 'Price cannot be set to 0.';
            header('Location: service_management.php');
            exit();
        }

        $update_price_query = "UPDATE serviceforitem SET price = ? WHERE service_for_item_id = ? AND company_id = ?";
        $stmt = $conn->prepare($update_price_query);
        $stmt->bind_param('dii', $price, $service_for_item_id, $company_id);
        $stmt->execute();
        $stmt->close();

        header('Location: service_management.php');
        exit();
    } elseif (isset($_POST['delete_service_type'])) {
        // New delete service type logic
        $service_type_id = intval($_POST['service_type_id']);

        // First, delete related entries in serviceforitem table
        $delete_serviceforitem_query = "
            DELETE FROM serviceforitem 
            WHERE servicetype_id = ? AND company_id = ?";
        $stmt = $conn->prepare($delete_serviceforitem_query);
        $stmt->bind_param('ii', $service_type_id, $company_id);
        $stmt->execute();
        $stmt->close();

        // Then, delete related entries in service table
        $delete_service_query = "
            DELETE FROM service 
            WHERE service_type_id = ? AND company_id = ?";
        $stmt = $conn->prepare($delete_service_query);
        $stmt->bind_param('ii', $service_type_id, $company_id);
        $stmt->execute();
        $stmt->close();

        header('Location: service_management.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Management | LaundroSphere</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/company_dashboard.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f7f6;
            color: #333;
            margin: 0;
            padding: 0;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            margin-left: 300px;
            transition: margin-left 0.3s ease;
        }

        @media (max-width: 768px) {
            .container {
                margin-left: 0;
            }
        }

        
        .service-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f1f4f3;
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .service-header .delete-service-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 3px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .service-header .delete-service-btn:hover {
            background-color: #c0392b;
        }

        form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #4a90e2;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #e0e0e0;
        }

        table th {
            background-color: #4a90e2;
            color: white;
            font-weight: 600;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #357abd;
        }

        .alert-danger {
            background-color: #f2dede;
            color: #a94442;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .price-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .price-form input {
            flex: 1;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <?php include 'company_navbar.php'; ?>

    <div class="container">
        <h1>Service Management</h1>

        <!-- Error Message -->
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Add Service Form -->
        <form method="POST">
            <h2>Add Service</h2>
            <div class="form-group">
                <label for="servicetype_id">Service Type</label>
                <select name="servicetype_id" id="servicetype_id" required>
                    <option value="">Select a Service</option>
                    <?php 
                    // Reset pointer for service types
                    $service_types_result->data_seek(0);
                    while ($service = $service_types_result->fetch_assoc()) : ?>
                        <option value="<?= $service['service_type_id']; ?>"><?= htmlspecialchars($service['service_type_name']); ?></option>
                    <?php endwhile; ?>
                    <option value="other">Other</option>
                </select>
            </div>
            <div id="custom_service" style="display: none;" class="form-group">
                <label for="custom_service_name">Custom Service Name</label>
                <input type="text" name="custom_service_name" id="custom_service_name">
            </div>
            <div class="form-group">
                <label for="default_price">Default Price</label>
                <input type="number" step="0.01" name="default_price" id="default_price" required>
            </div>
            <button type="submit" name="add_service" class="btn">Add Service</button>
        </form>

        <!-- Manage Services -->
        <?php
        // Reset pointer for company services
        $company_services_result->data_seek(0);
        $current_type = null;
        $current_type_id = null;
        $items_table = '';
        while ($service = $company_services_result->fetch_assoc()) :
            if ($service['service_type_name'] !== $current_type) :
                if ($current_type) echo $items_table . '</tbody></table>';
                $current_type = $service['service_type_name'];
                $current_type_id = $service['service_type_id'];
                $items_table = '
                    <div class="service-header">
                        <h3>' . htmlspecialchars($current_type) . '</h3>
                        <form method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete this service type?\');">
                            <input type="hidden" name="service_type_id" value="' . $current_type_id . '">
                            <button type="submit" name="delete_service_type" class="delete-service-btn">
                                <i class="fas fa-trash"></i> Delete Service Type
                            </button>
                        </form>
                    </div>
                    <table><thead><tr><th>Item</th><th>Price</th><th>Actions</th></tr></thead><tbody>';
            endif;
            $items_table .= '<tr>
                <td>' . htmlspecialchars($service['item_name']) . '</td>
                <td>$' . htmlspecialchars(number_format($service['price'], 2)) . '</td>
                <td>
                    <form method="POST" class="price-form">
                        <input type="hidden" name="service_for_item_id" value="' . $service['service_for_item_id'] . '">
                        <input type="number" step="0.01" name="price" value="' . htmlspecialchars($service['price']) . '" required>
                        <button type="submit" name="update_price" class="btn">Update</button>
                    </form>
                </td>
            </tr>';
        endwhile;
        if ($current_type) echo $items_table . '</tbody></table>';
        ?>
    </div>

    <script>
        document.getElementById('servicetype_id').addEventListener('change', function () {
            document.getElementById('custom_service').style.display = (this.value === 'other') ? 'block' : 'none';
        });
    </script>
</body>
</html>