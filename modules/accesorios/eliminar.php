<?php
require_once '../../config/database.php';

$conn = getDBConnection();

// Obtener ID del accesorio
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // Verificar si el accesorio está en préstamos activos (unidades pendientes de devolución)
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM prestamo_items pi
        JOIN prestamos p ON pi.prestamo_id = p.id
        WHERE pi.tipo_item = 'accesorio' AND pi.item_id = ? AND p.estado = 'Activo' AND pi.cantidad_devuelta < pi.cantidad_prestada
    ");
    $stmt->execute([$id]);
    $prestamos_activos = $stmt->fetchColumn();

    if ($prestamos_activos > 0) {
        header('Location: index.php?error=' . urlencode('El accesorio no puede ser eliminado porque está en préstamos activos o tiene unidades pendientes de devolución.'));
        exit;
    }

    // Eliminar accesorio
    $stmt = $conn->prepare("DELETE FROM accesorios WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: index.php?success=' . urlencode('Accesorio eliminado exitosamente'));

} catch(PDOException $e) {
    header('Location: index.php?error=' . urlencode('Error al eliminar el accesorio: ' . $e->getMessage()));
}
?>
