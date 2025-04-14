<?php
// sidebar.php
?>
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
                </ul>
            </li>
            <!-- Requests Dropdown -->
            <li class="dropdown">
                <a href="#" class="dropdown-toggle"><i class="fas fa-file-alt"></i> Requests <i class="fas fa-caret-down"></i></a>
                <ul class="dropdown-menu">
                    <li><a href="make_request.php">Request Items</a></li>
                    <li><a href="approve.php">Approve Requests</a></li>
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

<script>
    // JavaScript for dropdown toggle functionality
    document.querySelectorAll('.dropdown-toggle').forEach(item => {
        item.addEventListener('click', event => {
            event.preventDefault(); // Prevent default link behavior
            const parent = item.parentElement; // Get the parent dropdown element
            parent.classList.toggle('active'); // Toggle the 'active' class
        });
    });
</script>
