<?php
$new_password = "newpassword"; // Replace with the desired password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
echo $hashed_password; // Use this hash to update the database
?>