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

// Fetch inventory status data for the pie chart
$status_sql = "SELECT status, COUNT(*) AS count FROM inventory GROUP BY status";
$status_result = $conn->query($status_sql);
$status_data = [];
while ($row = $status_result->fetch_assoc()) {
    $status_data[$row['status']] = $row['count'];
}

// Fetch inventory stock levels for the bar chart
$stock_sql = "SELECT item_name, quantity FROM inventory ORDER BY quantity DESC LIMIT 10";
$stock_result = $conn->query($stock_sql);
$stock_data = [];
while ($row = $stock_result->fetch_assoc()) {
    $stock_data[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNMEB Inventory Dashboard</title>
    <link rel="stylesheet" href="styles.css"> <!-- External CSS -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --error-color: #e74c3c;
            --success-color: #27ae60;
            --neutral-color: #f4f4f4;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
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
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        h2 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
            text-align: center;
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
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .chart-box h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        /* Chart Sizing */
        .chart-box canvas {
            width: 100% !important;
            height: 400px !important;
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar { width: 200px; }
            .main-content { margin-left: 200px; }
            .charts-container { grid-template-columns: 1fr; gap: 1.5rem; }
        }
        @media (max-width: 480px) {
            .sidebar { width: 150px; }
            .main-content { margin-left: 150px; }
            .chart-box canvas { height: 300px !important; }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>UNMEB</h2>
        <ul>
            <!-- Dashboard -->
            <li><a href="index.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <!-- Inventory Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-boxes"></i> Inventory <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="inventory.php">View Inventory</a></li>
                    <li><a href="addinventory.php">Add New Item</a></li>
                    <li><a href="editinventory.php">Edit Item</a></li>
                    <li><a href="deleteinventory.php">Delete Item</a></li>
                </ul>
            </li>
            <!-- Requests Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-file-alt"></i> Requests <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="request.php">Manage Exam Materials</a></li>
                    <li><a href="storesupplies.php">Store Supplies</a></li>
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
                    <li><a href="editusers.php">Edit User Information</a></li>
                    <li><a href="access_control.php">Access Control</a></li>
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
            <h2>Welcome to UNMEB Inventory Management</h2>
            <p style="text-align: center; color: var(--primary-color);">Your central hub for managing inventory, tracking requests, and generating reports.</p>
            
            <!-- Charts Container -->
            <div class="charts-container">
                <!-- Pie Chart -->
                <div class="chart-box">
                    <h3>Inventory Status Overview</h3>
                    <canvas id="statusPieChart"></canvas>
                </div>
                <!-- Bar Graph -->
                <div class="chart-box">
                    <h3>Top 10 Items by Stock Levels</h3>
                    <canvas id="inventoryBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Scripts -->
    <script>
        // Pie Chart Data
        const statusData = {
            labels: <?= json_encode(array_keys($status_data)) ?>,
            datasets: [{
                label: "Inventory Status",
                data: <?= json_encode(array_values($status_data)) ?>,
                backgroundColor: ["#27ae60", "#3498db", "#e74c3c"],
                borderColor: "white",
                borderWidth: 2
            }]
        };

        // Render Pie Chart
        const statusPieChart = new Chart(document.getElementById("statusPieChart"), {
            type: "pie",
            data: statusData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: "Inventory Status Breakdown",
                        font: { size: 16 }
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // Bar Chart Data
        const stockLabels = <?= json_encode(array_column($stock_data, 'item_name')) ?>;
        const stockValues = <?= json_encode(array_column($stock_data, 'quantity')) ?>;

        // Render Bar Chart
        const inventoryBarChart = new Chart(document.getElementById("inventoryBarChart"), {
            type: "bar",
            data: {
                labels: stockLabels,
                datasets: [{
                    label: "Available Stock",
                    data: stockValues,
                    backgroundColor: stockLabels.map((_, index) => `hsl(${index * 45}, 70%, 50%)`),
                    borderColor: "#2c3e50",
                    borderWidth: 1,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: {
                    duration: 2000,
                    easing: 'easeInOutBounce'
                },
                scales: {
                    y: { beginAtZero: true }
                },
                plugins: {
                    title: {
                        display: true,
                        text: "Stock Levels per Item",
                        font: { size: 16 }
                    },
                    legend: {
                        display: false
                    }
                }
            }
        });
    </script>
</body>
</html>