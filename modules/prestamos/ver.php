<?php
$base_url = '../../';
$page_title = 'Ver Préstamo';
$current_module = 'prestamos';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();

// Obtener ID del préstamo
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

try {
    // Obtener datos del préstamo
    $stmt = $conn->prepare("
        SELECT p.*, e.nombre_completo, e.grado, e.documento_identidad
        FROM prestamos p
        JOIN estudiantes e ON p.estudiante_id = e.id
        WHERE p.id = ?
    ");
    $stmt->execute([$id]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prestamo) {
        header('Location: index.php?error=' . urlencode('Préstamo no encontrado.'));
        exit;
    }
    
    // Obtener items del préstamo
    $stmt = $conn->prepare("
        SELECT pi.*, 
               CASE 
                   WHEN pi.tipo_item = 'instrumento' THEN i.nombre
                   WHEN pi.tipo_item = 'uniforme' THEN CONCAT(u.tipo, ' Talla ', u.talla)
                   WHEN pi.tipo_item = 'accesorio' THEN a.nombre -- Nuevo: para accesorios
               END as nombre_item,
               CASE 
                   WHEN pi.tipo_item = 'instrumento' THEN i.codigo_serie
                   WHEN pi.tipo_item = 'uniforme' THEN u.codigo_serie
                   WHEN pi.tipo_item = 'accesorio' THEN a.codigo_serie -- Nuevo: para accesorios
               END as detalle_item,
               CASE
                   WHEN pi.tipo_item = 'instrumento' THEN i.estado
                   WHEN pi.tipo_item = 'uniforme' THEN u.estado
                   WHEN pi.tipo_item = 'accesorio' THEN a.estado -- Nuevo: para accesorios
               END as estado_item_actual
        FROM prestamo_items pi
        LEFT JOIN instrumentos i ON pi.tipo_item = 'instrumento' AND pi.item_id = i.id
        LEFT JOIN uniformes u ON pi.tipo_item = 'uniforme' AND pi.item_id = u.id
        LEFT JOIN accesorios a ON pi.tipo_item = 'accesorio' AND pi.item_id = a.id -- Nuevo: para accesorios
        WHERE pi.prestamo_id = ?
        ORDER BY pi.tipo_item, pi.id
    ");
    $stmt->execute([$id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

// Calcular días restantes o vencidos
$hoy = new DateTime();
$fecha_devolucion = new DateTime($prestamo['fecha_devolucion_esperada']);
$diferencia = $hoy->diff($fecha_devolucion);
$dias_diferencia = $diferencia->days;
$vencido = $hoy > $fecha_devolucion;
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Detalles del Préstamo #<?php echo $prestamo['id']; ?></h2>
        <div class="btn-group">
            <?php if ($prestamo['estado'] == 'Activo'): ?>
            <a href="devolver.php?id=<?php echo $prestamo['id']; ?>" class="btn btn-success">Procesar Devolución</a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
        </div>
    </div>
    
    <!-- Información del préstamo -->
    <div class="card">
        <h3>Información General</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <strong>Estudiante:</strong><br>
                <?php echo htmlspecialchars($prestamo['nombre_completo']); ?>
            </div>
            <div>
                <strong>Grado:</strong><br>
                <?php echo htmlspecialchars($prestamo['grado']); ?>
            </div>
            <div>
                <strong>Documento:</strong><br>
                <?php echo htmlspecialchars($prestamo['documento_identidad']); ?>
            </div>
            <div>
                <strong>Estado:</strong><br>
                <span class="estado estado-<?php echo strtolower($prestamo['estado']); ?>">
                    <?php echo $prestamo['estado']; ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Fechas del préstamo -->
    <div class="card">
        <h3>Fechas</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <div>
                <strong>Fecha de Préstamo:</strong><br>
                <?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?>
            </div>
            <div>
                <strong>Fecha de Devolución Esperada:</strong><br>
                <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?>
            </div>
            <div>
                <strong>Fecha de Devolución Real:</strong><br>
                <?php echo $prestamo['fecha_devolucion_real'] ? date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])) : 'Pendiente'; ?>
            </div>
            <div>
                <strong>Estado del Plazo:</strong><br>
                <?php if ($prestamo['estado'] == 'Devuelto'): ?>
                    <span class="estado estado-devuelto">Devuelto</span>
                <?php elseif ($vencido): ?>
                    <span class="estado estado-vencido">Vencido por <?php echo $dias_diferencia; ?> días</span>
                <?php else: ?>
                    <span class="estado estado-activo"><?php echo $dias_diferencia; ?> días restantes</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Items prestados -->
    <div class="card">
        <h3>Items Prestados</h3>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Item</th>
                        <th>Código/Detalle</th>
                        <th>Estado en Préstamo</th>
                        <th>Estado Actual del Item</th>
                        <th>Fecha Última Devolución</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo ucfirst($item['tipo_item']); ?></td>
                        <td><?php echo htmlspecialchars($item['nombre_item']); ?></td>
                        <td><?php echo htmlspecialchars($item['detalle_item'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="estado estado-<?php echo ($item['cantidad_devuelta'] >= $item['cantidad_prestada']) ? 'devuelto' : 'prestado'; ?>">
                                <?php echo ($item['cantidad_devuelta'] >= $item['cantidad_prestada']) ? 'Devuelto' : 'Pendiente'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="estado estado-<?php echo strtolower(str_replace(' ', '-', $item['estado_item_actual'])); ?>">
                                <?php echo $item['estado_item_actual']; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $item['fecha_devolucion'] ? date('d/m/Y H:i', strtotime($item['fecha_devolucion'])) : 'Pendiente'; ?>
                        </td>
                        <td><?php echo htmlspecialchars($item['observaciones_devolucion'] ?? ''); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Observaciones -->
    <?php if ($prestamo['observaciones']): ?>
    <div class="card">
        <h3>Observaciones</h3>
        <p><?php echo nl2br(htmlspecialchars($prestamo['observaciones'])); ?></p>
    </div>
    <?php endif; ?>
    
    <!-- Acciones -->
    <div class="card">
        <h3>Acciones</h3>
        <div class="btn-group">
            <?php if ($prestamo['estado'] == 'Activo'): ?>
            <a href="devolver.php?id=<?php echo $prestamo['id']; ?>" class="btn btn-success">Procesar Devolución</a>
            <?php endif; ?>
            <a href="../estudiantes/ver.php?id=<?php echo $prestamo['estudiante_id']; ?>" class="btn">Ver Estudiante</a>
            <button onclick="window.print()" class="btn btn-secondary">Imprimir</button>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>
