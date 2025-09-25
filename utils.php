<?php

require_once 'lib/jwt/JWT.php';
require_once 'lib/jwt/KEY.php';


use Firebase\JWT\JWT;
use Firebase\JWT\KEY;



$jwt_secret = 'kyanar-project';

function generateToken($user) {
  global $jwt_secret;

  $payload = [
    'username' => $user['username'],
    'role' => $user['role'],
    'exp' => time() + 3600, // 1 jam
  ];

  return JWT::encode($payload, $jwt_secret, 'HS256');
}

function validateToken($token) {
    global $jwt_secret;
  
    try {
      $decoded = JWT::decode($token, new Key($jwt_secret, 'HS256'));
      return (array) $decoded;
    } catch (Exception $e) {
      file_put_contents('jwt_error.log', $e->getMessage());
      return false;
    }
  }