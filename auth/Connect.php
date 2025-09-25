<?php
$host = 'localhost';
$db = 'db_arisan';
$user = 'root';
$pass = '';
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die(json_encode(['error' => 'Database connection failed']));
}
?>