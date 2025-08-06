<?php
$base_url = '../../';
$page_title = 'Instrumentos';
$current_module = 'instrumentos';
require_once '../../config/database.php';
require_once '../../includes/header.php';

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

$conn = getDBConnection();

// Manejar filtros
$filtro_nombre = isset($_GET['nombre']) ? $_GET['nombre'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// Construir consulta con filtros
$sql = "SELECT * FROM instrumentos WHERE 1=1";
$params = [];

if ($filtro_nombre) {
    $sql .= " AND nombre = ?";
    $params[] = $filtro_nombre;
}

if ($filtro_estado) {
    $sql .= " AND estado = ?";
    $params[] = $filtro_estado;
}

if ($buscar) {
    $sql .= " AND (nombre LIKE ? OR codigo_serie LIKE ? OR observaciones LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " ORDER BY nombre, codigo_serie";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $instrumentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener nombres únicos para el filtro
    $stmt_nombres = $conn->query("SELECT DISTINCT nombre FROM instrumentos ORDER BY nombre");
    $nombres = $stmt_nombres->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Gestión de Instrumentos</h2>
        <div class="btn-group">
            <a href="crear.php" class="btn btn-success">Agregar Instrumento</a>
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
                    <input type="text" name="buscar" class="form-control" placeholder="Nombre, código o observaciones..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="form-group">
                    <label>Instrumento:</label>
                    <select name="nombre" class="form-control">
                        <option value="">Todos los instrumentos</option>
                        <?php foreach ($nombres as $nombre): ?>
                        <option value="<?php echo htmlspecialchars($nombre); ?>" <?php echo $filtro_nombre == $nombre ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($nombre); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado:</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <option value="Disponible" <?php echo $filtro_estado == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                        <option value="Prestado" <?php echo $filtro_estado == 'Prestado' ? 'selected' : ''; ?>>Prestado</option>
                        <option value="Dañado" <?php echo $filtro_estado == 'Dañado' ? 'selected' : ''; ?>>Dañado</option>
                        <option value="En reparación" <?php echo $filtro_estado == 'En reparación' ? 'selected' : ''; ?>>En reparación</option>
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
    
    <!-- Tabla de instrumentos -->
    <div class="table-container">
        <table id="tabla-datos">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Código/Serie</th>
                    <th>Estado</th>
                    <th>Observaciones</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($instrumentos)): ?>
                <tr>
                    <td colspan="6" class="no-results-cell">
                        No se encontraron instrumentos con los filtros aplicados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($instrumentos as $instrumento): ?>
                <tr>
                    <td><?php echo htmlspecialchars($instrumento['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($instrumento['codigo_serie']); ?></td>
                    <td>
                        <span class="estado estado-<?php echo strtolower(str_replace(' ', '-', $instrumento['estado'])); ?>">
                            <?php echo $instrumento['estado']; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($instrumento['observaciones']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($instrumento['fecha_registro'])); ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="editar.php?id=<?php echo $instrumento['id']; ?>" class="btn btn-warning">Editar</a>
                            <a href="eliminar.php?id=<?php echo $instrumento['id']; ?>" class="btn btn-danger" onclick="return confirmarEliminacion('¿Está seguro de eliminar este instrumento?')">Eliminar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-small text-muted">
        <strong>Total de instrumentos:</strong> <?php echo count($instrumentos); ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
