<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'unmebims');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch all users with plain-text passwords
$query = "SELECT user_id, password FROM users";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $user_id = $row['user_id'];
        $plain_password = $row['password'];

        // Check if the password is already hashed
        if (!password_needs_rehash($plain_password, PASSWORD_DEFAULT)) {
            echo "Password for user ID $user_id is already hashed. Skipping.<br>";
            continue;
        }

        // Hash the plain-text password
        $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);

        // Update the database with the hashed password
        $update_query = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("si", $hashed_password, $user_id);

        if ($stmt->execute()) {
            echo "Password for user ID $user_id has been hashed successfully.<br>";
        } else {
            echo "Error updating password for user ID $user_id: " . $stmt->error . "<br>";
        }
    }
} else {
    echo "No users found in the database.";
}

$conn->close();
?>