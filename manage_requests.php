<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Check if user has admin privileges
if ($_SESSION['role'] !== 'admin') {
    die("Access denied.");
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unmebims');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle request approval or rejection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $request_id = intval($_POST['request_id']);
    $action = $_POST['action']; // 'approve' or 'reject'

    if ($action === 'approve') {
        // Fetch request details
        $sql = "SELECT r.item_id, r.quantity, i.quantity AS current_stock 
                FROM requests r 
                JOIN inventory i ON r.item_id = i.item_id 
                WHERE r.request_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $request = $result->fetch_assoc();

        if ($request && $request['current_stock'] >= $request['quantity']) {
            // Deduct quantity from inventory
            $new_stock = $request['current_stock'] - $request['quantity'];
            $update_inventory_sql = "UPDATE inventory SET quantity = ? WHERE item_id = ?";
            $stmt = $conn->prepare($update_inventory_sql);
            $stmt->bind_param("ii", $new_stock, $request['item_id']);

            if ($stmt->execute()) {
                // Update request status to 'Approved'
                $update_request_sql = "UPDATE requests SET status = 'Approved' WHERE request_id = ?";
                $stmt = $conn->prepare($update_request_sql);
                $stmt->bind_param("i", $request_id);
                $stmt->execute();
                $_SESSION['success_message'] = "Request approved and inventory updated.";
            } else {
                $_SESSION['error_message'] = "Error updating inventory.";
            }
        } else {
            $_SESSION['error_message'] = "Not enough stock to approve this request.";
        }
    } elseif ($action === 'reject') {
        // Update request status to 'Rejected'
        $update_request_sql = "UPDATE requests SET status = 'Rejected' WHERE request_id = ?";
        $stmt = $conn->prepare($update_request_sql);
        $stmt->bind_param("i", $request_id);
        $stmt->execute();
        $_SESSION['success_message'] = "Request rejected.";
    }

    header("Location: manage_requests.php");
    exit();
}

// Fetch all pending and resolved requests
$sql = "SELECT r.request_id, r.item_id, r.user_id, r.quantity, r.purpose, r.status, i.item_name, u.username 
        FROM requests r 
        JOIN inventory i ON r.item_id = i.item_id 
        JOIN users u ON r.user_id = u.user_id";
$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: var(--primary-color);
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .action-link {
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
        }
        .approve { background: var(--success-color); }
        .reject { background: var(--error-color); }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2>Manage Requests</h2>
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>User</th>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Purpose</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['request_id'] ?></td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['item_name']) ?></td>
                        <td><?= $row['quantity'] ?></td>
                        <td><?= htmlspecialchars($row['purpose']) ?></td>
                        <td><?= $row['status'] ?></td>
                        <td>
                            <?php if ($row['status'] === 'Pending'): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                    <button type="submit" name="action" value="approve" class="action-link approve">Approve</button>
                                </form>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="request_id" value="<?= $row['request_id'] ?>">
                                    <button type="submit" name="action" value="reject" class="action-link reject">Reject</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>