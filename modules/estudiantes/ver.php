<?php
$base_url = '../../';
$page_title = 'Ver Estudiante';
$current_module = 'estudiantes';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();

// Obtener ID del estudiante
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // Obtener datos del estudiante
    $stmt = $conn->prepare("SELECT * FROM estudiantes WHERE id = ?");
    $stmt->execute([$id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$estudiante) {
        header('Location: index.php?error=' . urlencode('Estudiante no encontrado.'));
        exit;
    }
    
    // Obtener historial de préstamos
    $stmt = $conn->prepare("
        SELECT p.*, 
               SUM(pi.cantidad_prestada) as total_items_prestados,
               SUM(pi.cantidad_prestada - pi.cantidad_devuelta) as items_pendientes
        FROM prestamos p
        LEFT JOIN prestamo_items pi ON p.id = pi.prestamo_id
        WHERE p.estudiante_id = ?
        GROUP BY p.id
        ORDER BY p.fecha_prestamo DESC
    ");
    $stmt->execute([$id]);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Información del Estudiante</h2>
        <div class="btn-group">
            <a href="editar.php?id=<?php echo $estudiante['id']; ?>" class="btn btn-warning">Editar</a>
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
        </div>
    </div>
    
    <!-- Información del estudiante -->
    <div class="card">
        <h3>Datos Personales</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <strong>Nombre Completo:</strong><br>
                <?php echo htmlspecialchars($estudiante['nombre_completo']); ?>
            </div>
            <div>
                <strong>Grado:</strong><br>
                <?php echo htmlspecialchars($estudiante['grado']); ?>
            </div>
            <div>
                <strong>Documento:</strong><br>
                <?php echo htmlspecialchars($estudiante['documento_identidad']); ?>
            </div>
            <div>
                <strong>Fecha de Registro:</strong><br>
                <?php echo date('d/m/Y H:i', strtotime($estudiante['fecha_registro'])); ?>
            </div>
        </div>
    </div>
    
    <!-- Historial de préstamos -->
    <div class="card">
        <h3>Historial de Préstamos</h3>
        
        <?php if (empty($prestamos)): ?>
        <p style="text-align: center; padding: 20px; color: #666;">
            Este estudiante no tiene préstamos registrados.
        </p>
        <?php else: ?>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Fecha Préstamo</th>
                        <th>Fecha Devolución Esperada</th>
                        <th>Fecha Devolución Real</th>
                        <th>Total Items</th>
                        <th>Items Pendientes</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prestamos as $prestamo): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></td>
                        <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?></td>
                        <td>
                            <?php echo $prestamo['fecha_devolucion_real'] ? date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])) : 'Pendiente'; ?>
                        </td>
                        <td><?php echo $prestamo['total_items_prestados'] ?? 0; ?></td>
                        <td><?php echo $prestamo['items_pendientes'] ?? 0; ?></td>
                        <td>
                            <span class="estado estado-<?php echo strtolower($prestamo['estado']); ?>">
                                <?php echo $prestamo['estado']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="../prestamos/ver.php?id=<?php echo $prestamo['id']; ?>" class="btn btn-secondary">Ver Detalles</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="card">
        <h3>Acciones Rápidas</h3>
        <div class="btn-group">
            <a href="../prestamos/crear.php?estudiante_id=<?php echo $estudiante['id']; ?>" class="btn btn-success">Crear Nuevo Préstamo</a>
            <a href="editar.php?id=<?php echo $estudiante['id']; ?>" class="btn btn-warning">Editar Información</a>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
