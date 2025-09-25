<?php
// CORS header untuk semua origin
header("Access-Control-Allow-Origin: *");

// Mengizinkan Content-Type dari frontend
header("Access-Control-Allow-Headers: Content-Type");

// Mengizinkan metode yang diperbolehkan
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Return JSON sebagai response
header('Content-Type: application/json');

// Menangani preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit;
}

session_start();

include 'Connect.php';
require '../utils.php';

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data) {
  echo json_encode([
    'error' => 'Data kosong atau tidak valid',
    'rawData' => $rawData
  ]);
  exit;
}

// Validasi input
if (!$data || !isset($data['username']) || !isset($data['password'])) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid input data']);
  exit;
}

$username = $data['username'];
$password = $data['password'];



$sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";
$result = $conn->query($sql);

if ($user = $result->fetch_assoc()) {

    $token = generateToken($user);
    echo json_encode([
    'success' => true,
    'token' => $token,
    'user' => [
      'username' => $user['username'],
      'role' => $user['role'],
    ]
  ]);
} else {
  http_response_code(401);
  echo json_encode(['error' => 'Invalid login']);
}

?>