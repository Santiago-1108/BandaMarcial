<?php
$base_url = '../../';
$page_title = 'Uniformes';
$current_module = 'uniformes';
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
$filtro_tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$filtro_talla = isset($_GET['talla']) ? $_GET['talla'] : '';
$filtro_estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// Construir consulta con filtros
$sql = "SELECT * FROM uniformes WHERE 1=1";
$params = [];

if ($filtro_tipo) {
    $sql .= " AND tipo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_talla) {
    $sql .= " AND talla = ?";
    $params[] = $filtro_talla;
}

if ($filtro_estado) {
    $sql .= " AND estado = ?";
    $params[] = $filtro_estado;
}

if ($buscar) {
    $sql .= " AND (tipo LIKE ? OR talla LIKE ? OR codigo_serie LIKE ? OR observaciones LIKE ?)";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
    $params[] = "%$buscar%";
}

$sql .= " ORDER BY tipo, talla, codigo_serie";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $uniformes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener tipos, tallas y estados únicos para los filtros
    $stmt_tipos = $conn->query("SELECT DISTINCT tipo FROM uniformes ORDER BY tipo");
    $tipos = $stmt_tipos->fetchAll(PDO::FETCH_COLUMN);

    $stmt_tallas = $conn->query("SELECT DISTINCT talla FROM uniformes ORDER BY talla");
    $tallas = $stmt_tallas->fetchAll(PDO::FETCH_COLUMN);

    $stmt_estados = $conn->query("SELECT DISTINCT estado FROM uniformes ORDER BY estado");
    $estados = $stmt_estados->fetchAll(PDO::FETCH_COLUMN);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Gestión de Uniformes</h2>
        <div class="btn-group">
            <a href="crear.php" class="btn btn-success">Agregar Uniforme</a>
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
                    <input type="text" name="buscar" class="form-control" placeholder="Tipo, talla, código o observaciones..." value="<?php echo htmlspecialchars($buscar); ?>">
                </div>
                <div class="form-group">
                    <label>Tipo:</label>
                    <select name="tipo" class="form-control">
                        <option value="">Todos los tipos</option>
                        <?php foreach ($tipos as $tipo_opt): ?>
                        <option value="<?php echo htmlspecialchars($tipo_opt); ?>" <?php echo $filtro_tipo == $tipo_opt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tipo_opt); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Talla:</label>
                    <select name="talla" class="form-control">
                        <option value="">Todas las tallas</option>
                        <?php foreach ($tallas as $talla_opt): ?>
                        <option value="<?php echo htmlspecialchars($talla_opt); ?>" <?php echo $filtro_talla == $talla_opt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($talla_opt); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado:</label>
                    <select name="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        <?php foreach ($estados as $estado_opt): ?>
                        <option value="<?php echo htmlspecialchars($estado_opt); ?>" <?php echo $filtro_estado == $estado_opt ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($estado_opt); ?>
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
    
    <!-- Tabla de uniformes -->
    <div class="table-container">
        <table id="tabla-datos">
            <thead>
                <tr>
                    <th>Tipo</th>
                    <th>Talla</th>
                    <th>Código Serie</th>
                    <th>Estado</th>
                    <th>Observaciones</th>
                    <th>Fecha Registro</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($uniformes)): ?>
                <tr>
                    <td colspan="7" class="no-results-cell">
                        No se encontraron uniformes con los filtros aplicados.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($uniformes as $uniforme): ?>
                <tr>
                    <td><?php echo htmlspecialchars($uniforme['tipo']); ?></td>
                    <td><?php echo $uniforme['talla'] ? htmlspecialchars($uniforme['talla']) : 'N/A'; ?></td>
                    <td><?php echo htmlspecialchars($uniforme['codigo_serie']); ?></td>
                    <td>
                        <span class="estado estado-<?php echo strtolower(str_replace(' ', '-', $uniforme['estado'])); ?>">
                            <?php echo $uniforme['estado']; ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($uniforme['observaciones']); ?></td>
                    <td><?php echo date('d/m/Y', strtotime($uniforme['fecha_registro'])); ?></td>
                    <td>
                        <div class="table-actions">
                            <a href="editar.php?id=<?php echo $uniforme['id']; ?>" class="btn btn-warning">Editar</a>
                            <a href="eliminar.php?id=<?php echo $uniforme['id']; ?>" class="btn btn-danger" onclick="return confirmarEliminacion('¿Está seguro de eliminar este uniforme? Esto solo es posible si no está prestado o tiene préstamos pendientes.')">Eliminar</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <div class="text-small text-muted">
        <strong>Total de uniformes individuales:</strong> <?php echo count($uniformes); ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
