<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user']) || !isset($_SESSION['company'])) {
    echo json_encode(['success'=>false, 'message'=>'Non autorisé']);
    exit;
}

$company = $_SESSION['company'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['id'])) {
    echo json_encode(['success'=>false, 'message'=>'ID objet manquant']);
    exit;
}

try {
    $dsn = "mysql:host={$company['DBServer']};dbname={$company['DBName']};charset=utf8mb4";
    $pdo = new PDO($dsn, $company['DBUser'], $company['DBPassword'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $stmt = $pdo->prepare("UPDATE Objets SET id_status = 5 WHERE id = :id");
    $stmt->execute(['id' => $input['id']]);

    echo json_encode(['success'=>true]);

} catch (PDOException $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}