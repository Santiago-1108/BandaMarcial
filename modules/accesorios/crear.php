<?php
$base_url = '../../';
$page_title = 'Crear Accesorio';
$current_module = 'accesorios';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $estado = $_POST['estado'];
    $codigo_serie = trim($_POST['codigo_serie']);
    $observaciones = trim($_POST['observaciones']);
    
    // Validaciones
    if (empty($nombre) || empty($estado) || empty($codigo_serie)) {
        $mensaje = 'Por favor complete todos los campos obligatorios.';
        $tipo_mensaje = 'error';
    } elseif ($estado == 'Prestado') { // Nueva validación: no se puede crear un accesorio directamente como "Prestado"
        $mensaje = 'No se puede crear un accesorio con estado "Prestado". Los accesorios deben estar "Disponibles" para ser prestados a través del módulo de Préstamos.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar que el código de serie no exista
            $stmt = $conn->prepare("SELECT id FROM accesorios WHERE codigo_serie = ?");
            $stmt->execute([$codigo_serie]);
            
            if ($stmt->rowCount() > 0) {
                $mensaje = 'Ya existe un accesorio con ese código de serie.';
                $tipo_mensaje = 'error';
            } else {
                // Insertar accesorio
                $stmt = $conn->prepare("INSERT INTO accesorios (nombre, estado, codigo_serie, observaciones) VALUES (?, ?, ?, ?)");
                
                if ($stmt->execute([$nombre, $estado, $codigo_serie, $observaciones])) {
                    header('Location: index.php?success=' . urlencode('Accesorio creado exitosamente'));
                    exit;
                }
            }
        } catch(PDOException $e) {
            $mensaje = 'Error al crear el accesorio: ' . $e->getMessage();
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
        <h2>Crear Nuevo Accesorio</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST">
        <div class="form-row">
            <div class="form-group">
                <label for="nombre">Nombre del Accesorio *</label>
                <input type="text" id="nombre" name="nombre" class="form-control" 
                       value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>" 
                       placeholder="Ej: Boquilla, Atril, Baquetas" required>
            </div>
            <div class="form-group">
                <label for="estado">Estado *</label>
                <select id="estado" name="estado" class="form-control" required>
                    <option value="">Seleccionar estado</option>
                    <option value="Disponible" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Disponible') ? 'selected' : ''; ?>>Disponible</option>
                    <option value="Dañado" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'Dañado') ? 'selected' : ''; ?>>Dañado</option>
                    <option value="En reparación" <?php echo (isset($_POST['estado']) && $_POST['estado'] == 'En reparación') ? 'selected' : ''; ?>>En reparación</option>
                    <!-- 'Prestado' no se permite directamente al crear -->
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="codigo_serie">Código o Número de Serie *</label>
            <input type="text" id="codigo_serie" name="codigo_serie" class="form-control" 
                   value="<?php echo isset($_POST['codigo_serie']) ? htmlspecialchars($_POST['codigo_serie']) : ''; ?>" 
                   placeholder="Ej: BQ001, ATRIL001" required>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Observaciones adicionales sobre el accesorio..."><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Crear Accesorio</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
