<?php
$base_url = '../../';
$page_title = 'Crear Préstamo';
$current_module = 'prestamos'; // Definir el módulo actual
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();

// Obtener estudiante preseleccionado si viene de URL
$estudiante_preseleccionado = isset($_GET['estudiante_id']) ? (int)$_GET['estudiante_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $estudiante_id = (int)$_POST['estudiante_id'];
    $fecha_prestamo = $_POST['fecha_prestamo'];
    $fecha_devolucion_esperada = $_POST['fecha_devolucion_esperada'];
    $observaciones = trim($_POST['observaciones']);
    $tipos_item = $_POST['tipo_item'] ?? [];
    $items_id = $_POST['item_id'] ?? [];
    // $cantidades_item ya no es necesaria para uniformes, pero se mantiene para instrumentos (siempre 1)

    // Validaciones
    if (empty($estudiante_id) || empty($fecha_prestamo) || empty($fecha_devolucion_esperada) || empty($tipos_item)) {
        header('Location: crear.php?error=' . urlencode('Por favor complete todos los campos obligatorios y agregue al menos un item.'));
        exit;
    }
    
    if ($fecha_devolucion_esperada < $fecha_prestamo) {
        header('Location: crear.php?error=' . urlencode('La fecha de devolución no puede ser anterior a la fecha de préstamo.'));
        exit;
    }

    try {
        $conn->beginTransaction();
        
        // Crear préstamo
        $stmt = $conn->prepare("INSERT INTO prestamos (estudiante_id, fecha_prestamo, fecha_devolucion_esperada, observaciones) VALUES (?, ?, ?, ?)");
        $stmt->execute([$estudiante_id, $fecha_prestamo, $fecha_devolucion_esperada, $observaciones]);
        $prestamo_id = $conn->lastInsertId();
        
        // Agregar items al préstamo
        for ($i = 0; $i < count($tipos_item); $i++) {
            $tipo_item = $tipos_item[$i];
            $item_id = (int)$items_id[$i];
            // Para items individuales, la cantidad a prestar siempre es 1
            $cantidad_a_prestar = 1; 

            if (!empty($tipo_item) && !empty($item_id)) {
                if ($tipo_item == 'instrumento') {
                    // Verificar disponibilidad del instrumento
                    $stmt_check = $conn->prepare("SELECT estado FROM instrumentos WHERE id = ?");
                    $stmt_check->execute([$item_id]);
                    $instrumento_estado = $stmt_check->fetchColumn();

                    if ($instrumento_estado != 'Disponible') {
                        $conn->rollback();
                        header('Location: crear.php?error=' . urlencode('El instrumento seleccionado no está disponible.'));
                        exit;
                    }

                    // Insertar item en préstamo (cantidad_prestada siempre 1 para instrumentos)
                    $stmt = $conn->prepare("INSERT INTO prestamo_items (prestamo_id, tipo_item, item_id, cantidad_prestada) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$prestamo_id, $tipo_item, $item_id]);
                    
                    // Actualizar estado del instrumento
                    $stmt = $conn->prepare("UPDATE instrumentos SET estado = 'Prestado' WHERE id = ?");
                    $stmt->execute([$item_id]);

                } elseif ($tipo_item == 'uniforme') {
                    // Verificar disponibilidad del uniforme individual
                    $stmt_check = $conn->prepare("SELECT estado FROM uniformes WHERE id = ?");
                    $stmt_check->execute([$item_id]);
                    $uniforme_estado = $stmt_check->fetchColumn();

                    if ($uniforme_estado != 'Disponible') {
                        $conn->rollback();
                        header('Location: crear.php?error=' . urlencode('El uniforme seleccionado no está disponible.'));
                        exit;
                    }

                    // Insertar item en préstamo (cantidad_prestada siempre 1 para uniformes individuales)
                    $stmt = $conn->prepare("INSERT INTO prestamo_items (prestamo_id, tipo_item, item_id, cantidad_prestada) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$prestamo_id, $tipo_item, $item_id]);

                    // Actualizar estado del uniforme
                    $stmt = $conn->prepare("UPDATE uniformes SET estado = 'Prestado' WHERE id = ?");
                    $stmt->execute([$item_id]);
                } elseif ($tipo_item == 'accesorio') { // Nuevo: Manejo de accesorios
                    // Verificar disponibilidad del accesorio individual
                    $stmt_check = $conn->prepare("SELECT estado FROM accesorios WHERE id = ?");
                    $stmt_check->execute([$item_id]);
                    $accesorio_estado = $stmt_check->fetchColumn();

                    if ($accesorio_estado != 'Disponible') {
                        $conn->rollback();
                        header('Location: crear.php?error=' . urlencode('El accesorio seleccionado no está disponible.'));
                        exit;
                    }

                    // Insertar item en préstamo (cantidad_prestada siempre 1 para accesorios individuales)
                    $stmt = $conn->prepare("INSERT INTO prestamo_items (prestamo_id, tipo_item, item_id, cantidad_prestada) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$prestamo_id, $tipo_item, $item_id]);

                    // Actualizar estado del accesorio
                    $stmt = $conn->prepare("UPDATE accesorios SET estado = 'Prestado' WHERE id = ?");
                    $stmt->execute([$item_id]);
                }
            }
        }
        
        $conn->commit();
        header('Location: index.php?success=' . urlencode('Préstamo creado exitosamente.'));
        exit;
        
    } catch(PDOException $e) {
        $conn->rollback();
        header('Location: crear.php?error=' . urlencode('Error al crear el préstamo: ' . $e->getMessage()));
        exit;
    }
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

// Obtener estudiantes
try {
    $stmt = $conn->query("SELECT id, nombre_completo, grado FROM estudiantes ORDER BY nombre_completo");
    $estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Crear Nuevo Préstamo</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="form-prestamo">
        <div class="form-row">
            <div class="form-group">
                <label for="estudiante_id">Estudiante *</label>
                <select id="estudiante_id" name="estudiante_id" class="form-control" required>
                    <option value="">Seleccionar estudiante</option>
                    <?php foreach ($estudiantes as $estudiante): ?>
                    <option value="<?php echo $estudiante['id']; ?>" <?php echo $estudiante_preseleccionado == $estudiante['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($estudiante['nombre_completo'] . ' - ' . $estudiante['grado']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="fecha_prestamo">Fecha de Préstamo *</label>
                <input type="date" id="fecha_prestamo" name="fecha_prestamo" class="form-control" 
                       value="<?php echo date('Y-m-d'); ?>" required>
            </div>
        </div>
        
        <div class="form-group">
            <label for="fecha_devolucion_esperada">Fecha de Devolución Esperada *</label>
            <input type="date" id="fecha_devolucion_esperada" name="fecha_devolucion_esperada" class="form-control" 
                   min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        
        <div class="form-group">
            <label>Items a Prestar *</label>
            <div id="items-container">
                <div class="form-row item-row" data-item-index="0">
                    <div class="form-group">
                        <label>Tipo de Item:</label>
                        <select name="tipo_item[]" class="form-control" onchange="cargarItems(this, 0)" required>
                            <option value="">Seleccionar tipo</option>
                            <option value="instrumento">Instrumento</option>
                            <option value="uniforme">Uniforme</option>
                            <option value="accesorio">Accesorio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Item:</label>
                        <select name="item_id[]" class="form-control" id="item_select_0" required>
                            <option value="">Seleccionar item</option>
                        </select>
                    </div>
                    <!-- La cantidad ya no es necesaria para uniformes, solo para instrumentos (siempre 1) -->
                    <!-- <div class="form-group" id="cantidad_group_0" style="display: none;">
                        <label>Cantidad:</label>
                        <input type="number" name="cantidad_item[]" class="form-control" id="cantidad_input_0" min="1" value="1">
                    </div> -->
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="button" class="btn btn-success" onclick="agregarItem()">Agregar Item</button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Observaciones adicionales sobre el préstamo..."></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Crear Préstamo</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
