<?php
require_once '../../config/database.php';

$conn = getDBConnection();

// Obtener ID del uniforme (individual)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // Obtener información del uniforme
    $stmt = $conn->prepare("SELECT estado FROM uniformes WHERE id = ?");
    $stmt->execute([$id]);
    $uniforme = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$uniforme) {
        header('Location: index.php?error=' . urlencode('Uniforme no encontrado.'));
        exit;
    }
    
    // Verificar si el uniforme está en préstamos activos (estado 'Prestado' o pendiente de devolución)
    if ($uniforme['estado'] == 'Prestado') {
        header('Location: index.php?error=' . urlencode('No se puede eliminar este uniforme porque está actualmente prestado.'));
        exit;
    }

    // También verificar si hay registros en prestamo_items donde este uniforme fue prestado y no devuelto
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM prestamo_items pi
        JOIN prestamos p ON pi.prestamo_id = p.id
        WHERE pi.tipo_item = 'uniforme' AND pi.item_id = ? AND p.estado = 'Activo' AND pi.cantidad_devuelta < pi.cantidad_prestada
    ");
    $stmt->execute([$id]);
    $prestamos_pendientes = $stmt->fetchColumn();

    if ($prestamos_pendientes > 0) {
        header('Location: index.php?error=' . urlencode('No se puede eliminar este uniforme porque tiene registros de préstamos pendientes de devolución.'));
        exit;
    }
    
    // Eliminar uniforme
    $stmt = $conn->prepare("DELETE FROM uniformes WHERE id = ?");
    $stmt->execute([$id]);
    
    header('Location: index.php?success=' . urlencode('Uniforme eliminado exitosamente.'));
    
} catch(PDOException $e) {
    header('Location: index.php?error=' . urlencode('Error al eliminar el uniforme: ' . $e->getMessage()));
}
?>
