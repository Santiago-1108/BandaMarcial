<?php
$base_url = '../../';
$page_title = 'Estudiantes';
$current_module = 'estudiantes';
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
$filtro_grado = isset($_GET['grado']) ? $_GET['grado'] : '';
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// Construir consulta con filtros, incluyendo los nuevos campos
$sql = "SELECT id, nombre_completo, grado, documento_identidad, direccion, telefono, fecha_registro, activo FROM estudiantes WHERE 1=1";
$params = [];

if ($filtro_grado) {
    $sql .= " AND grado = ?";
    $params[] = $filtro_grado;
}

if ($buscar) {
    $sql .= " AND (nombre_completo LIKE ? OR documento_identidad LIKE ? OR grado LIKE ? OR direccion LIKE ? OR telefono LIKE ?)"; // Incluir búsqueda en nuevos campos
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%"; // Para dirección
    $params[] = "%$buscar%"; // Para teléfono
}

$sql .= " ORDER BY nombre_completo";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener grados únicos para el filtro
    $stmt_grados = $conn->query("SELECT DISTINCT grado FROM estudiantes ORDER BY grado");
    $grados = $stmt_grados->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Gestión de Estudiantes</h2>
        <div class="btn-group">
            <a href="crear.php" class="btn btn-success">Registrar Estudiante</a>
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
                    <input type="text" name="buscar" class="form-control" placeholder="Nombre, documento, grado, dirección o teléfono..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="form-group">
                    <label>Grado:</label>
                    <select name="grado" class="form-control">
                        <option value="">Todos los grados</option>
                        <?php foreach ($grados as $grado): ?>
                        <option value="<?php echo htmlspecialchars($grado); ?>" <?php echo $filtro_grado == $grado ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($grado); ?>
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
    
    <!-- Tabla de estudiantes -->
    <div class="table-container">
        <table id="tabla-datos">
            <thead>
                <tr>
                    <th>Nombre Completo</th>
                    <th>Grado</th>
                    <th>Documento</th>
                    <th>Dirección</th>  <!-- Nueva columna -->
                    <th>Teléfono</th>    <!-- Nueva columna -->
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($estudiantes)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;"> <!-- colspan ajustado -->
                        No se encontraron estudiantes con los filtros aplicados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($estudiantes as $estudiante): ?>
                <tr>
                    <td><?php echo htmlspecialchars($estudiante['nombre_completo']); ?></td>
                    <td><?php echo htmlspecialchars($estudiante['grado']); ?></td>
                    <td><?php echo htmlspecialchars($estudiante['documento_identidad']); ?></td>
                    <td><?php echo htmlspecialchars($estudiante['direccion'] ?? 'N/A'); ?></td>  <!-- Mostrar dirección -->
                    <td><?php echo htmlspecialchars($estudiante['telefono'] ?? 'N/A'); ?></td>    <!-- Mostrar teléfono -->
                    <td><?php echo date('d/m/Y', strtotime($estudiante['fecha_registro'])); ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="ver.php?id=<?php echo $estudiante['id']; ?>" class="btn btn-secondary">Ver</a>
                            <a href="editar.php?id=<?php echo $estudiante['id']; ?>" class="btn btn-warning">Editar</a>
                            <a href="eliminar.php?id=<?php echo $estudiante['id']; ?>" class="btn btn-danger" onclick="return confirmarEliminacion('¿Está seguro de eliminar este estudiante?')">Eliminar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-small text-muted">
        <strong>Total de estudiantes:</strong> <?php echo count($estudiantes); ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
