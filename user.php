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

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
  case 'GET':
    $result = $conn->query("SELECT * FROM users ORDER BY username DESC");
    $rows = [];
    while ($row = $result->fetch_assoc()) {
      $rows[] = $row;
    }
    echo json_encode($rows);
    break;

  case 'POST':
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) $data = $_POST;

    $username = $conn->real_escape_string($data['username']);
    $password = $conn->real_escape_string($data['password']);
    $name     = $conn->real_escape_string($data['name']);
    $email    = $conn->real_escape_string($data['email']);
    $role     = $conn->real_escape_string($data['role']);

    $sql = "INSERT INTO users (username, password, name, email, role) 
            VALUES ('$username', '$password', '$name', '$email', '$role')";
    if ($conn->query($sql)) {
      echo json_encode(["success" => true]);
    } else {
      http_response_code(500);
      echo json_encode(["error" => $conn->error]);
    }
    break;  

  case 'PUT':
    parse_str($_SERVER['QUERY_STRING'], $query);
    $username = $query['user'];
    $data = json_decode(file_get_contents("php://input"), true);

    $username2 = $conn->real_escape_string($data['username']);
    $name     = $conn->real_escape_string($data['name']);
    $email    = $conn->real_escape_string($data['email']);
    $role     = $conn->real_escape_string($data['role']);
    $password = $conn->real_escape_string($data['password']);

    if (!empty($password)) {
      $sql = "UPDATE users SET username='$username2', password='$password', 
              name='$name', email='$email', role='$role' WHERE username='$username'";
    } else {
      $sql = "UPDATE users SET username='$username2', 
              name='$name', email='$email', role='$role' WHERE username='$username'";
    }

    if ($conn->query($sql)) {
      echo json_encode(["success" => true]);
    } else {
      http_response_code(500);
      echo json_encode(["error" => $conn->error]);
    }
    break;

  case 'DELETE':
    parse_str($_SERVER['QUERY_STRING'], $query);
    $username = $query['user'];

    $sql = "DELETE FROM users WHERE username='$username'";
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