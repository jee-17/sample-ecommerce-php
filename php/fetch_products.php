<?php
// Database connection details
$db_host = 'localhost';
$db_user = 'root'; // <-- REPLACE WITH YOUR DATABASE USERNAME
$db_pass = '';     // <-- REPLACE WITH YOUR DATABASE PASSWORD
$db_name = 'kcpl'; // <-- REPLACE WITH YOUR DATABASE NAME

// Establish the database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL query to fetch products and their minimum prices
$sql = "SELECT p.id, p.name, p.description, p.image, MIN(pv.price) as price
        FROM products p
        JOIN product_variants pv ON p.id = pv.product_id
        GROUP BY p.id
        ORDER BY p.id ASC";

$result = $conn->query($sql);
?>