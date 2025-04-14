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

// Handle request approval
if (isset($_GET['approve']) && filter_var($_GET['approve'], FILTER_VALIDATE_INT)) {
    $request_id = intval($_GET['approve']);
    $approved_at = date("Y-m-d H:i:s"); // Current timestamp for approval

    // Update the request status to "Approved" and set the approved_at timestamp
    $update_sql = "UPDATE requests 
                   SET status = 'Approved', approved_at = ? 
                   WHERE request_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $approved_at, $request_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Request approved successfully.";
    } else {
        $_SESSION['error_message'] = "Error approving request: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// Handle request rejection
if (isset($_GET['reject']) && filter_var($_GET['reject'], FILTER_VALIDATE_INT)) {
    $request_id = intval($_GET['reject']);

    // Update the request status to "Rejected"
    $update_sql = "UPDATE requests 
                   SET status = 'Rejected' 
                   WHERE request_id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("i", $request_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Request rejected successfully.";
    } else {
        $_SESSION['error_message'] = "Error rejecting request: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// Fetch all pending requests
$query = "SELECT r.request_id, u.full_name, i.item_name, r.quantity, r.purpose, r.status, r.created_at, r.approved_at 
          FROM requests r
          JOIN users u ON r.user_id = u.user_id
          JOIN inventory i ON r.item_id = i.item_id
          WHERE r.status = 'Approved'";
$result = $conn->query($query);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pending Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Root Variables */
        :root {
            --primary-color: #2c3e50;
            --accent-color: #3498db;
            --neutral-color: #f4f4f4;
        }
        body {
            font-family: 'Segoe UI', sans-serif;
            background: var(--neutral-color);
            line-height: 1.6;
            margin: 0;
            padding: 0;
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
        .sidebar a:hover { background: var(--accent-color); }
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
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        } 
        /* Container */
         .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        input, select, textarea {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 0.8rem 1.5rem;
            background: var(--accent-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: var(--primary-color);
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: var(--primary-color);
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        tr:hover {
            background: #f1f1f1;
        }
        .action-link {
            color: var(--accent-color);
            text-decoration: none;
        }
        .action-link:hover {
            text-decoration: underline;
        }
        .btn-container {
            display: flex;
            gap: 0.5rem;
        }
        .btn-approve {
            background: var(--accent-color);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-reject {
            background: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-approve:hover, .btn-reject:hover {
            opacity: 0.9;
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
            <h2>Pending Requests</h2>

            <?php if (isset($_SESSION['success_message'])): ?>
                <p style="color: green;"><?= htmlspecialchars($_SESSION['success_message']) ?></p>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <p style="color: red;"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <?php if ($result->num_rows > 0): ?>
                <table class="th-table">
                    <thead>
                        <tr>
                          <!--  <th>ID</th>-->
                          <th>Id</th>  
                            <th>User</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Purpose</th>
                            <th>Status</th>
                            <th>Date Requested</th>
                            <th>Date Approved</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['request_id']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= htmlspecialchars($row['quantity']) ?></td>
                            <td><?= htmlspecialchars($row['purpose']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                            <td><?= htmlspecialchars($row['created_at']) ?></td>
                            <td><?= htmlspecialchars($row['approved_at']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending requests found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>