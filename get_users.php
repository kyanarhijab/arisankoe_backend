<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start(); // Mulai output buffering

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Content-Type: application/json");

// Menangani preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  http_response_code(200);
  exit;
}

include 'auth/Connect.php';
require 'utils.php';

file_put_contents('debug.log', print_r(getallheaders(), true));

function getAuthorizationHeader() {
  $headers = null;
  if (isset($_SERVER['Authorization'])) {
      $headers = trim($_SERVER["Authorization"]);
  } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { // Nginx atau FastCGI
      $headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
  } elseif (function_exists('apache_request_headers')) {
      $requestHeaders = apache_request_headers();
      foreach ($requestHeaders as $key => $value) {
          if (strtolower($key) === 'authorization') {
              $headers = trim($value);
              break;
          }
      }
  }
  return $headers;
}

$token = getAuthorizationHeader(); // seperti sebelumnya
$token = str_replace('Bearer ', '', $token);

$user = validateToken($token);

if (!$user) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}


$method = $_SERVER['REQUEST_METHOD'];

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

$sql = "SELECT username, name FROM users";
if ($q != '') {
  $sql .= " WHERE name LIKE '%" . mysqli_real_escape_string($conn, $q) . "%'";
}

$result = mysqli_query($conn, $sql);

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
  $data[] = $row;
}

echo json_encode($data);


$conn->close();