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

// Initialize variables
$error_message = '';
$success_message = '';

// Handle user deletion
if (isset($_GET['delete']) && filter_var($_GET['delete'], FILTER_VALIDATE_INT)) {
    $user_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User deleted successfully.";
        header("Location: manageuser.php");
        exit();
    } else {
        $error_message = "Error deleting user: " . htmlspecialchars($stmt->error);
    }
    $stmt->close();
}

// Handle editing or adding users
$edit_mode = false;
$user_id = $full_name = $username = $password = $role = $department = $email = "";

if (isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT)) {
    $user_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        die("User not found.");
    }

    $user = $result->fetch_assoc();
    $full_name = htmlspecialchars($user['full_name']);
    $username = htmlspecialchars($user['username']);
    $password = htmlspecialchars($user['password']);
    $role = htmlspecialchars($user['role']);
    $department = htmlspecialchars($user['department']);
    $email = htmlspecialchars($user['email']);
    $edit_mode = true;
    $stmt->close();
}

// Handle form submission for adding/editing users
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize input
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);
    $department = trim($_POST['department']);
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

    // Validate inputs
    if (empty($full_name) || empty($username) || empty($password) || empty($role) || empty($department) || empty($email)) {
        $error_message = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        // Hash the password for security
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        if ($edit_mode) {
            // Update existing user
            $update_sql = "UPDATE users 
                           SET full_name=?, username=?, password=?, role=?, department=?, email=? 
                           WHERE user_id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ssssssi", $full_name, $username, $hashed_password, $role, $department, $email, $user_id);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "User updated successfully.";
                header("Location: manageuser.php");
                exit();
            } else {
                $error_message = "Error updating user: " . htmlspecialchars($stmt->error);
            }
        } else {
            // Add new user
            $insert_sql = "INSERT INTO users (full_name, username, password, role, department, email) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssss", $full_name, $username, $hashed_password, $role, $department, $email);

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "User added successfully.";
                header("Location: manageuser.php");
                exit();
            } else {
                $error_message = "Error adding user: " . htmlspecialchars($stmt->error);
            }
        }
        $stmt->close();
    }
}

// Fetch all users
$result = $conn->query("SELECT * FROM users");
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Management Users</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        /* Table Styles */
        .user-table {
            width: 100%;
            border-collapse: collapse;
        }
        .user-table th, .user-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .user-table th {
            background-color: var(--primary-color);
            color: white;
        }
        .user-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .user-table tr:hover {
            background-color: #f1f1f1;
        }
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
        form input, form select {
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
<?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
    <div class="container">
        <?php if (!empty($error_message)): ?>
            <p class="error-message"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <?php if (!empty($success_message)): ?>
            <p class="success-message"><?= htmlspecialchars($success_message) ?></p>
        <?php endif; ?>

        <?php if (isset($_GET['add']) || $edit_mode): ?>
            <h2><?= $edit_mode ? "Edit User" : "Add New User" ?></h2>
            <form method="POST" action="">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" value="<?= $full_name ?>" required>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value="<?= $username ?>" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" value="<?= $password ?>" required>
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
                    <input type="text" id="department" name="department" value="<?= $department ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?= $email ?>" required>
                </div>
                <div class="btn-container">
                    <button type="submit"><?= $edit_mode ? "Update User" : "Add User" ?></button>
                    <a href="manageuser.php"><button type="button">Cancel</button></a>
                </div>
            </form>
        <?php else: ?>
            <h2>Manage Users</h2>
            <table class="user-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['user_id']) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><?= htmlspecialchars($row['username']) ?></td>
                            <td><?= htmlspecialchars($row['role']) ?></td>
                            <td><?= htmlspecialchars($row['department']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td>
                                <a href="?edit=<?= $row['user_id'] ?>" class="action-link" onclick="return confirm('Are you sure you want to edit this user?');">Edit</a> |
                                <a href="?delete=<?= $row['user_id'] ?>" class="action-link" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>