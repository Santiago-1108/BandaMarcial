<?php
require_once '../../config/database.php';

$conn = getDBConnection();

// Obtener ID del estudiante
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // Verificar si el estudiante tiene préstamos activos
    $stmt = $conn->prepare("SELECT COUNT(*) FROM prestamos WHERE estudiante_id = ? AND estado = 'Activo'");
    $stmt->execute([$id]);
    $prestamos_activos = $stmt->fetchColumn();
    
    if ($prestamos_activos > 0) {
        header('Location: index.php?error=' . urlencode('El estudiante no puede ser eliminado porque tiene préstamos activos.'));
        exit;
    }
    
    // Eliminar estudiante
    $stmt = $conn->prepare("DELETE FROM estudiantes WHERE id = ?");
    $stmt->execute([$id]);
    
    header('Location: index.php?success=' . urlencode('Estudiante eliminado exitosamente.'));
    
} catch(PDOException $e) {
    header('Location: index.php?error=' . urlencode('Error al eliminar el estudiante: ' . $e->getMessage()));
}
?>
