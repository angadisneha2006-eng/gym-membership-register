<?php
// db.php - Database connection
$conn = new mysqli("localhost", "root", "", "gym_management_db");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Database Connection Failed"]));
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

echo json_encode(["status" => "success", "message" => "Database Connected Successfully!"]);
?>