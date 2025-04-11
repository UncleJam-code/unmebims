CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('Admin', 'Inventory Manager', 'Staff') NOT NULL
);
CREATE TABLE inventory (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    location VARCHAR(255) NOT NULL,
    status ENUM('Available', 'In Use', 'Expired') NOT NULL,
    barcode VARCHAR(255) NOT NULL
);
CREATE TABLE transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    action ENUM('Added', 'Removed', 'Updated') NOT NULL,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    user_id INT NOT NULL,
    FOREIGN KEY (item_id) REFERENCES inventory(item_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
CREATE TABLE reports (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_type VARCHAR(255) NOT NULL,
    date_generated DATETIME DEFAULT CURRENT_TIMESTAMP,
    data TEXT
);
