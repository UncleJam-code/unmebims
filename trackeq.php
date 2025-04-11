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

// Fetch all available inventory items for the dropdown
$sql = "SELECT * FROM inventory WHERE status = 'Available'";
$result = $conn->query($sql);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = intval($_POST['item_id']);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $purpose = trim($_POST['purpose']);
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if (!$quantity || $quantity <= 0) {
        echo "<p style='color: red;'>Invalid quantity.</p>";
    } elseif (empty($purpose)) {
        echo "<p style='color: red;'>Purpose is required.</p>";
    } else {
        // Check if sufficient stock is available
        $check_stock_sql = "SELECT quantity FROM inventory WHERE item_id = ?";
        $stmt = $conn->prepare($check_stock_sql);
        $stmt->bind_param("i", $item_id);
        $stmt->execute();
        $result_stock = $stmt->get_result();
        $stock = $result_stock->fetch_assoc();

        if ($stock && $stock['quantity'] >= $quantity) {
            // Deduct quantity from inventory
            $new_stock = $stock['quantity'] - $quantity;
            $update_inventory_sql = "UPDATE inventory SET quantity = ? WHERE item_id = ?";
            $stmt = $conn->prepare($update_inventory_sql);
            $stmt->bind_param("ii", $new_stock, $item_id);

            if ($stmt->execute()) {
                // Log the equipment pickup
                $log_sql = "INSERT INTO equipment_logs (item_id, user_id, quantity, purpose, action) VALUES (?, ?, ?, ?, 'Picked')";
                $stmt = $conn->prepare($log_sql);
                $stmt->bind_param("iiis", $item_id, $user_id, $quantity, $purpose);

                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Equipment picked successfully.";
                    header("Location: track_equipment.php");
                    exit();
                } else {
                    echo "<p style='color: red;'>Error logging equipment pickup: " . htmlspecialchars($stmt->error) . "</p>";
                }
            } else {
                echo "<p style='color: red;'>Error updating inventory: " . htmlspecialchars($stmt->error) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>Not enough stock available for this item.</p>";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Equipment Pickup</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Form Styles */
        form {
            max-width: 500px;
            margin: 0 auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        form input, form select, form textarea {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        form button {
            padding: 0.8rem 1.5rem;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        form button:hover {
            background: var(--primary-color);
        }
        .btn-container {
            display: flex;
            gap: 1rem;
        }
        .btn-container a {
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>UNMEB</h2>
        <ul>
            <!-- Dashboard -->
            <li><a href="adminindex.html"><i class="fas fa-home"></i> Dashboard</a></li>
            <!-- Inventory Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-boxes"></i> Inventory <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="inventory.php">View Inventory</a></li>
                    <li><a href="addinventory.php">Add New Item</a></li>
                </ul>
            </li>
            <!-- Requests Dropdown -->
            <li class="dropdown active">
                <a href="#" class="dropdown-toggle"><i class="fas fa-file-alt"></i> Requests <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="make_request.php">Request Items</a></li>
                    <li><a href="approve.php">Approve Requests</a></li>
                    <li><a href="trackequipment.php">Track Equipment</a></li>
                </ul>
            </li>
            <!-- Reports Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-chart-bar"></i> Reports <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="report.php">Inventory Reports</a></li>
                    <li><a href="audit_logs.php">Audit Logs</a></li>
                </ul>
            </li>
            <!-- User Management Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-users"></i> Users <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="addusers.php">Add New User</a></li>
                    <li><a href="manageuser.php">Edit User Information</a></li>
                </ul>
            </li>
            <!-- Logout -->
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2>Track Equipment Pickup</h2>
            <form method="POST" action="">
                <label for="item_id">Select Item:</label>
                <select id="item_id" name="item_id" required>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <option value="<?= $row['item_id'] ?>"><?= htmlspecialchars($row['item_name']) ?></option>
                    <?php endwhile; ?>
                </select>

                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" min="1" required>

                <label for="purpose">Purpose:</label>
                <textarea id="purpose" name="purpose" rows="3" placeholder="Describe why you need this item" required></textarea>

                <button type="submit">Submit Pickup</button>
            </form>
        </div>
    </div>
</body>
</html>