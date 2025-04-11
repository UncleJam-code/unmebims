<?php
session_start();

// Function to log actions
function logAction($conn, $action, $details = "") {
    if (!isset($_SESSION['user_id'])) {
        return; // Do not log if no user is logged in
    }

    $user_id = $_SESSION['user_id'];
    $action = $conn->real_escape_string($action);
    $details = $conn->real_escape_string($details);

    $query = "INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}

// Example usage:
// logAction($conn, "Added Item", "Item Name: Pen, Quantity: 100");
?>