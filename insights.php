<?php
session_start();

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); // Stop further execution
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unmebims');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch total items
$total_items_sql = "SELECT COUNT(*) AS total_items FROM inventory";
$total_items_result = $conn->query($total_items_sql);
$total_items = $total_items_result->fetch_assoc()['total_items'];

// Fetch low stock items (e.g., quantity < 10)
$low_stock_sql = "SELECT COUNT(*) AS low_stock_count FROM inventory WHERE quantity < 10";
$low_stock_result = $conn->query($low_stock_sql);
$low_stock_count = $low_stock_result->fetch_assoc()['low_stock_count'];

// Fetch expired items
$expired_items_sql = "SELECT COUNT(*) AS expired_count FROM inventory WHERE status = 'Expired'";
$expired_items_result = $conn->query($expired_items_sql);
$expired_items_count = $expired_items_result->fetch_assoc()['expired_count'];

// Fetch most requested items
$most_requested_sql = "
    SELECT i.item_name, SUM(r.quantity) AS total_requested
    FROM requests r
    JOIN inventory i ON r.item_id = i.item_id
    GROUP BY i.item_name
    ORDER BY total_requested DESC
    LIMIT 5
";
$most_requested_result = $conn->query($most_requested_sql);
$most_requested_items = [];
while ($row = $most_requested_result->fetch_assoc()) {
    $most_requested_items[] = $row;
}

// Fetch recent activity (e.g., last 5 inventory updates)
$recent_activity_sql = "
    SELECT item_name, quantity, status, last_updated
    FROM inventory
    ORDER BY last_updated DESC
    LIMIT 5
";
$recent_activity_result = $conn->query($recent_activity_sql);
$recent_activity = [];
while ($row = $recent_activity_result->fetch_assoc()) {
    $recent_activity[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>UNMEB Insights</title>
    <!-- External CSS -->
    <style>
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
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        h2 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .stat-box {
            background: var(--neutral-color);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .stat-box h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .stat-box p {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent-color);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        table th, table td {
            padding: 0.8rem;
            border: 1px solid #ddd;
            text-align: left;
        }
        table th {
            background-color: var(--primary-color);
            color: white;
        }
    </style>
</head>

<body>
<?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2>Inventory Insights</h2>

            <!-- Total Items -->
            <div class="insights-section">
                <h3>Key Metrics</h3>
                <div class="dashboard-stats">
                    <div class="stat-box">
                        <h3>Total Items</h3>
                        <p><?= htmlspecialchars($total_items) ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Low Stock Items</h3>
                        <p><?= htmlspecialchars($low_stock_count) ?></p>
                    </div>
                    <div class="stat-box">
                        <h3>Expired Items</h3>
                        <p><?= htmlspecialchars($expired_items_count) ?></p>
                    </div>
                </div>
            </div>

            <!-- Most Requested Items -->
            <div class="insights-section">
                <h3>Most Requested Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Total Requests</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($most_requested_items)): ?>
                            <?php foreach ($most_requested_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                                    <td><?= htmlspecialchars($item['total_requested']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2">No data available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Recent Activity -->
            <div class="insights-section">
                <h3>Recent Activity</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_activity)): ?>
                            <?php foreach ($recent_activity as $activity): ?>
                                <tr>
                                    <td><?= htmlspecialchars($activity['item_name']) ?></td>
                                    <td><?= htmlspecialchars($activity['quantity']) ?></td>
                                    <td><?= htmlspecialchars($activity['status']) ?></td>
                                    <td><?= htmlspecialchars($activity['last_updated']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No recent activity found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>