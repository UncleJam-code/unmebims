<?php
$conn = new mysqli("localhost", "root", "", "unmebims");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to calculate counts for each status
$sql = "SELECT 
            COALESCE(SUM(CASE WHEN status='Available' THEN 1 ELSE 0 END), 0) AS available,
            COALESCE(SUM(CASE WHEN status='In Use' THEN 1 ELSE 0 END), 0) AS in_use,
            COALESCE(SUM(CASE WHEN status='Out of Stock' THEN 1 ELSE 0 END), 0) AS out_of_stock
        FROM inventory";

$result = $conn->query($sql);

if ($result) {
    $data = $result->fetch_assoc();
    echo json_encode($data);
} else {
    echo json_encode(["error" => "Query failed: " . $conn->error]);
}

$conn->close();
?>
