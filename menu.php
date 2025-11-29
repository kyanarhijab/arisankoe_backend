<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=utf-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    echo json_encode(["message" => "CORS preflight OK"]);
    exit();
}

// ============================
// Lanjut script lain setelah ini
// ============================
include 'auth/Connect.php';
require 'utils.php';

// (opsional tapi aman)
ob_clean(); // bersihkan buffer lama jika ada

// Debug: cek apakah header Authorization terkirim
file_put_contents('debug.log', print_r(getallheaders(), true));

function getAuthorizationHeader() {
  $headers = null;
  if (isset($_SERVER['Authorization'])) {
    $headers = trim($_SERVER["Authorization"]);
  } else if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
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

$token = getAuthorizationHeader();
$token = str_replace('Bearer ', '', $token);

$user = validateToken($token);

if (!$user) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit();
}

// --- Query Menu ---
$sql = "
  SELECT m.*
  FROM menus m
  JOIN menu_permissions p ON m.kode_menu = p.kode_menu
  WHERE p.role = ?
  ORDER BY m.parent_id, m.order_no, m.kode_menu
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(['error' => 'Database prepare failed', 'detail' => $conn->error]);
  exit();
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

echo json_encode(buildTree($menus), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);