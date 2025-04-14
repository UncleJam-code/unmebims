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

// Fetch all available inventory items for the dropdown
$sql = "SELECT * FROM inventory WHERE status = 'Available'";
$result = $conn->query($sql);

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = intval($_POST['item_id']);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $purpose = trim($_POST['purpose']);
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    if (!$quantity || $quantity <= 0) {
        echo "<p style='color: red;'>Invalid quantity.</p>";
    } elseif (empty($purpose)) {
        echo "<p style='color: red;'>Purpose is required.</p>";
    } else {
        // Insert the request into the database
        $insert_sql = "INSERT INTO requests (item_id, user_id, quantity, purpose, status, created_at) 
                       VALUES (?, ?, ?, ?, 'Pending', NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("iiis", $item_id, $user_id, $quantity, $purpose);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Request submitted successfully.";
            header("Location: request.php");
            exit();
        } else {
            echo "<p style='color: red;'>Error submitting request: " . htmlspecialchars($stmt->error) . "</p>";
        }
        $stmt->close();
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make Request</title>
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
        .main-content { margin-left: 250px; padding: 2rem; }
        /* Container */
        .container {
            max-width: 500px;
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
            <h2>Make Request</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="item_id">Select Item:</label>
                    <select id="item_id" name="item_id" required>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <option value="<?= $row['item_id'] ?>"><?= htmlspecialchars($row['item_name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" min="1" required>
                </div>
                <div class="form-group">
                    <label for="purpose">Purpose:</label>
                    <textarea id="purpose" name="purpose" rows="5" placeholder="Describe why you need this item" required></textarea>
                </div>
                <button type="submit">Submit Request</button>
            </form>
        </div>
    </div>
</body>
</html>