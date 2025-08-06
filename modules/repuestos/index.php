<?php
$base_url = '../../';
$page_title = 'Repuestos';
$current_module = 'repuestos';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();

// Manejar mensajes de éxito/error desde URL
$mensaje = '';
$tipo_mensaje = '';
if (isset($_GET['success'])) {
    $mensaje = $_GET['success'];
    $tipo_mensaje = 'success';
}
if (isset($_GET['error'])) {
    $mensaje = $_GET['error'];
    $tipo_mensaje = 'error';
}

// Manejar filtros
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// Construir consulta con filtros
$sql = "SELECT * FROM repuestos WHERE 1=1";
$params = [];

if ($buscar) {
    $sql .= " AND (nombre LIKE ? OR observaciones LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " ORDER BY nombre";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $repuestos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Gestión de Repuestos</h2>
        <div class="btn-group">
            <a href="crear.php" class="btn btn-success">Agregar Repuesto</a>
            <a href="movimientos.php" class="btn btn-secondary">Ver Movimientos</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>
    
    <!-- Filtros -->
    <div class="filters">
        <h3>Filtros y Búsqueda</h3>
        <form method="GET">
            <div class="form-row">
                <div class="form-group">
                    <label>Buscar:</label>
                    <input type="text" name="buscar" class="form-control" placeholder="Nombre o observaciones..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="form-group">
                    <div class="btn-group">
                        <button type="submit" class="btn">Filtrar</button>
                        <a href="index.php" class="btn btn-secondary">Limpiar</a>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Tabla de repuestos -->
    <div class="table-container">
        <table id="tabla-datos">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Cantidad Disponible</th>
                    <th>Observaciones</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($repuestos)): ?>
                <tr>
                    <td colspan="5" class="no-results-cell">
                        No se encontraron repuestos con los filtros aplicados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($repuestos as $repuesto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($repuesto['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($repuesto['cantidad_disponible']); ?></td>
                    <td><?php echo htmlspecialchars($repuesto['observaciones']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($repuesto['fecha_registro'])); ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="salida.php?id=<?php echo $repuesto['id']; ?>" class="btn btn-primary">Registrar Salida</a>
                            <a href="editar.php?id=<?php echo $repuesto['id']; ?>" class="btn btn-warning">Editar</a>
                            <a href="eliminar.php?id=<?php echo $repuesto['id']; ?>" class="btn btn-danger" onclick="return confirmarEliminacion('¿Está seguro de eliminar este repuesto? Esto eliminará también su historial de movimientos.')">Eliminar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-small text-muted">
        <strong>Total de repuestos:</strong> <?php echo count($repuestos); ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
