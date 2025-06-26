<?php
// Mostrar errores solo para desarrollo (puedes desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// 🔐 DATOS DE TU BASE DE DATOS (RELLENA ESTOS CAMPOS)
$db_host = 'localhost';
$db_name = 'formulario_db';
$db_user = 'root';
$db_pass = '';

$sql = "INSERT INTO guardar_resultados (originurl, resultado, task_id, status) VALUES (:originurl, :resultado, :task_id, :status)";


try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Error de conexión a la base de datos', 'detalle' => $e->getMessage()]);
    exit;
}

// Leer datos del POST
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

// 🔁 MODO 1: GUARDAR RESULTADO
if (isset($input['originurl']) && isset($input['resultado']) && !isset($input['producto'])) {
    try {
        $stmt = $pdo->prepare("INSERT INTO resultados_robot (originurl, resultado, task_id, status) VALUES (:originurl, :resultado, :task_id, :status)");
        $stmt->execute([
            ':originurl' => $input['originurl'],
            ':resultado' => json_encode($input['resultado'], JSON_UNESCAPED_UNICODE),
            ':task_id' => $input['taskId'] ?? null,
            ':status' => $input['status'] ?? null
        ]);

        echo json_encode(['success' => true, 'message' => 'Resultado guardado correctamente en la base de datos.']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Error al guardar en la base de datos.', 'detalle' => $e->getMessage()]);
        exit;
    }
}

// 🔁 MODO 2: EJECUTAR ROBOT
if (!isset($input['producto'])) {
    echo json_encode(['error' => 'No se recibió un objeto "producto" válido.']);
    exit;
}

$producto = $input['producto'];
$originUrl = $producto['originurl'] ?? '';
$gsm_accessories_limit = $producto['gsm_accessories_limit'] ?? '';

if (empty($originUrl)) {
    echo json_encode(['error' => 'El campo originUrl es obligatorio.']);
    exit;
}

if ($gsm_accessories_limit === '' || $gsm_accessories_limit === null) {
    echo json_encode(['error' => 'El campo gsm_accessories_limit es obligatorio.']);
    exit;
}

// 🔑 DATOS DE BROWSE AI
$apiKey = '1b7ace07-4f82-4cd9-8f31-54fc74a3536b:fbc99909-9a94-4532-9f39-55e7d1599e7a';
$robotId = 'a5e30b47-73fa-409b-b095-9401a245590a';
$url = "https://api.browse.ai/v2/robots/$robotId/tasks";

$data = [
    'inputParameters' => [
        'originUrl' => $originUrl,
        'gsm_accessories_limit' => $gsm_accessories_limit
    ]
];

// Petición CURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if (!$response) {
    echo json_encode(['error' => 'Error en cURL: ' . curl_error($ch)]);
    exit;
}

$responseData = json_decode($response, true);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode([
        'success' => true,
        'message' => 'Tarea iniciada con éxito.',
        'taskId' => $responseData['id'] ?? null,
        'status' => $responseData['status'] ?? null,
        'resultado' => $responseData
    ]);
} else {
    echo json_encode([
        'error' => "Error del servidor Browse AI. Código HTTP: $httpCode",
        'message' => $responseData['message'] ?? 'Error desconocido',
        'details' => $responseData
    ]);
}
?>
