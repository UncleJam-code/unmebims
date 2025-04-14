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
if (isset($_POST['add_item'])) {
    // Input sanitization
    $item_name = $conn->real_escape_string(trim($_POST['item_name']));
    $quantity = intval($_POST['quantity']);
    $category = $conn->real_escape_string(trim($_POST['category']));
    $location = $conn->real_escape_string(trim($_POST['location']));
    $status = in_array($_POST['status'], ['Available', 'In use', 'Expired']) 
                ? $_POST['status'] 
                : 'Available';
    $barcode = $conn->real_escape_string(trim($_POST['barcode']));
    // Validation
    if (!empty($item_name) && $quantity >= 0) {
        $stmt = $conn->prepare("INSERT INTO inventory (item_name, quantity, category, location, status, barcode) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sissis", $item_name, $quantity, $category, $location, $status, $barcode);
        
        if ($stmt->execute()) {
            header("Location: inventory.php");
            exit();
        } else {
            $error = "Error adding item: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error = "Invalid input data";
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
                <div style="display: flex; align-items: center;">
                    <input 
                        type="text" 
                        id="barcode" 
                        name="barcode" 
                        placeholder="Scan barcode or enter manually" 
                        required 
                        readonly 
                        style="flex-grow: 1; margin-right: 10px;" 
                    >
                    <button 
                        type="button" 
                        id="scan-barcode-btn" 
                        style="padding: 0.5rem 1rem; background: #3498db; color: white; border: none; border-radius: 4px; cursor: pointer;"
                    >
                        Scan Barcode
                    </button>
                </div>
            </div>

                <button type="submit" name="add_item" class="submit-btn">Add Item</button>
            </form>
        </div>
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
<script>
        // Initialize QuaggaJS
        Quagga.init({
            inputStream: {
                name: "Live",
                type: "LiveStream",
                target: document.querySelector("#scanner-container"), // Use the container for the camera feed
                constraints: {
                    width: 300,
                    height: 250,
                    facingMode: "environment" // Use the rear camera
                }
            },
            decoder: {
                readers: ["code_128_reader", "ean_reader", "upc_reader"] // Supported barcode formats
            }
        }, function(err) {
            if (err) {
                console.error("Error initializing Quagga:", err);
                return;
            }
            console.log("Quagga initialized successfully.");
            Quagga.start(); // Start the camera stream
        });

        // Listen for detected barcodes
        Quagga.onDetected(function(result) {
            const barcode = result.codeResult.code; // Extract the barcode value
            document.getElementById("result").innerText = `Barcode Detected: ${barcode}`;
            Quagga.stop(); // Stop scanning after detecting a barcode

            // Optionally send the barcode to the backend
            fetch('process_barcode.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ barcode: barcode })
            })
            .then(response => response.json())
            .then(data => {
                console.log("Backend response:", data);
            })
            .catch(error => {
                console.error("Error sending barcode to backend:", error);
            });
        });
    </script>
                     Include QuaggaJS 
                <script src="https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js"></script>

                <script>
                    // Function to initialize barcode scanner
                    function startBarcodeScanner() {
                        // Create a container for the camera feed
                        const scannerContainer = document.createElement('div');
                        scannerContainer.style.position = 'fixed';
                        scannerContainer.style.top = '0';
                        scannerContainer.style.left = '0';
                        scannerContainer.style.width = '100%';
                        scannerContainer.style.height = '100%';
                        scannerContainer.style.backgroundColor = 'rgba(0, 0, 0, 0.8)';
                        scannerContainer.style.zIndex = '1000';
                        scannerContainer.style.display = 'flex';
                        scannerContainer.style.justifyContent = 'center';
                        scannerContainer.style.alignItems = 'center';

                        // Add the camera feed element
                        const cameraFeed = document.createElement('div');
                        cameraFeed.style.width = '300px';
                        cameraFeed.style.height = '250px';
                        cameraFeed.style.border = '2px solid white';
                        cameraFeed.style.overflow = 'hidden';
                        scannerContainer.appendChild(cameraFeed);

                        // Add a close button
                        const closeButton = document.createElement('button');
                        closeButton.innerText = 'Close Scanner';
                        closeButton.style.position = 'absolute';
                        closeButton.style.top = '20px';
                        closeButton.style.right = '20px';
                        closeButton.style.padding = '0.5rem 1rem';
                        closeButton.style.background = '#e74c3c';
                        closeButton.style.color = 'white';
                        closeButton.style.border = 'none';
                        closeButton.style.borderRadius = '4px';
                        closeButton.style.cursor = 'pointer';
                        closeButton.onclick = () => {
                            Quagga.stop();
                            document.body.removeChild(scannerContainer);
                        };
                        scannerContainer.appendChild(closeButton);

                        // Append the scanner container to the body
                        document.body.appendChild(scannerContainer);

                        // Initialize QuaggaJS
                        Quagga.init({
                            inputStream: {
                                name: "Live",
                                type: "LiveStream",
                                target: cameraFeed,
                                constraints: {
                                    width: 300,
                                    height: 250,
                                    facingMode: "environment" // Use the rear camera
                                }
                            },
                            decoder: {
                                readers: ["code_128_reader", "ean_reader", "upc_reader"] // Supported barcode formats
                            }
                        }, function(err) {
                            if (err) {
                                console.error("Error initializing Quagga:", err);
                                alert("Failed to initialize barcode scanner. Please try again.");
                                document.body.removeChild(scannerContainer);
                                return;
                            }
                            console.log("Quagga initialized successfully.");
                            Quagga.start(); // Start the camera stream
                        });

                        // Listen for detected barcodes
                        Quagga.onDetected(function(result) {
                            const barcode = result.codeResult.code; // Extract the barcode value
                            document.getElementById("barcode").value = barcode; // Populate the input field
                            Quagga.stop(); // Stop scanning after detecting a barcode
                            document.body.removeChild(scannerContainer); // Remove the scanner container
                        });
                    }

                    // Attach the scanner function to the button
                    document.getElementById("scan-barcode-btn").addEventListener("click", startBarcodeScanner);
                </script>
</body>
</html>

<?php
$conn->close();
?>
