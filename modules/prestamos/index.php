<?php
$base_url = '../../';
$page_title = 'Préstamos';
$current_module = 'prestamos'; // Definir el módulo actual
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
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$filtro_estudiante = isset($_GET['estudiante']) ? $_GET['estudiante'] : '';
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// Construir consulta con filtros
$sql = "
    SELECT p.*, e.nombre_completo, e.grado,
           COUNT(pi.id) as total_items_prestados, -- Cuenta el número de items individuales prestados
           SUM(CASE WHEN pi.cantidad_devuelta < pi.cantidad_prestada THEN 1 ELSE 0 END) as items_pendientes -- Suma 1 por cada item individual pendiente
    FROM prestamos p
    JOIN estudiantes e ON p.estudiante_id = e.id
    LEFT JOIN prestamo_items pi ON p.id = pi.prestamo_id
    WHERE 1=1
";
$params = [];

if ($filtro_estado) {
    $sql .= " AND p.estado = ?";
    $params[] = $filtro_estado;
}

if ($filtro_estudiante) {
    $sql .= " AND p.estudiante_id = ?";
    $params[] = $filtro_estudiante;
}

if ($buscar) {
    $sql .= " AND (e.nombre_completo LIKE ? OR e.documento_identidad LIKE ? OR p.observaciones LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " GROUP BY p.id ORDER BY p.fecha_prestamo DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener estudiantes para el filtro
    $stmt_estudiantes = $conn->query("SELECT id, nombre_completo FROM estudiantes ORDER BY nombre_completo");
    $estudiantes = $stmt_estudiantes->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Gestión de Préstamos</h2>
        <div class="btn-group">
            <a href="crear.php" class="btn btn-success">Crear Préstamo</a>
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
                    <input type="text" name="buscar" class="form-control" placeholder="Estudiante, documento u observaciones..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="form-group">
                    <label>Estado:</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="Activo" <?php echo $filtro_estado == 'Activo' ? 'selected' : ''; ?>>Activo</option>
                        <option value="Devuelto" <?php echo $filtro_estado == 'Devuelto' ? 'selected' : ''; ?>>Devuelto</option>
                        <option value="Vencido" <?php echo $filtro_estado == 'Vencido' ? 'selected' : ''; ?>>Vencido</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estudiante:</label>
                    <select name="estudiante" class="form-control">
                        <option value="">Todos los estudiantes</option>
                        <?php foreach ($estudiantes as $estudiante): ?>
                        <option value="<?php echo $estudiante['id']; ?>" <?php echo $filtro_estudiante == $estudiante['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($estudiante['nombre_completo']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
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
    
    <!-- Tabla de préstamos -->
    <div class="table-container">
        <table id="tabla-datos">
            <thead>
                <tr>
                    <th>Estudiante</th>
                    <th>Grado</th>
                    <th>Fecha Préstamo</th>
                    <th>Fecha Devolución</th>
                    <th>Total Items</th>
                    <th>Pendientes</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prestamos)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 30px;">
                        No se encontraron préstamos con los filtros aplicados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($prestamos as $prestamo): ?>
                <tr>
                    <td><?php echo htmlspecialchars($prestamo['nombre_completo']); ?></td>
                    <td><?php echo htmlspecialchars($prestamo['grado']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></td>
                    <td>
                        <?php 
                        $fecha_esperada = date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada']));
                        $fecha_real = $prestamo['fecha_devolucion_real'] ? date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])) : '';
                        echo $fecha_real ? $fecha_real : $fecha_esperada;
                        ?>
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
                            <a href="ver.php?id=<?php echo $prestamo['id']; ?>" class="btn btn-secondary">Ver</a>
                            <?php if ($prestamo['estado'] == 'Activo'): ?>
                            <a href="devolver.php?id=<?php echo $prestamo['id']; ?>" class="btn btn-success">Devolver</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-small text-muted">
        <strong>Total de préstamos:</strong> <?php echo count($prestamos); ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
