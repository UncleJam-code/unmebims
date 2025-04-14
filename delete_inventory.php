<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unmebims');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if 'id' is provided and valid
if (!isset($_GET['item_id']) || !filter_var($_GET['item_id'], FILTER_VALIDATE_INT)) {
    header("Location: inventory.php?error=Invalid item ID");
    exit();
}

$item_id = intval($_GET['item_id']);

// Prepare delete query
$sql = "DELETE * FROM inventory WHERE item_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $item_id);

// Execute and check if successful
if ($stmt->execute()) {
    header("Location: inventory.php?success=Item deleted successfully");
} else {
    header("Location: inventory.php?error=Failed to delete item");
}

$stmt->close();
$conn->close();
exit();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Inventory Item</title>
    <link rel="stylesheet" href="styles.css">
    <!-- External CSS and Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Root Variables */
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --error-color: #e74c3c;
            --success-color: #27ae60;
            --neutral-color: #f4f4f4;
        }

        /* Reset Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--neutral-color);
            line-height: 1.6;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: var(--primary-color);
            color: white;
            display: flex;
            flex-direction: column;
            padding: 1rem;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
        }
        .sidebar h2 { text-align: center; margin-bottom: 1.5rem; }
        .sidebar ul { list-style: none; padding: 0; }
        .sidebar li { margin: 10px 0; }
        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 1rem;
            display: block;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .sidebar a:hover { background: var(--secondary-color); }

        /* Dropdown Menu */
        .dropdown-menu { display: none; padding-left: 20px; }
        .dropdown.active .dropdown-menu { display: block; }
        .dropdown-menu li a {
            padding: 0.8rem;
            font-size: 0.9em;
            background: var(--neutral-color);
            color: var(--primary-color);
            margin: 2px 0;
            border-radius: 4px;
            transition: background 0.3s ease;
        }
        .dropdown-menu li a:hover { background: var(--accent-color); color: white; }

        /* Main Content */
        .main-content { margin-left: 250px; padding: 2rem; }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }

        /* Charts Layout */
        .charts-container {
            display: grid;
            gap: 2rem;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            margin-top: 2rem;
        }

        /* Chart Box */
        .chart-box {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            height: 400px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .chart-box canvas { width: 100% !important; height: 100% !important; max-height: 350px; }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; }
            .charts-container { grid-template-columns: 1fr; gap: 1.5rem; }
        }

        @media (max-width: 480px) {
            .sidebar { width: 150px; }
            .main-content { margin-left: 150px; }
        }
    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div class="container">
        <h2>Edit Inventory Item</h2>

        <form method="POST" action="">
            <label>Item ID:</label>
            <input type="number" name="item_id" value="<?= htmlspecialchars($item['item_id']) ?>" required>
            
            <label>Item Name:</label>
            <input type="text" name="item_name" value="<?= htmlspecialchars($item['item_name']) ?>" required>

            <label>Quantity:</label>
            <input type="number" name="quantity" value="<?= htmlspecialchars($item['quantity']) ?>" required>

            <label>Category:</label>
            <input type="text" name="category" value="<?= htmlspecialchars($item['category']) ?>" required>

            <label>Location:</label>
            <input type="text" name="location" value="<?= htmlspecialchars($item['location']) ?>" required>

            <label>Status:</label>
            <select name="status" required>
                <option value="Available" <?= $item['status'] == 'Available' ? 'selected' : '' ?>>Available</option>
                <option value="Out of Stock" <?= $item['status'] == 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
            </select>
            
            <label>Barcode:</label>
            <input type="text" name="barcode" value="<?= htmlspecialchars($item['barcode']) ?>" required>

            <div class="btn-container">
                <button type="submit">Delete</button>
                <a href="inventory.php"><button type="button">Cancel</button></a>
            </div>
        </form>
    </div>

</body>
</html>
