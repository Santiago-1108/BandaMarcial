<?php
require_once '../../config/database.php';

$conn = getDBConnection();

// Obtener ID del repuesto
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // Eliminar repuesto (la FK en movimientos_repuestos tiene ON DELETE CASCADE,
    // por lo que los movimientos asociados también se eliminarán automáticamente)
    $stmt = $conn->prepare("DELETE FROM repuestos WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: index.php?success=' . urlencode('Repuesto eliminado exitosamente.'));

} catch(PDOException $e) {
    header('Location: index.php?error=' . urlencode('Error al eliminar el repuesto: ' . $e->getMessage()));
}
?>
