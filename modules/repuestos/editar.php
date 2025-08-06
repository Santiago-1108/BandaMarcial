<?php
$base_url = '../../';
$page_title = 'Editar Repuesto';
$current_module = 'repuestos';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();
$mensaje = '';
$tipo_mensaje = '';

// Obtener ID del repuesto
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del repuesto
try {
    $stmt = $conn->prepare("SELECT * FROM repuestos WHERE id = ?");
    $stmt->execute([$id]);
    $repuesto = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$repuesto) {
        header('Location: index.php?error=' . urlencode('Repuesto no encontrado.'));
        exit;
    }
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

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
            // Actualizar repuesto
            $stmt = $conn->prepare("UPDATE repuestos SET nombre = ?, cantidad_disponible = ?, observaciones = ? WHERE id = ?");
            if ($stmt->execute([$nombre, $cantidad_disponible, $observaciones, $id])) {
                // Actualizar datos mostrados en la página
                $repuesto['nombre'] = $nombre;
                $repuesto['cantidad_disponible'] = $cantidad_disponible;
                $repuesto['observaciones'] = $observaciones;

                header('Location: index.php?success=' . urlencode('Repuesto actualizado exitosamente.'));
                exit;
            }
        } catch(PDOException $e) {
            $mensaje = 'Error al actualizar el repuesto: ' . $e->getMessage();
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
        <h2>Editar Repuesto</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="form-repuesto">
        <div class="form-group">
            <label for="nombre">Nombre del Repuesto *</label>
            <input type="text" id="nombre" name="nombre" class="form-control" 
                   value="<?php echo htmlspecialchars($repuesto['nombre']); ?>" 
                   placeholder="Ej: Parches de Caja, Aceite para Válvulas" required>
        </div>
        
        <div class="form-group">
            <label for="cantidad_disponible">Cantidad Disponible *</label>
            <input type="number" id="cantidad_disponible" name="cantidad_disponible" class="form-control" 
                   value="<?php echo htmlspecialchars($repuesto['cantidad_disponible']); ?>" 
                   min="0" required>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Observaciones adicionales sobre el repuesto..."><?php echo htmlspecialchars($repuesto['observaciones']); ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Actualizar Repuesto</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
