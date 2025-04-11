<?php
$transaction_query = "INSERT INTO transactions (item_id, action, user_id)
                      VALUES ('$item_id', '$action', '$user_id')";
if($conn->query($transaction_query) === TRUE) {
    echo "Transaction logged successfully";
} else {
    echo "Error: " . $conn->error;
}
?>
