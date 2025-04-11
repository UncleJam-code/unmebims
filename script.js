// JavaScript for frontend validation and handling
document.getElementById('loginForm').addEventListener('submit', function(event) {
    // Simple validation for login
    var username = document.getElementById('username').value;
    var password = document.getElementById('password').value;

    if (username === '' || password === '') {
        event.preventDefault();
        document.getElementById('error-message').textContent = 'Please fill in both fields.';
    }
});

// Example: Handling form submission with AJAX (optional)
document.getElementById('inventoryForm').addEventListener('submit', function(event) {
    event.preventDefault();

    var itemName = document.getElementById('item_name').value;
    var quantity = document.getElementById('quantity').value;
    var location = document.getElementById('location').value;
    var status = document.getElementById('status').value;
    var barcode = document.getElementById('barcode').value;

    // Here, you could use AJAX to send the data to your backend script (addinventory.php)
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'addinventory.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        if (xhr.status === 200) {
            alert('Item Added Successfully');
            // Reload the page to reflect changes
            location.reload();
        } else {
            alert('Error adding item');
        }
    };
    xhr.send(`item_name=${itemName}&quantity=${quantity}&location=${location}&status=${status}&barcode=${barcode}`);
});
