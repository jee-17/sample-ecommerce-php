<?php
session_start();
$source = $_SESSION['checkout_source'] ?? 'cart';
echo json_encode(["source" => $source]);
