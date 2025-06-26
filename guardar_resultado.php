<?php
// Mostrar errores solo para desarrollo
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

// 🔐 DATOS DE TU BASE DE DATOS
$db_host = 'localhost';
$db_name = 'formulario_db';
$db_user = 'root';
$db_pass = '';


$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input['originurl']) || !isset($input['resultado'])) {
    echo json_encode(['error' => 'Datos insuficientes.']);
    exit;
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("INSERT INTO resultados_robot (originurl, resultado, task_id, status) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $input['originurl'],
        json_encode($input['resultado'], JSON_UNESCAPED_UNICODE),
        $input['taskId'] ?? null,
        $input['status'] ?? null
    ]);

    echo json_encode(['success' => true, 'message' => 'Resultado guardado.']);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error BDD', 'detalle' => $e->getMessage()]);
}
