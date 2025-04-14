<?php
session_start();

// Database connection
$conn = new mysqli('localhost', 'root', '', 'unmebims');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = ""; // Initialize error message

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? ''); // Use 'password' instead of 'password_hash'

    if (empty($username) || empty($password)) {
        $error = "Username and password are required.";
    } else {
        // Check if username exists
        $query = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Debug: Print hashed password from DB
            // echo "DB Password: " . $user['password'] . "<br>";
            // echo "Entered Password: " . $password . "<br>";


            // Verify password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                switch ($user['role']) {
                    case 'Admin':
                        header("Location: index.php");
                        break;
                    case 'User':
                        header("Location: userindex.php");
                        break;
                    default:
                        header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }

        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNMEB</title>
    <script src="https://cdn.tailwindcss.com"></script>
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

<!-- Sidebar -->
<div class="sidebar">
        <h2>UNMEB</h2>
        <ul>
            <!-- Dashboard -->
            <li><a href="indexu.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <!-- Inventory Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-boxes"></i> Inventory <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="inventoryu.php">View Inventory</a></li>
                </ul>
            </li>
            <!-- Requests Dropdown -->
            <li class="dropdown active">
                <a href="#" class="dropdown-toggle"><i class="fas fa-file-alt"></i> Requests <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="make_requestu.php">Request Items</a></li>
                    <li><a href="approved.php">Approve Requests</a></li>
                    
                </ul>
            </li>
            
            <!-- Analytics Dropdown -->
            <li class="dropdown active">
                <a href="#" class="dropdown-toggle"><i class="fas fa-file-line"></i> Analytics <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="graphu.php">View Graphs</a></li>
                    
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
            <div class="charts-container">
                <div class="chart-box">
                    <h3>Inventory Status Overview</h3>
                    <canvas id="statusPieChart"></canvas>
                </div>
                <div class="chart-box">
                    <h3>Inventory Stock Levels</h3>
                    <canvas id="inventoryBarChart"></canvas>
                </div>
                <div class="chart-box">
                    <h3>Requests Levels</h3>
                    <canvas id="requestsChart"></canvas>
                </div>
                <div class="chart-box">
                    <h2>Inventory Stock Levels</h2>
                    <canvas id="inventoryLineChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Dropdown Toggle -->
    <script>
        document.querySelectorAll('.dropdown-toggle').forEach(item => {
            item.addEventListener('click', event => {
                event.preventDefault();
                event.target.parentElement.classList.toggle('active');
            });
        });

        // Fetch and Display Charts
        fetch('get_status_data.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                let labels = ["Available", "In Use", "Out of Stock"];
                let values = [data.available, data.in_use, data.out_of_stock];

                new Chart(document.getElementById("statusPieChart"), {
                    type: "pie",
                    data: {
                        labels: labels,
                        datasets: [{
                            label: "Inventory Status",
                            data: values,
                            backgroundColor: ["#27ae60", "#3498db", "#e74c3c"],
                            borderColor: "white",
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: "Inventory status breakdown",
                                font: { size: 16 }
                            },
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('There was a problem with the fetch operation:', error);
            });

        // Fetch inventory items for the bar chart
        fetch('get_inventory_data.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                let itemNames = data.map(item => item.item_name);
                let itemValues = data.map(item => item.quantity);
                let backgroundColors = itemNames.map((_, index) => `hsl(${index * 45}, 70%, 50%)`);

                new Chart(document.getElementById("inventoryBarChart"), {
                    type: "bar",
                    data: {
                        labels: itemNames,
                        datasets: [{
                            label: "Available Stock",
                            data: itemValues,
                            backgroundColor: backgroundColors,
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
                                text: "Stock levels per item",
                                font: { size: 16 }
                            },
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            })
            .catch(error => {
                console.error('There was a problem with the fetch operation:', error);
            });
            
            // Fetch request data for the bar chart
            fetch('get_requests_data.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Extract item names and requested quantities from the data
                    const itemNames = data.map(item => item.item_name); // Names of the items requested
                    const quantities = data.map(item => item.requested_quantity); // Quantities requested

                    // Generate dynamic background colors for the bars
                    const backgroundColors = itemNames.map((_, index) => `hsl(${index * 45}, 80%, 80%)`);

                    // Initialize the bar chart
                    const ctx = document.getElementById('requestsChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar', // Bar chart type
                        data: {
                            labels: itemNames, // Item names on the x-axis
                        datasets: [{
                            label: 'Requested Quantity',
                            data: quantities, // Quantities on the y-axis
                            backgroundColor: backgroundColors, // Dynamic colors for each bar
                            borderColor: '#2c3e50', // Border color for bars
                            borderWidth: 1,
                            borderRadius: 5 // Rounded corners for bars
                        }]
                    },
                    options: {
                        responsive: true, // Make the chart responsive
                        maintainAspectRatio: false, // Allow custom aspect ratio
                        animation: {
                            duration: 2000, // Animation duration
                            easing: 'easeInOutBounce' // Bouncy animation effect
                        },
                        scales: {
                            y: {
                                beginAtZero: true, // Start y-axis at zero
                                ticks: {
                                    stepSize: 1 // Ensure integer steps on the y-axis
                                }
                            }
                        },
                        plugins: {
                            title: {
                                display: true,
                                text: 'Items requested and their quantities', // Chart title
                                font: {
                                    size: 16 // Title font size
                                }
                            },
                            legend: {
                                display: false // Hide the legend since there's only one dataset
                    }
                }
            }
        });
    })
    .catch(error => {
        console.error('There was a problem with the fetch operation:', error);
    });
            // Fetch inventory data for the line graph
            fetch('get_inventory_line.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    // Extract item names and quantities from the data
                    const itemNames = data.map(item => item.item_name); // Names of the items
                    const quantities = data.map(item => item.quantity); // Quantities of the items

                    // Initialize the line chart
                    const ctx = document.getElementById('inventoryLineChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line', // Line chart type
                        data: {
                            labels: itemNames, // Item names on the x-axis
                            datasets: [{
                                label: 'Quantity',
                                data: quantities, // Quantities on the y-axis
                                borderColor: '#3498db', // Line color
                                borderWidth: 2,
                                fill: false, // Disable area filling under the line
                                pointBackgroundColor: '#e74c3c', // Point color
                                pointRadius: 5, // Point size
                                pointHoverRadius: 7 // Hovered point size
                            }]
                        },
                        options: {
                            responsive: true, // Make the chart responsive
                            maintainAspectRatio: false, // Allow custom aspect ratio
                            animation: {
                                duration: 2000, // Animation duration
                                easing: 'easeInOutQuad' // Smooth animation effect
                            },
                            scales: {
                                y: {
                                    beginAtZero: true, // Start y-axis at zero
                                    ticks: {
                                        stepSize: 1 // Ensure integer steps on the y-axis
                                    }
                                }
                            },
                            plugins: {
                                title: {
                                    display: true,
                                    text: 'Inventory Stock Levels by Item', // Chart title
                                    font: {
                                        size: 16 // Title font size
                                    }
                                },
                                legend: {
                                    display: true, // Show the legend
                                    position: 'bottom'
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            const label = context.label || '';
                                            const value = context.raw || 0;
                                            return `${label}: ${value} units`;
                                        }
                                    }
                                }
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('There was a problem with the fetch operation:', error);
                });
    </script>

</body>
</html>
