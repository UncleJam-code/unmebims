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
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$last_updated = isset($_GET['last_updated']) ? $_GET['last_updated'] : '';
$inventory_data = [];

// Handle report generation
if ($report_type && $start_date && $end_date) {
    // Validate and sanitize input
    $start_date = $conn->real_escape_string($start_date);
    $end_date = $conn->real_escape_string($end_date);
    $last_updated = $conn->real_escape_string($last_updated);

    // Fetch data based on report type
    switch ($report_type) {
        case 'requested':
            $query = "
                SELECT 
                    r.request_id, 
                    u.full_name, 
                    i.item_name, 
                    r.quantity, 
                    r.purpose, 
                    r.status, 
                    r.created_at 
                FROM 
                    requests r
                JOIN 
                    users u ON r.user_id = u.user_id
                JOIN 
                    inventory i ON r.item_id = i.item_id
                WHERE 
                    r.created_at BETWEEN '$start_date' AND '$end_date'
            ";
            break;

        case 'added':
            $query = "
                SELECT 
                    item_id, 
                    item_name, 
                    quantity, 
                    category, 
                    location, 
                    created_at 
                FROM 
                    inventory 
                WHERE 
                    created_at BETWEEN '$start_date' AND '$end_date'
            ";
            break;

        case 'deleted':
            $query = "
                SELECT 
                    log_id AS id, 
                    action, 
                    details, 
                    timestamp 
                FROM 
                    audit_logs 
                WHERE 
                    action = 'Deleted Item' 
                    AND timestamp BETWEEN '$start_date' AND '$end_date'
            ";
            break;

        case 'inventory_levels':
            $query = "
                SELECT 
                    item_name, 
                    quantity, 
                    category, 
                    location, 
                    status, 
                    last_updated 
                FROM 
                    inventory
            ";
            break;

        default:
            $query = "";
            break;
    }

    if ($query) {
        $result = $conn->query($query);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $inventory_data[] = $row;
            }
        } else {
            $inventory_data = [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Reports</title>
    <!-- External CSS and Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!--  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script> -->
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
        }
        h2 {
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }
        form {
            margin-bottom: 2rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        input[type="date"], select {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
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
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 0.8rem;
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
        .export-buttons {
            margin-top: 1rem;
        }
        .export-buttons button {
            margin-right: 0.5rem;
        }

    </style>
</head>
<body>
<?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h2>Generate Report</h2>
            <form method="GET" action="">
            <label for="report_type">Report Type:</label>
            <select id="report_type" name="report_type" required>
                <option value="">-- Select Report Type --</option>
                <option value="requested" <?= $report_type === 'requested' ? 'selected' : '' ?>>Requested Items</option>
                <option value="added" <?= $report_type === 'added' ? 'selected' : '' ?>>Added Items</option>
                <option value="deleted" <?= $report_type === 'deleted' ? 'selected' : '' ?>>Deleted Items</option>
                <option value="inventory_levels" <?= $report_type === 'inventory_levels' ? 'selected' : '' ?>>Inventory Levels</option>
            </select>

                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" required>

                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" required>

                <button type="submit">Generate Report</button>
            </form>

            <?php if ($report_type): ?>
                <h3><?= ucfirst($report_type) ?> Report</h3>
                <?php if (empty($inventory_data)): ?>
                    <p>No data found for the selected date range.</p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <?php
                                // Dynamically generate table headers based on report type
                                if ($report_type === 'requested') {
                                    echo "<th>ID</th><th>User</th><th>Item Name</th><th>Quantity</th><th>Purpose</th><th>Status</th><th>Date Requested</th>";
                                } elseif ($report_type === 'added') {
                                    echo "<th>ID</th><th>Item Name</th><th>Quantity</th><th>Category</th><th>Location</th><th>Date Added</th>";
                                } elseif ($report_type === 'deleted') {
                                    echo "<th>ID</th><th>Action</th><th>Details</th><th>Date Deleted</th>";
                                } elseif ($report_type === 'inventory_levels') {
                                    echo "<th>Item Name</th><th>Quantity</th><th>Category</th><th>Location</th><th>Status</th><th>Last Updated</th>";
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($inventory_data as $item): ?>
                            <tr>
                                <?php
                                // Dynamically generate table rows based on report type
                                if ($report_type === 'requested') {
                                    echo "<td>" . htmlspecialchars($item['request_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['full_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['item_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['purpose']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['status']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['created_at']) . "</td>";
                                } elseif ($report_type === 'added') {
                                    echo "<td>" . htmlspecialchars($item['item_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['item_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['category']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['location']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['created_at']) . "</td>";
                                } elseif ($report_type === 'deleted') {
                                    echo "<td>" . htmlspecialchars($item['log_id']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['action']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['details']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['timestamp']) . "</td>";
                                } elseif ($report_type === 'inventory_levels') {
                                    echo "<td>" . htmlspecialchars($item['item_name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['quantity']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['category']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['location']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['status']) . "</td>";
                                    echo "<td>" . htmlspecialchars($item['last_updated']) . "</td>";
                                }
                                ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Export Buttons -->
                    <div class="export-buttons">
                        <button onclick="exportToExcel()">Export to Excel</button>
                        <button onclick="exportToPDF()">Export to PDF</button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- JavaScript for Exporting -->
    <script>
        // Export to Excel
        function exportToExcel() {
            const table = document.querySelector('table');
            const rows = table.querySelectorAll('tr');
            let csvContent = "data:text/csv;charset=utf-8,";

            rows.forEach(row => {
                const rowData = [];
                row.querySelectorAll('th, td').forEach(cell => {
                    rowData.push(cell.innerText);
                });
                csvContent += rowData.join(',') + "\r\n";
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "inventory_report.csv");
            document.body.appendChild(link);
            link.click();
        }

        // Export to PDF
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            // Add table headers
            const headers = Array.from(document.querySelectorAll('th')).map(th => th.innerText);
            const data = Array.from(document.querySelectorAll('tbody tr')).map(row => {
                return Array.from(row.querySelectorAll('td')).map(td => td.innerText);
            });

            // AutoTable plugin for jsPDF
            doc.autoTable({
                head: [headers],
                body: data,
            });

            // Save the PDF
            doc.save('inventory_report.pdf');
        }
    </script>

    <!-- Include jsPDF Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.26/jspdf.plugin.autotable.min.js"></script>
</body>
</html>