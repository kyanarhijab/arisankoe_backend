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

$created_by = $user['username'];


$method = $_SERVER['REQUEST_METHOD'];




switch ($method) {
  case 'GET':
    $result = $conn->query("SELECT * FROM arisan_groups ORDER BY kode DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
    }
    echo json_encode($rows);
    break;

  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) $data = $_POST;

    $kode             = $conn->real_escape_string($data['kode']);
    $name             = $conn->real_escape_string($data['name']);
    $description      = $conn->real_escape_string($data['description']);
    $total_rounds     = $conn->real_escape_string($data['total_rounds']);
    $amount           = $conn->real_escape_string($data['amount']);
    $start_date       = $conn->real_escape_string($data['start_date']);
    $status           = $conn->real_escape_string($data['status']);
    $status           = $conn->real_escape_string($data['status']);

    $sql = "INSERT INTO arisan_groups (kode,name,description,total_rounds,amount,start_date,status,created_by) 
            VALUES ('$kode', '$name', '$description', '$total_rounds', '$amount', '$start_date', '$status','$created_by')";
    if ($conn->query($sql)) {
      echo json_encode(["success" => true]);
    } else {
      http_response_code(500);
      echo json_encode(["error" => $conn->error ]);
    }
    break;  

  case 'PUT':
    parse_str($_SERVER['QUERY_STRING'], $query);
    $kode = $query['kode'];
    $data = json_decode(file_get_contents("php://input"), true);

    $kode2          = $conn->real_escape_string($data['kode']);
    $name           = $conn->real_escape_string($data['name']);
    $description    = $conn->real_escape_string($data['description']);
    $total_rounds   = $conn->real_escape_string($data['total_rounds']);
    $amount         = $conn->real_escape_string($data['amount']);
    $start_date     = $conn->real_escape_string($data['start_date']);
    $status         = $conn->real_escape_string($data['status']);

    
    $sql = "UPDATE arisan_groups SET 
            kode='$kode2', 
            name='$name', 
            description='$description', 
            total_rounds='$total_rounds',
            amount = '$amount',
            start_date = '$start_date',
            status = '$status' 
            WHERE kode='$kode'";
    

    if ($conn->query($sql)) {
      echo json_encode(["success" => true]);
    } else {
      http_response_code(500);
      echo json_encode(["error" => $conn->error]);
    }
    break;

  case 'DELETE':
    parse_str($_SERVER['QUERY_STRING'], $query);
    $kode = $query['kode'];

    $sql = "DELETE FROM arisan_groups WHERE kode='$kode'";
    if ($conn->query($sql)) {
      echo json_encode(["success" => true]);
    } else {
      http_response_code(500);
      echo $sql;
      echo json_encode(["error" => $conn->error]);
    }
    break;

  default:
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed"]);
    break;
}

$conn->close();