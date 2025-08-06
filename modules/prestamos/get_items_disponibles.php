<?php
require_once '../../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

$conn = getDBConnection();
$tipo = $_GET['tipo'] ?? '';

if (!$tipo) {
    echo json_encode(['error' => 'Tipo de item no especificado']);
    exit;
}

try {
    if ($tipo == 'instrumento') {
        $stmt = $conn->query("SELECT id, nombre, codigo_serie FROM instrumentos WHERE estado = 'Disponible' ORDER BY nombre, codigo_serie");
    } elseif ($tipo == 'uniforme') {
        // Para uniformes, devolver el ID del item individual, tipo, talla y código de serie
        $stmt = $conn->query("SELECT id, tipo, talla, codigo_serie FROM uniformes WHERE estado = 'Disponible' ORDER BY tipo, talla, codigo_serie");
    } elseif ($tipo == 'accesorio') { // Nuevo: para accesorios
        $stmt = $conn->query("SELECT id, nombre, codigo_serie FROM accesorios WHERE estado = 'Disponible' ORDER BY nombre, codigo_serie");
    } else {
        echo json_encode(['error' => 'Tipo de item no válido']);
        exit;
    }
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);
    
} catch(PDOException $e) {
    echo json_encode(['error' => 'Error de base de datos: ' . $e->getMessage()]);
}
?>
