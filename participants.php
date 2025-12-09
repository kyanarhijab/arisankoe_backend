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
    $kode = isset($_GET['kode']) ? $conn->real_escape_string($_GET['kode']) : '';

    if ($kode !== '') {
        // Filter berdasarkan kode
        
        $sql =" SELECT a.id, a.user_id AS username, u.name AS nama_peserta, g.name AS nama_group , a.join_date AS join_date
                  FROM participants a
                JOIN users u ON u.username = a.user_id
                JOIN arisan_groups g ON g.kode = a.group_id
                and g.kode = '$kode' ORDER BY id DESC  ";
    } else {
        // Default: kosongkan hasil
        echo json_encode([]);
        exit;
    }

    $result = $conn->query($sql);

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }

    echo json_encode($rows);
    break;

  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) $data = $_POST;

    $id             = $conn->real_escape_string($data['id']);
    $user_id        = $conn->real_escape_string($data['user_id']);
    $group_id       = $conn->real_escape_string($data['group_id']);
    $join_date      = $conn->real_escape_string($data['join_date']);
    $status         = $conn->real_escape_string($data['status']);

    $sql = "INSERT INTO participants (id,user_id,group_id,join_date,status) 
            VALUES ('$id', '$user_id', '$group_id', '$join_date', '$status')";
    if ($conn->query($sql)) {
      echo json_encode(["success" => true]);
    } else {
      http_response_code(500);
      echo json_encode(["error" => $conn->error ]);
    }
    break;  

  case 'PUT':
    parse_str($_SERVER['QUERY_STRING'], $query);
    $id = $query['id'];
    $data = json_decode(file_get_contents("php://input"), true);

    $id2          = $conn->real_escape_string($data['id']);
    $user_id      = $conn->real_escape_string($data['user_id']);
    $group_id     = $conn->real_escape_string($data['group_id']);
    $join_date    = $conn->real_escape_string($data['join_date']);
    $status    = $conn->real_escape_string($data['status']);

    $sql = "UPDATE participants SET  
            user_id='$user_id', 
            group_id='$group_id', 
            join_date='$join_date',
            status = '$status',
            start_date = '$start_date',
            status = '$status' 
            WHERE id='$id'";
    

    if ($conn->query($sql)) {
      echo json_encode(["success" => true]);
    } else {
      http_response_code(500);
      echo json_encode(["error" => $conn->error]);
    }
    break;

  case 'DELETE':
    parse_str($_SERVER['QUERY_STRING'], $query);
    $id = $query['id'];

    $sql = "DELETE FROM participants WHERE id='$id'";
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