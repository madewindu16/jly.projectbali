<?php
header('Content-Type: application/json');

$host = getenv('DB_HOST') ?: 'db';
$name = getenv('DB_NAME') ?: 'jly_db';
$user = getenv('DB_USER') ?: 'jly_user';
$pass = getenv('DB_PASS') ?: 'jly_pass';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name", $user, $pass,
        [PDO::ATTR_TIMEOUT => 3]);
    http_response_code(200);
    echo json_encode([
        "status" => "healthy",
        "db"     => "connected",
        "time"   => date('Y-m-d H:i:s')
    ]);
} catch (Exception $e) {
    http_response_code(503);
    echo json_encode([
        "status" => "unhealthy",
        "db"     => "disconnected"
    ]);
}