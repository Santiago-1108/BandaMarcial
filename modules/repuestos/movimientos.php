<?php
$base_url = '../../';
$page_title = 'Movimientos de Repuestos';
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
$filtro_repuesto = isset($_GET['repuesto']) ? $_GET['repuesto'] : '';
$filtro_tipo_movimiento = isset($_GET['tipo_movimiento']) ? $_GET['tipo_movimiento'] : '';
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';

// Construir consulta con filtros
$sql = "
  SELECT mr.*, 
         COALESCE(r.nombre, 'Repuesto Eliminado') as nombre_repuesto, 
         i.nombre as nombre_instrumento, 
         i.codigo_serie as codigo_instrumento
  FROM movimientos_repuestos mr
  LEFT JOIN repuestos r ON mr.repuesto_id = r.id 
  LEFT JOIN instrumentos i ON mr.instrumento_id = i.id
  WHERE 1=1
";
$params = [];

if ($filtro_repuesto) {
  $sql .= " AND mr.repuesto_id = ?";
  $params[] = $filtro_repuesto;
}

if ($filtro_tipo_movimiento) {
  $sql .= " AND mr.tipo_movimiento = ?";
  $params[] = $filtro_tipo_movimiento;
}

if ($buscar) {
  $sql .= " AND (COALESCE(r.nombre, 'Repuesto Eliminado') LIKE ? OR mr.observaciones LIKE ? OR i.nombre LIKE ? OR i.codigo_serie LIKE ?)"; // CAMBIO: Buscar también en el nombre del repuesto eliminado
  $params[] = "%$buscar%";
  $params[] = "%$buscar%";
  $params[] = "%$buscar%";
  $params[] = "%$buscar%";
}

$sql .= " ORDER BY mr.fecha_movimiento DESC";

try {
  $stmt = $conn->prepare($sql);
  $stmt->execute($params);
  $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
  
  // Obtener lista de repuestos para el filtro (solo los existentes)
  $stmt_repuestos = $conn->query("SELECT id, nombre FROM repuestos ORDER BY nombre");
  $lista_repuestos = $stmt_repuestos->fetchAll(PDO::FETCH_ASSOC);
  
} catch(PDOException $e) {
  echo "Error: " . $e->getMessage();
}
?>

<div class="main-content">
  <div class="header-actions">
      <h2>Historial de Movimientos de Repuestos</h2>
      <div class="btn-group">
          <a href="index.php" class="btn btn-secondary">Volver a Repuestos</a>
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
                  <input type="text" name="buscar" class="form-control" placeholder="Repuesto, instrumento u observaciones..." value="<?php echo htmlspecialchars($buscar); ?>">
              </div>
              <div class="form-group">
                  <label>Repuesto:</label>
                  <select name="repuesto" class="form-control">
                      <option value="">Todos los repuestos</option>
                      <?php foreach ($lista_repuestos as $repuesto_opt): ?>
                      <option value="<?php echo htmlspecialchars($repuesto_opt['id']); ?>" <?php echo $filtro_repuesto == $repuesto_opt['id'] ? 'selected' : ''; ?>>
                          <?php echo htmlspecialchars($repuesto_opt['nombre']); ?>
                      </option>
                      <?php endforeach; ?>
                  </select>
              </div>
              <div class="form-group">
                  <label>Tipo de Movimiento:</label>
                  <select name="tipo_movimiento" class="form-control">
                      <option value="">Todos</option>
                      <option value="entrada" <?php echo $filtro_tipo_movimiento == 'entrada' ? 'selected' : ''; ?>>Entrada</option>
                      <option value="salida" <?php echo $filtro_tipo_movimiento == 'salida' ? 'selected' : ''; ?>>Salida</option>
                  </select>
              </div>
              <div class="form-group">
                  <div class="btn-group">
                      <button type="submit" class="btn">Filtrar</button>
                      <a href="movimientos.php" class="btn btn-secondary">Limpiar</a>
                  </div>
              </div>
          </div>
      </form>
  </div>
  
  <!-- Tabla de movimientos -->
  <div class="table-container">
      <table id="tabla-datos">
          <thead>
              <tr>
                  <th>Fecha</th>
                  <th>Repuesto</th>
                  <th>Tipo</th>
                  <th>Cantidad</th>
                  <th>Instrumento Asociado</th>
                  <th>Observaciones</th>
              </tr>
          </thead>
          <tbody>
              <?php if (empty($movimientos)): ?>
              <tr>
                  <td colspan="6" class="no-results-cell">
                      No se encontraron movimientos con los filtros aplicados.
                  </td>
              </tr>
              <?php else: ?>
              <?php foreach ($movimientos as $movimiento): ?>
              <tr>
                  <td><?php echo date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'])); ?></td>
                  <td><?php echo htmlspecialchars($movimiento['nombre_repuesto']); ?></td>
                  <td>
                      <span class="estado estado-<?php echo $movimiento['tipo_movimiento'] == 'entrada' ? 'activo' : 'prestado'; ?>">
                          <?php echo ucfirst($movimiento['tipo_movimiento']); ?>
                      </span>
                  </td>
                  <td><?php echo htmlspecialchars($movimiento['cantidad']); ?></td>
                  <td>
                      <?php 
                          if ($movimiento['instrumento_id']) {
                              echo htmlspecialchars($movimiento['nombre_instrumento'] . ' (' . $movimiento['codigo_instrumento'] . ')');
                          } else {
                              echo 'N/A';
                          }
                      ?>
                  </td>
                  <td><?php echo htmlspecialchars($movimiento['observaciones'] ?? ''); ?></td> <!-- CAMBIO: Añadido ?? '' -->
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
          </tbody>
      </table>
  </div>
  
  <div class="text-small text-muted">
      <strong>Total de movimientos:</strong> <?php echo count($movimientos); ?>
  </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
