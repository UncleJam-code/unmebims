<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unmebims');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch request data with item names and quantities
$query = "
    SELECT 
        r.request_id, 
        i.item_name, 
        r.quantity AS requested_quantity, 
        r.purpose, 
        r.status, 
        r.created_at, 
        r.approved_at
    FROM 
        requests r
    JOIN 
        inventory i 
    ON 
        r.item_id = i.item_id
";

$result = $conn->query($query);

$data = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'request_id' => (int)$row['request_id'],
            'item_name' => htmlspecialchars($row['item_name']),
            'requested_quantity' => (int)$row['requested_quantity'],
            'purpose' => htmlspecialchars($row['purpose']),
            'status' => htmlspecialchars($row['status']),
            'created_at' => htmlspecialchars($row['created_at']),
            'approved_at' => htmlspecialchars($row['approved_at'] ?? 'Not Approved')
        ];
    }
}

// Return data as JSON
header('Content-Type: application/json');
echo json_encode($data);

$conn->close();
?>