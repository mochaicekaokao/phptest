<?php
$host="localhost";
$user="root";
$password="";
$db="ecommerce";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $password);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Create a mysqli connection as well since your login uses mysqli
$data = mysqli_connect($host, $user, $password, $db);
if($data === false) {
    die("connection error");
}
?> 