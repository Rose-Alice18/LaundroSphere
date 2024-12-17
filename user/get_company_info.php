<?php
require_once '../db_connect.php';

header('Content-Type: application/json');

if (isset($_GET['company_id'])) {
    $company_id = intval($_GET['company_id']);

    $response = [
        'company_name' => null,
        'company_email' => null,
        'company_address' => null,
        'company_phone_number' => null,
        'short_quote' => null,
        'services' => [],
        'item_prices' => [],
        'error' => null,
    ];

    try {
        // Step 1: Fetch company details
        $company_sql = "SELECT company_name, company_email, company_address, company_phone_number, short_code 
                        FROM company 
                        WHERE company_id = ?";
        $stmt = $conn->prepare($company_sql);
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $company_result = $stmt->get_result();
        if ($company_result->num_rows === 0) {
            throw new Exception("No details found for this company.");
        }
        $company = $company_result->fetch_assoc();
        $response['company_name'] = $company['company_name'];
        $response['company_email'] = $company['company_email'];
        $response['company_address'] = $company['company_address'];
        $response['company_phone_number'] = $company['company_phone_number'];
        $response['short_quote'] = $company['short_code'] ?: "No quote provided.";

        // Step 2: Fetch available services for the company
        $services_sql = "
            SELECT st.service_type_name, sfi.price
            FROM serviceforitem sfi
            INNER JOIN servicetype st ON sfi.servicetype_id = st.service_type_id
            WHERE sfi.company_id = ?
            GROUP BY st.service_type_name, sfi.price
        ";
        $stmt = $conn->prepare($services_sql);
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $services_result = $stmt->get_result();
        if ($services_result->num_rows > 0) {
            while ($service = $services_result->fetch_assoc()) {
                $response['services'][] = [
                    'service_type_name' => $service['service_type_name'],
                    'price' => $service['price']
                ];
            }
        } else {
            throw new Exception("This company has no services listed. Please try booking another company.");
        }

        // Step 3: Fetch item prices
        $items_sql = "
            SELECT i.item_name, sfi.price
            FROM serviceforitem sfi
            INNER JOIN item i ON sfi.item_id = i.item_id
            WHERE sfi.company_id = ?
        ";
        $stmt = $conn->prepare($items_sql);
        $stmt->bind_param("i", $company_id);
        $stmt->execute();
        $items_result = $stmt->get_result();
        while ($item = $items_result->fetch_assoc()) {
            $response['item_prices'][] = [
                'item_name' => $item['item_name'],
                'price' => $item['price']
            ];
        }

        echo json_encode($response);
    } catch (Exception $e) {
        $response['error'] = $e->getMessage();
        echo json_encode($response);
    } finally {
        $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode(['error' => 'Invalid company ID.']);
}
?>
