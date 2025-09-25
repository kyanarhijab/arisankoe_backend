<?php

ob_start(); // Mulai output buffering

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
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

$sql = "
        SELECT m.*
        FROM menus m
        JOIN menu_permissions p ON m.kode_menu = p.kode_menu
        WHERE p.role = ?
        ORDER BY m.parent_id, m.order_no , m.kode_menu
      ";
$stmt = $conn->prepare($sql);

if (!$stmt) {
  die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("s", $user['role']);
$stmt->execute();
$res = $stmt->get_result();

$menus = [];
while ($row = $res->fetch_assoc()) {
  $row['disabled'] = false; 
  $menus[] = $row;
}

function buildTree($items, $parentId = null) {
  $tree = [];
  foreach ($items as $item) {
    if ($item['parent_id'] == $parentId) {
      $children = buildTree($items, $item['kode_menu']);
      if ($children) $item['children'] = $children;
      $tree[] = $item;
    }
  }
  return $tree;
}

if (empty($menus)) {
  echo json_encode(['message' => 'No menus found for this role.']);
  exit;
}


$content = ob_get_clean(); // hapus output
if (trim($content) !== '') {
  file_put_contents('unexpected_output.log', $content);
}


file_put_contents('menu-debug.log', json_encode($menus));
echo json_encode(buildTree($menus), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
//exit;

//echo json_encode($menus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);