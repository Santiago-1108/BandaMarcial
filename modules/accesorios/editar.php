<?php
$base_url = '../../';
$page_title = 'Editar Accesorio';
$current_module = 'accesorios';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();
$mensaje = '';
$tipo_mensaje = '';
$prestado_a_estudiante = null; // Variable para almacenar el nombre del estudiante si está prestado

// Obtener ID del accesorio
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del accesorio
try {
    $stmt = $conn->prepare("SELECT * FROM accesorios WHERE id = ?");
    $stmt->execute([$id]);
    $accesorio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$accesorio) {
        header('Location: index.php?error=' . urlencode('Accesorio no encontrado.'));
        exit;
    }

    // Si el accesorio está prestado, obtener información del préstamo activo
    if ($accesorio['estado'] == 'Prestado') {
        $stmt_loan = $conn->prepare("
            SELECT e.nombre_completo, p.id as prestamo_id
            FROM prestamo_items pi
            JOIN prestamos p ON pi.prestamo_id = p.id
            JOIN estudiantes e ON p.estudiante_id = e.id
            WHERE pi.tipo_item = 'accesorio' AND pi.item_id = ? AND p.estado = 'Activo' AND pi.cantidad_devuelta < pi.cantidad_prestada
            LIMIT 1
        ");
        $stmt_loan->execute([$id]);
        $prestamo_info = $stmt_loan->fetch(PDO::FETCH_ASSOC);
        if ($prestamo_info) {
            $prestado_a_estudiante = $prestamo_info;
        }
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $estado = $_POST['estado'];
    $codigo_serie = trim($_POST['codigo_serie']);
    $observaciones = trim($_POST['observaciones']);
    
    // Validaciones
    if (empty($nombre) || empty($estado) || empty($codigo_serie)) {
        $mensaje = 'Por favor complete todos los campos obligatorios.';
        $tipo_mensaje = 'error';
    } elseif ($accesorio['estado'] == 'Prestado' && $estado != 'Prestado') {
        // Si el accesorio estaba prestado y se intenta cambiar su estado a algo diferente de "Prestado"
        $mensaje = 'No se puede cambiar el estado de un accesorio que está actualmente prestado. Debe ser devuelto a través del módulo de Préstamos.';
        $tipo_mensaje = 'error';
    } elseif ($accesorio['estado'] != 'Prestado' && $estado == 'Prestado') {
        // Si el accesorio NO estaba prestado y se intenta cambiar su estado a "Prestado"
        $mensaje = 'No se puede cambiar el estado de un accesorio a "Prestado" directamente. Los accesorios se marcan como prestados al crear un nuevo préstamo en el módulo de Préstamos.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar que el código de serie no exista en otro accesorio
            $stmt = $conn->prepare("SELECT id FROM accesorios WHERE codigo_serie = ? AND id != ?");
            $stmt->execute([$codigo_serie, $id]);
            
            if ($stmt->rowCount() > 0) {
                $mensaje = 'Ya existe otro accesorio con ese código de serie.';
                $tipo_mensaje = 'error';
            } else {
                // Actualizar accesorio
                $stmt = $conn->prepare("UPDATE accesorios SET nombre = ?, estado = ?, codigo_serie = ?, observaciones = ? WHERE id = ?");
                $stmt->execute([$nombre, $estado, $codigo_serie, $observaciones, $id]);
                
                $mensaje = 'Accesorio actualizado exitosamente.';
                $tipo_mensaje = 'success';
                
                // Actualizar datos mostrados
                $accesorio['nombre'] = $nombre;
                $accesorio['estado'] = $estado;
                $accesorio['codigo_serie'] = $codigo_serie;
                $accesorio['observaciones'] = $observaciones;
                
                // Redirigir después de actualizar exitosamente
                header('Location: index.php?success=' . urlencode('Accesorio actualizado exitosamente.'));
                exit;
            }
        } catch(PDOException $e) {
            $mensaje = 'Error al actualizar el accesorio: ' . $e->getMessage();
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
        <h2>Editar Accesorio</h2>
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
                       value="<?php echo htmlspecialchars($accesorio['nombre']); ?>" 
                       placeholder="Ej: Boquilla, Atril, Baquetas" required>
            </div>
            <div class="form-group">
                <label for="estado">Estado *</label>
                <select id="estado" name="estado" class="form-control" required <?php echo ($accesorio['estado'] == 'Prestado') ? 'disabled' : ''; ?>>
                    <option value="">Seleccionar estado</option>
                    <option value="Disponible" <?php echo $accesorio['estado'] == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                    <option value="Prestado" <?php echo $accesorio['estado'] == 'Prestado' ? 'selected' : ''; ?>>Prestado</option>
                    <option value="Dañado" <?php echo $accesorio['estado'] == 'Dañado' ? 'selected' : ''; ?>>Dañado</option>
                    <option value="En reparación" <?php echo $accesorio['estado'] == 'En reparación' ? 'selected' : ''; ?>>En reparación</option>
                </select>
                <?php if ($accesorio['estado'] == 'Prestado'): ?>
                    <small class="text-muted">El estado no puede ser cambiado mientras el accesorio esté prestado.</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label for="codigo_serie">Código o Número de Serie *</label>
            <input type="text" id="codigo_serie" name="codigo_serie" class="form-control" 
                   value="<?php echo htmlspecialchars($accesorio['codigo_serie']); ?>" 
                   placeholder="Ej: BQ001, ATRIL001" required>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Observaciones adicionales sobre el accesorio..."><?php echo htmlspecialchars($accesorio['observaciones']); ?></textarea>
        </div>

        <?php if ($prestado_a_estudiante): ?>
        <div class="card" style="margin-top: 20px; background-color: var(--color-info-bg-light); border-color: var(--color-info-border-dark);">
            <h3>Información de Préstamo Activo</h3>
            <p>Este accesorio está actualmente prestado a: <strong><?php echo htmlspecialchars($prestado_a_estudiante['nombre_completo']); ?></strong></p>
            <p>Ver detalles del préstamo: <a href="../prestamos/ver.php?id=<?php echo $prestado_a_estudiante['prestamo_id']; ?>" class="btn btn-secondary btn-small">Ver Préstamo #<?php echo $prestado_a_estudiante['prestamo_id']; ?></a></p>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Actualizar Accesorio</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
