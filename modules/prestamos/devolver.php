<?php
$base_url = '../../';
$page_title = 'Devolver Préstamo';
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

// Procesar devolución
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $items_devolver = $_POST['items_devolver'] ?? []; // Array de IDs de prestamo_items
    $observaciones_items = $_POST['observaciones_items'] ?? []; // Array asociativo: [prestamo_item_id => observaciones]
    
    if (empty($items_devolver)) {
        header('Location: devolver.php?id=' . $id . '&error=' . urlencode('Debe seleccionar al menos un item para devolver.'));
        exit;
    }
    
    try {
        $conn->beginTransaction();
        
        foreach ($items_devolver as $prestamo_item_id) {
            // Para items individuales, la cantidad a devolver siempre es 1
            $cantidad_a_devolver = 1; 
            $observaciones = $observaciones_items[$prestamo_item_id] ?? '';

            // Obtener información actual del prestamo_item
            $stmt = $conn->prepare("SELECT tipo_item, item_id, cantidad_prestada, cantidad_devuelta FROM prestamo_items WHERE id = ?");
            $stmt->execute([$prestamo_item_id]);
            $item_info = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item_info) {
                continue; // Item no encontrado
            }

            // Si ya está devuelto, saltar
            if ($item_info['cantidad_devuelta'] >= $item_info['cantidad_prestada']) {
                continue;
            }

            // Actualizar cantidad_devuelta en prestamo_items (siempre 1 para items individuales)
            $stmt = $conn->prepare("UPDATE prestamo_items SET cantidad_devuelta = 1, fecha_devolucion = NOW(), observaciones_devolucion = ? WHERE id = ?");
            $stmt->execute([$observaciones, $prestamo_item_id]);
            
            // Actualizar estado del item en su tabla original (instrumentos, uniformes o accesorios)
            if ($item_info['tipo_item'] == 'instrumento') {
                $stmt = $conn->prepare("UPDATE instrumentos SET estado = 'Disponible' WHERE id = ?");
                $stmt->execute([$item_info['item_id']]);
            } elseif ($item_info['tipo_item'] == 'uniforme') {
                $stmt = $conn->prepare("UPDATE uniformes SET estado = 'Disponible' WHERE id = ?");
                $stmt->execute([$item_info['item_id']]);
            } elseif ($item_info['tipo_item'] == 'accesorio') { // Nuevo: Manejo de accesorios
                $stmt = $conn->prepare("UPDATE accesorios SET estado = 'Disponible' WHERE id = ?");
                $stmt->execute([$item_info['item_id']]);
            }
        }
        
        // Verificar si todos los items del préstamo han sido devueltos
        $stmt = $conn->prepare("SELECT COUNT(*) FROM prestamo_items WHERE prestamo_id = ? AND cantidad_devuelta < cantidad_prestada");
        $stmt->execute([$id]);
        $items_pendientes_count = $stmt->fetchColumn();
        
        // Si no quedan items pendientes, marcar préstamo como devuelto
        if ($items_pendientes_count == 0) {
            $stmt = $conn->prepare("UPDATE prestamos SET estado = 'Devuelto', fecha_devolucion_real = NOW() WHERE id = ?");
            $stmt->execute([$id]);
        }
        
        $conn->commit();
        header('Location: ver.php?id=' . $id . '&success=' . urlencode('Devolución procesada exitosamente.'));
        exit;
        
    } catch(PDOException $e) {
        $conn->rollback();
        header('Location: devolver.php?id=' . $id . '&error=' . urlencode('Error al procesar la devolución: ' . $e->getMessage()));
        exit;
    }
}

try {
    // Obtener datos del préstamo
    $stmt = $conn->prepare("
        SELECT p.*, e.nombre_completo, e.grado
        FROM prestamos p
        JOIN estudiantes e ON p.estudiante_id = e.id
        WHERE p.id = ? AND p.estado = 'Activo'
    ");
    $stmt->execute([$id]);
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$prestamo) {
        header('Location: index.php?error=' . urlencode('Préstamo no encontrado o ya devuelto.'));
        exit;
    }
    
    // Obtener items pendientes de devolución
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
               END as detalle_item
        FROM prestamo_items pi
        LEFT JOIN instrumentos i ON pi.tipo_item = 'instrumento' AND pi.item_id = i.id
        LEFT JOIN uniformes u ON pi.tipo_item = 'uniforme' AND pi.item_id = u.id
        LEFT JOIN accesorios a ON pi.tipo_item = 'accesorio' AND pi.item_id = a.id -- Nuevo: para accesorios
        WHERE pi.prestamo_id = ? AND pi.cantidad_devuelta < pi.cantidad_prestada
        ORDER BY pi.tipo_item, pi.id
    ");
    $stmt->execute([$id]);
    $items_pendientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items_pendientes)) {
        header('Location: ver.php?id=' . $id . '&success=' . urlencode('Todos los items ya han sido devueltos.'));
        exit;
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

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
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Devolver Items - Préstamo #<?php echo $prestamo['id']; ?></h2>
        <div class="btn-group">
            <a href="ver.php?id=<?php echo $prestamo['id']; ?>" class="btn btn-secondary">Volver a Detalles</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>
    
    <!-- Información del préstamo -->
    <div class="card">
        <h3>Información del Préstamo</h3>
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
                <strong>Fecha de Préstamo:</strong><br>
                <?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?>
            </div>
            <div>
                <strong>Fecha de Devolución Esperada:</strong><br>
                <?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?>
            </div>
        </div>
    </div>
    
    <!-- Formulario de devolución -->
    <form method="POST">
        <div class="card">
            <h3>Items Pendientes de Devolución</h3>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Seleccionar</th>
                            <th>Tipo</th>
                            <th>Item</th>
                            <th>Código/Detalle</th>
                            <th>Estado Actual</th>
                            <th>Observaciones de Devolución</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items_pendientes as $item): 
                            // Para items individuales, cantidad_prestada y cantidad_devuelta son 1 o 0
                            $is_returned = ($item['cantidad_devuelta'] >= $item['cantidad_prestada']);
                        ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="items_devolver[]" value="<?php echo $item['id']; ?>" <?php echo $is_returned ? 'disabled' : 'checked'; ?>>
                            </td>
                            <td><?php echo ucfirst($item['tipo_item']); ?></td>
                            <td><?php echo htmlspecialchars($item['nombre_item']); ?></td>
                            <td><?php echo htmlspecialchars($item['detalle_item'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="estado estado-<?php echo $is_returned ? 'devuelto' : 'prestado'; ?>">
                                    <?php echo $is_returned ? 'Devuelto' : 'Pendiente'; ?>
                                </span>
                            </td>
                            <td>
                                <input type="text" name="observaciones_items[<?php echo $item['id']; ?>]" 
                                       class="form-control" placeholder="Estado del item al devolverlo..." 
                                       <?php echo $is_returned ? 'disabled' : ''; ?>>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Procesar Devolución</button>
            <a href="ver.php?id=<?php echo $prestamo['id']; ?>" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
