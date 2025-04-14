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

// Fetch inventory status data for dashboard numbers
$status_sql = "SELECT status, COUNT(*) AS count FROM inventory GROUP BY status";
$status_result = $conn->query($status_sql);

$status_data = [
    'Available' => 0,
    'In Use' => 0,
    'Expired' => 0,
];

while ($row = $status_result->fetch_assoc()) {
    $status_data[$row['status']] = $row['count'];
}

// Fetch total inventory items
$total_items_sql = "SELECT COUNT(*) AS total_items FROM inventory";
$total_items_result = $conn->query($total_items_sql);
$total_items = $total_items_result->fetch_assoc()['total_items'];

// Fetch low stock items (e.g., quantity < 10)
$low_stock_sql = "SELECT COUNT(*) AS low_stock_count FROM inventory WHERE quantity < 10";
$low_stock_result = $conn->query($low_stock_sql);
$low_stock_count = $low_stock_result->fetch_assoc()['low_stock_count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>UNMEB Inventory Dashboard</title>
    <!-- External CSS -->
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
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        .stat-box {
            background: var(--neutral-color);
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .stat-box h3 {
            color: var(--primary-color);
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }
        .stat-box p {
            font-size: 1.8rem;
            font-weight: bold;
            color: var(--accent-color);
        }
    </style>
</head>

<body>

<!-- Sidebar -->
<div class="sidebar">
        <h2>UNMEB</h2>
        <ul>
            <!-- Dashboard -->
            <li><a href="indexi.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <!-- Inventory Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-boxes"></i> Inventory <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="inventoryi.php">View Inventory</a></li>
                </ul>
            </li>
            <!-- Requests Dropdown -->
            <li class="dropdown active">
                <a href="#" class="dropdown-toggle"><i class="fas fa-file-alt"></i> Requests <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="make_requesti.php">Request Items</a></li>
                    <li><a href="approvei.php">Approve Requests</a></li>
                    <li><a href="approvedi.php">Approved Requests</a></li>
                    
                </ul>
            </li>
            
            <!-- Analytics Dropdown -->
            <li class="dropdown active">
                <a href="#" class="dropdown-toggle"><i class="fas fa-file-line"></i> Analytics <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="graphi.php">View Graphs</a></li>
                    
                </ul>
            </li>
           
            <!-- Logout -->
            <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>

    <!-- Dropdown Toggle Script -->
    <script>
        document.querySelectorAll('.dropdown-toggle').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                const parent = item.parentElement;
                parent.classList.toggle('active');
            });
        });
    </script>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2>Inventory Dashboard</h2>

            <!-- Dashboard Stats -->
            <div class="dashboard-stats">
                <div class="stat-box">
                    <h3>Total Items</h3>
                    <p><?= htmlspecialchars($total_items) ?></p>
                </div>
                <div class="stat-box">
                    <h3>Available Items</h3>
                    <p><?= htmlspecialchars($status_data['Available']) ?></p>
                </div>
                <div class="stat-box">
                    <h3>In Use</h3>
                    <p><?= htmlspecialchars($status_data['In Use']) ?></p>
                </div>
                <div class="stat-box">
                    <h3>Expired Items</h3>
                    <p><?= htmlspecialchars($status_data['Expired']) ?></p>
                </div>
                <div class="stat-box">
                    <h3>Low Stock Items</h3>
                    <p><?= htmlspecialchars($low_stock_count) ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>