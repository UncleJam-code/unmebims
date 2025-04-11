<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli('localhost', 'root', '', 'unmebims');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
$error = '';
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

if (isset($_POST['add_user'])) {
    // Input sanitization
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $email = $_POST['role'];
    $email = $_POST['department'];
    $email = $_POST['email'];
   
    $sql = "INSERT INTO users (username, password, role, department, email) VALUES (?, ?, ?, ?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $username, $password, $role, $department, $email);

    if ($stmt->execute()) {
        echo "User added successfully.";
    } else {
        echo "Error: " . $stmt->error;
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
    <title>Add Inventory Item</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            padding: 2rem;
        }

        h2 {
            color: var(--primary-color);
            font-size: 1.8rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        /* Form Styling */
        .add-form {
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        label {
            color: var(--secondary-color);
            font-weight: 500;
        }

        input, select {
            padding: 0.8rem;
            border: 2px solid #eee;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }

        input:focus, select:focus {
            outline: none;
            border-color: var(--accent-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }

        .submit-btn {
            grid-column: 1 / -1;
            padding: 1rem;
            background: var(--success-color);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .submit-btn:hover {
            background: #219a52;
        }

        .error-message {
            color: var(--error-color);
            text-align: center;
            margin-bottom: 1rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .sidebar {
                width: 200px;
            }

            .container {
                padding: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .sidebar {
                width: 150px;
            }

            .add-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <nav class="sidebar">
        <a href="#" class="logo">UNMEB</a>
        <a href="statusChart.html">Home</a>
        <a href="report.php">Reports</a>
        <a href="inventory.php">Inventory</a>
        <a href="addinventory.php">Add New Item</a>
        <a href="settings.php">Settings</a>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2>Add New Inventory Item</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" class="add-form">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" required autofocus>
                </div>

                <div class="form-group">
                    <label>Quantity</label>
                    <input type="number" name="quantity" min="0" required>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <input type="text" name="category" required>
                </div>

                <div class="form-group">
                    <label>Location</label>
                    <input type="text" name="location" required>
                </div>

                <div class="form-group">
                    <label>Status</label>
                    <select name="status" required>
                        <option value="Available">Available</option>
                        <option value="In use">In use</option>
                        <option value="Expired">Expired</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Barcode</label>
                    <input type="text" name="barcode" required>
                </div>

                <button type="submit" name="add_item" class="submit-btn">Add Item</button>
            </form>
        </div>
    </div>

</body>
</html>
