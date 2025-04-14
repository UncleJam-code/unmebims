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

// Handle item deletion
if (isset($_GET['delete']) && filter_var($_GET['delete'], FILTER_VALIDATE_INT)) {
    $item_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    
    logAction($conn, "Deleted Item", "Item ID: 123");
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Item deleted successfully.";
        header("Location: inventory.php");
        exit();
    } else {
        echo "<p style='color: red;'>Error deleting item: " . htmlspecialchars($stmt->error) . "</p>";
    }
    $stmt->close();
}

// Handle editing or adding items
$edit_mode = false;
$item_id = null;
$item_name = $quantity = $category = $location = $status = $barcode = "";
if (isset($_GET['edit']) && filter_var($_GET['edit'], FILTER_VALIDATE_INT)) {
    $item_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM inventory WHERE item_id = ?");
    $stmt->bind_param("i", $item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        die("Item not found.");
    }
    $item = $result->fetch_assoc();
    $item_name = htmlspecialchars($item['item_name']);
    $quantity = htmlspecialchars($item['quantity']);
    $category = htmlspecialchars($item['category']);
    $location = htmlspecialchars($item['location']);
    $status = htmlspecialchars($item['status']);
    $barcode = htmlspecialchars($item['barcode']);
    $edit_mode = true;
    $stmt->close();
}

// Handle form submission for adding/editing items
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : null;
    $item_name = trim($_POST['item_name']);
    $quantity = filter_var($_POST['quantity'], FILTER_VALIDATE_INT);
    $category = trim($_POST['category']);
    $location = trim($_POST['location']);
    $status = trim($_POST['status']);
    $barcode = trim($_POST['barcode']);
    // Validate inputs
    if (empty($item_name) || empty($category) || empty($location) || empty($barcode)) {
        echo "<p style='color: red;'>All fields are required.</p>";
    } elseif (!$quantity || $quantity < 0) {
        echo "<p style='color: red;'>Quantity must be a positive integer.</p>";
    } else {
        if ($edit_mode) {
            // Update existing item
            $update_sql = "UPDATE inventory SET item_name=?, quantity=?, category=?, location=?, status=?, barcode=? WHERE item_id=?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sissssi", $item_name, $quantity, $category, $location, $status, $barcode, $item_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Item updated successfully.";
                header("Location: inventory.php");
                exit();
            } else {
                echo "<p style='color: red;'>Error updating item: " . htmlspecialchars($stmt->error) . "</p>";
            }
        } else {
            // Add new item
            $insert_sql = "INSERT INTO inventory (item_name, quantity, category, location, status, barcode) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sissss", $item_name, $quantity, $category, $location, $status, $barcode);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Item added successfully.";
                header("Location: inventory.php");
                exit();
            } else {
                echo "<p style='color: red;'>Error adding item: " . htmlspecialchars($stmt->error) . "</p>";
            }
        }
        $stmt->close();
    }
}

// Fetch all inventory items with optional search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "SELECT * FROM inventory";
if (!empty($search)) {
    $query .= " WHERE item_name LIKE ? OR category LIKE ? OR location LIKE ?";
    $stmt = $conn->prepare($query);
    $searchTerm = '%' . $search . '%';
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($query);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management</title>
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
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
        }
        .inventory-table th, .inventory-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .inventory-table th {
            background-color: var(--primary-color);
            color: white;
        }
        .inventory-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .inventory-table tr:hover {
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
       /* Search Bar Styles */
.search-bar {
    margin-bottom: 1.5rem;
    text-align: center; /* Center align the search bar */
}

.search-container {
    display: flex;
    /*align-items: center;*/
    max-width: 800px; /* Limit the width of the search bar */
    margin: 0 auto; /* Center the search bar on the page */
    position: relative;
    border: 1px solid var(--accent-color); /* Add a border for contrast */
    border-radius: 25px; /* Rounded corners for a modern look */
    overflow: hidden; /* Ensure the border radius works properly */
    background: white; /* Background color for the input field */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
}

.search-input {
    flex-grow: 1; /* Allow the input to expand and fill available space */
    padding: 0.8rem 1rem; /* Add padding for better spacing */
    border: none; /* Remove default borders */
    outline: none; /* Remove focus outline */
    font-size: 1rem; /* Increase font size for readability */
    background: transparent; /* Make the input background transparent */
    color: var(--primary-color); /* Text color */
}

.search-input::placeholder {
    color: var(--neutral-color); /* Placeholder text color */
    opacity: 0.7; /* Slightly fade the placeholder */
}

.search-button {
    padding: 0.8rem 1rem; /* Add padding for the button */
    background: var(--accent-color); /* Button background color */
    color: white; /* Button text color */
    border: none; /* Remove default borders */
    cursor: pointer; /* Pointer cursor for interactivity */
    font-size: 1rem; /* Match the input font size */
    transition: background 0.3s ease; /* Smooth hover effect */
}

.search-button:hover {
    background: var(--primary-color); /* Darker color on hover */
}

.search-button i {
    font-size: 1rem; /* Adjust the size of the search icon */
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
            <?php if (isset($_GET['add']) || $edit_mode): ?>
                <h2><?= $edit_mode ? "Edit Inventory Item" : "Add New Inventory Item" ?></h2>
                <form method="POST" action="">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="item_id" value="<?= $item_id ?>">
                    <?php endif; ?>
                    <label for="item_name">Item Name:</label>
                    <input type="text" id="item_name" name="item_name" value="<?= $item_name ?>" required>
                    <label for="quantity">Quantity:</label>
                    <input type="number" id="quantity" name="quantity" value="<?= $quantity ?>" min="0" required>
                    <label for="category">Category:</label>
                    <input type="text" id="category" name="category" value="<?= $category ?>" required>
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" value="<?= $location ?>" required>
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Available" <?= $status == 'Available' ? 'selected' : '' ?>>Available</option>
                        <option value="In use" <?= $status == 'In use' ? 'selected' : '' ?>>In use</option>
                        <option value="Out of Stock" <?= $status == 'Out of Stock' ? 'selected' : '' ?>>Out of Stock</option>
                        
                    </select>
                    <label for="barcode">Barcode:</label>
                    <input type="text" id="barcode" name="barcode" value="<?= $barcode ?>" required>
                    <div class="btn-container">
                        <button type="submit"><?= $edit_mode ? "Update Item" : "Add Item" ?></button>
                        <a href="inventory.php"><button type="button">Cancel</button></a>
                    </div>
                </form>
            <?php else: ?>
                <h2>Inventory Management</h2>
                
                <!-- Search Bar -->
                    <div class="search-bar">
                        <form method="GET" action="">
                            <div class="search-container">
                                <input 
                                    type="text" 
                                    name="search" 
                                    placeholder="Search by item name, category, or location..." 
                                    value="<?= htmlspecialchars($search) ?>" 
                                    class="search-input"
                                >
                                <button type="submit" class="search-button">
                                    <i class="fas fa-search"></i> <!-- Font Awesome search icon -->
                                </button>
                            </div>
                        </form>
                    </div>
                
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Item Name</th>
                            <th>Quantity</th>
                            <th>Category</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['item_id']) ?></td>
                            <td><?= htmlspecialchars($row['item_name']) ?></td>
                            <td><?= htmlspecialchars($row['quantity']) ?></td>
                            <td><?= htmlspecialchars($row['category']) ?></td>
                            <td><?= htmlspecialchars($row['location']) ?></td>
                            <td><?= htmlspecialchars($row['status']) ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>