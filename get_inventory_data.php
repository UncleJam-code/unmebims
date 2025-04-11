<?php
// Start the session (optional, if needed for user authentication)
session_start();

// Check if the user is logged in (optional, depending on your requirements)
if (!isset($_SESSION['user_id'])) {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unmebims');

// Check connection
if ($conn->connect_error) {
    header("HTTP/1.1 500 Internal Server Error");
    echo json_encode(["error" => "Database connection failed: " . $conn->connect_error]);
    exit();
}

// Fetch inventory data
$sql = "SELECT item_name, quantity FROM inventory";
$result = $conn->query($sql);

// Initialize an array to store the data
$data = [];

if ($result->num_rows > 0) {
    // Loop through each row and add it to the data array
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "item_name" => htmlspecialchars($row['item_name']), // Sanitize output
            "quantity" => intval($row['quantity']) // Ensure quantity is an integer
        ];
    }
} else {
    // Return an empty array if no data is found
    $data = [];
}

// Close the database connection
$conn->close();

// Set the content type to JSON
header('Content-Type: application/json');

// Return the data as a JSON response
echo json_encode($data);
?>