<?php

// CORS header untuk semua origin
header("Access-Control-Allow-Origin: *");

// Mengizinkan Content-Type dari frontend
header("Access-Control-Allow-Headers: Content-Type");

// Mengizinkan metode yang diperbolehkan
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Menangani preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit;
}

session_start();

include 'Connect.php';

$result = $conn->query("SELECT id, name, price FROM products");
$products = [];

while ($row = $result->fetch_assoc()) {
  $products[] = $row;
}

echo json_encode($products);
?>