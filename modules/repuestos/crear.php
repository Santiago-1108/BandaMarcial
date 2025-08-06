<?php
$base_url = '../../';
$page_title = 'Agregar Repuesto';
$current_module = 'repuestos';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $cantidad_disponible = (int)$_POST['cantidad_disponible'];
    $observaciones = trim($_POST['observaciones']);
    
    // Validaciones
    if (empty($nombre) || $cantidad_disponible < 0) {
        $mensaje = 'Por favor complete el nombre y asegúrese de que la cantidad sea válida.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Insertar repuesto
            $stmt = $conn->prepare("INSERT INTO repuestos (nombre, cantidad_disponible, observaciones) VALUES (?, ?, ?)");
            
            if ($stmt->execute([$nombre, $cantidad_disponible, $observaciones])) {
                // Registrar el movimiento de entrada
                $repuesto_id = $conn->lastInsertId();
                $stmt_mov = $conn->prepare("INSERT INTO movimientos_repuestos (repuesto_id, tipo_movimiento, cantidad, observaciones) VALUES (?, 'entrada', ?, ?)");
                $stmt_mov->execute([$repuesto_id, $cantidad_disponible, 'Entrada inicial al crear repuesto']);

                header('Location: index.php?success=' . urlencode('Repuesto agregado exitosamente.'));
                exit;
            }
        } catch(PDOException $e) {
            $mensaje = 'Error al agregar el repuesto: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Manejar mensajes de éxito/error desde URL (si vienen de una redirección previa)
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
        <h2>Agregar Nuevo Repuesto</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="form-repuesto">
        <div class="form-group">
            <label for="nombre">Nombre del Repuesto *</label>
            <input type="text" id="nombre" name="nombre" class="form-control" 
                   value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                   placeholder="Ej: Parches de Caja, Aceite para Válvulas" required>
        </div>
        
        <div class="form-group">
            <label for="cantidad_disponible">Cantidad Inicial *</label>
            <input type="number" id="cantidad_disponible" name="cantidad_disponible" class="form-control" 
                   value="<?php echo isset($_POST['cantidad_disponible']) ? htmlspecialchars($_POST['cantidad_disponible']) : '0'; ?>" 
                   min="0" required>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Observaciones adicionales sobre el repuesto..."><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Agregar Repuesto</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
