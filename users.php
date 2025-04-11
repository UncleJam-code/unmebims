<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

// Connect to the database
$conn = new mysqli('localhost', 'root', '', 'unmebims');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    $conn->query("DELETE FROM users WHERE user_id=$user_id");
    header("Location: users.php");
}

// Handle user addition
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $conn->query("INSERT INTO users (username, password, role) VALUES ('$username', '$password', '$role')");
    header("Location: users.php");
}

// Fetch users from database
$result = $conn->query("SELECT * FROM users");

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="styles.css">
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
            padding: 1rem;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar a {
            color: white;
            text-decoration: none;
            padding: 1rem;
            display: block;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .sidebar a:hover {
            background: var(--secondary-color);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }

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

        /* Modern Table Styling */
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
        }

        .inventory-table th,
        .inventory-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .inventory-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .inventory-table tr:hover {
            background-color: #f9f9f9;
        }

        .action-link {
            color: var(--error-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .action-link:hover {
            color: #c0392b;
        }

        .add-button {
            display: inline-block;
            padding: 0.8rem 1.5rem;
            background: var(--success-color);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
            margin-top: 1rem;
        }

        .add-button:hover {
            background: #219a52;
        }

        /* Responsive Table */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar {
                width: 200px;
            }

            .inventory-table {
                display: block;
                overflow-x: auto;
            }
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
                    <li><a href="editinventory.php">Edit Item</a></li>
                    <li><a href="deleteinventory.php">Delete Item</a></li>
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

<div class="container">
    <h2>User Management</h2>

    <table>
        <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Department</th>
            <th>email</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo $row['user_id']; ?></td>
            <td><?php echo $row['username']; ?></td>
            <td><?php echo $row['role']; ?></td>
            <td><?php echo $row['department']; ?></td>
            <td><?php echo $row['email']; ?></td>
            <td>
                <a href="users.php?delete=<?php echo $row['user_id']; ?>" style="color: red;">Delete</a>
            </td>
        </tr>
        <?php } ?>
    </table>

    <div class="add-user-form">
        <h3>Add New User</h3>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <select name="role">
                <option value="admin">Admin</option>
                <option value="user">User</option>
            </select>
            <input type="department" name="department" placeholder="department" required>
            <input type="email" name="email" placeholder="email" required>
            <button type="submit" name="add_user">Add User</button>
        </form>
    </div>
</div>

</body>
</html>
