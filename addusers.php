<?php
session_start();

// Redirect to login if user is not logged in
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

// Initialize variables
$error = '';
$success = '';

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize input
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = trim($_POST['role']);
    $department = trim($_POST['department']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Validate input
    if (empty($full_name) || empty($username) || empty($password) || empty($role) || empty($department) || empty($email)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into the database using prepared statement
        $stmt = $conn->prepare("INSERT INTO users (full_name, username, password, role, department, email) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $error = "Error preparing the SQL statement: " . $conn->error;
        } else {
            $stmt->bind_param("ssssss", $full_name, $username, $hashed_password, $role, $department, $email);

            if ($stmt->execute()) {
                $success = "User added successfully.";
                // Clear form fields after successful submission
                $full_name = $username = $password = $role = $department = $email = '';
            } else {
                $error = "Error adding user: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
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
        <h2>Add New User</h2>
        <?php if (!empty($error)) echo "<p style='color: red;'>$error</p>"; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name:</label>
                <input type="text" id="full_name" name="full_name" required>
            </div>
            <div class="form-group">
                <label for="username">User Name:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role:</label>
                <select id="role" name="role" required>
                <option value="Admin" <?= isset($role) && $role === 'Admin' ? 'selected' : '' ?>>Admin</option>
                <option value="Inventory Manager" <?= isset($role) && $role === 'inventory Manager' ? 'selected' : '' ?>>Inventory Manager</option>
                <option value="User" <?= isset($role) && $role === 'User' ? 'selected' : '' ?>>Staff</option>
            </select>
            </div>
            <div class="form-group">
                <label for="department">Department:</label>
                <input type="text" id="department" name="department" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="btn-container">
                <button type="submit">Add User</button>
                <a href="manageuser.php"><button type="button">Cancel</button></a>
            </div>
        </form>
    </div>
    <style>
    .container {
        max-width: 500px;
        margin: 50px auto;
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    .form-group {
        margin-bottom: 15px;
    }
    label {
        display: block;
        font-weight: bold;
        margin-bottom: 5px;
    }
    input, select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .btn-container {
        display: flex;
        justify-content: space-between;
    }
    button {
        padding: 10px 15px;
        border: none;
        background: #3498db;
        color: white;
        border-radius: 5px;
        cursor: pointer;
    }
    button:hover {
        background: #2980b9;
    }
</style>
</body>
</html>
