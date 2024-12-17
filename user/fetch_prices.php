<?php
require_once '../db_connect.php';

$company_id = intval($_GET['company_id']);
$service_type_id = intval($_GET['service_type']);

$query = "
    SELECT i.item_id, sfi.price 
    FROM ServiceForItem sfi
    INNER JOIN Item i ON sfi.item_id = i.item_id
    WHERE sfi.company_id = ? AND sfi.servicetype_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $company_id, $service_type_id);
$stmt->execute();
$result = $stmt->get_result();

$prices = [];
while ($row = $result->fetch_assoc()) {
    $prices[$row['item_id']] = $row['price'];
}

echo json_encode($prices);
