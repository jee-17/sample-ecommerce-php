<?php
// Database connection details
ini_set('display_errors', 1);
error_reporting(E_ALL);
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

// Get the product ID from the URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$product_data = null;
$variants_result = null;

// Check if a valid product ID was provided
if ($product_id > 0) {
    // SQL query to fetch a single product's details
    $sql_product = "SELECT id, name, description, image FROM products WHERE id = ?";
    $stmt_product = $conn->prepare($sql_product);
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $product_result = $stmt_product->get_result();
    
    // Fetch the main product data
    if ($product_result->num_rows > 0) {
        $product_data = $product_result->fetch_assoc();
        
        // SQL query to fetch all variants for that product
        // **CORRECTION HERE: changed 'variant_name' to 'weight'**
        $sql_variants = "SELECT weight, price,quantity FROM product_variants WHERE product_id = ? ORDER BY price ASC";
        $stmt_variants = $conn->prepare($sql_variants);
        $stmt_variants->bind_param("i", $product_id);
        $stmt_variants->execute();
        $variants_result = $stmt_variants->get_result();
    }
    $stmt_product->close();
}

// Close the database connection
if (isset($stmt_variants)) {
    $stmt_variants->close();
}
if (isset($conn)) {
    $conn->close();
}
?>