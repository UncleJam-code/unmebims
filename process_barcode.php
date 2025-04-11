<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the raw POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['barcode'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'No barcode provided']);
    exit();
}

$barcode = trim($data['barcode']);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unmebims');
if ($conn->connect_error) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

// Query to find the item by barcode
$sql = "SELECT * FROM inventory WHERE barcode = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $barcode);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $item = $result->fetch_assoc();
    echo json_encode(['success' => true, 'item' => $item]);
} else {
    echo json_encode(['success' => false, 'message' => 'No item found with this barcode']);
}

$stmt->close();
$conn->close();
?>