<?php
$base_url = '../../';
$page_title = 'Editar Instrumento';
$current_module = 'instrumentos';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();
$mensaje = '';
$tipo_mensaje = '';
$prestado_a_estudiante = null; // Variable para almacenar el nombre del estudiante si está prestado

// Obtener ID del instrumento
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del instrumento
try {
    $stmt = $conn->prepare("SELECT * FROM instrumentos WHERE id = ?");
    $stmt->execute([$id]);
    $instrumento = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$instrumento) {
        header('Location: index.php?error=' . urlencode('Instrumento no encontrado.'));
        exit;
    }

    // Si el instrumento está prestado, obtener información del préstamo activo
    if ($instrumento['estado'] == 'Prestado') {
        $stmt_loan = $conn->prepare("
            SELECT e.nombre_completo, p.id as prestamo_id
            FROM prestamo_items pi
            JOIN prestamos p ON pi.prestamo_id = p.id
            JOIN estudiantes e ON p.estudiante_id = e.id
            WHERE pi.tipo_item = 'instrumento' AND pi.item_id = ? AND p.estado = 'Activo' AND pi.cantidad_devuelta < pi.cantidad_prestada
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
    } elseif ($instrumento['estado'] == 'Prestado' && $estado != 'Prestado') {
        // Si el instrumento estaba prestado y se intenta cambiar su estado a algo diferente de "Prestado"
        $mensaje = 'No se puede cambiar el estado de un instrumento que está actualmente prestado. Debe ser devuelto a través del módulo de Préstamos.';
        $tipo_mensaje = 'error';
    } elseif ($instrumento['estado'] != 'Prestado' && $estado == 'Prestado') {
        // Si el instrumento NO estaba prestado y se intenta cambiar su estado a "Prestado"
        $mensaje = 'No se puede cambiar el estado de un instrumento a "Prestado" directamente. Los instrumentos se marcan como prestados al crear un nuevo préstamo en el módulo de Préstamos.';
        $tipo_mensaje = 'error';
    } else {
        try {
            // Verificar que el código de serie no exista en otro instrumento
            $stmt = $conn->prepare("SELECT id FROM instrumentos WHERE codigo_serie = ? AND id != ?");
            $stmt->execute([$codigo_serie, $id]);
            
            if ($stmt->rowCount() > 0) {
                $mensaje = 'Ya existe otro instrumento con ese código de serie.';
                $tipo_mensaje = 'error';
            } else {
                // Actualizar instrumento
                $stmt = $conn->prepare("UPDATE instrumentos SET nombre = ?, estado = ?, codigo_serie = ?, observaciones = ? WHERE id = ?");
                $stmt->execute([$nombre, $estado, $codigo_serie, $observaciones, $id]);
                
                $mensaje = 'Instrumento actualizado exitosamente.';
                $tipo_mensaje = 'success';
                
                // Actualizar datos mostrados
                $instrumento['nombre'] = $nombre;
                $instrumento['estado'] = $estado;
                $instrumento['codigo_serie'] = $codigo_serie;
                $instrumento['observaciones'] = $observaciones;
                
                // Redirigir después de actualizar exitosamente
                header('Location: index.php?success=' . urlencode('Instrumento actualizado exitosamente.'));
                exit;
            }
        } catch(PDOException $e) {
            $mensaje = 'Error al actualizar el instrumento: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Editar Instrumento</h2>
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
                <label for="nombre">Nombre del Instrumento *</label>
                <select id="nombre" name="nombre" class="form-control" required>
                    <option value="">Seleccionar instrumento</option>
                    <option value="Caja" <?php echo $instrumento['nombre'] == 'Caja' ? 'selected' : ''; ?>>Caja</option>
                    <option value="Bombo" <?php echo $instrumento['nombre'] == 'Bombo' ? 'selected' : ''; ?>>Bombo</option>
                    <option value="Redoblante" <?php echo $instrumento['nombre'] == 'Redoblante' ? 'selected' : ''; ?>>Redoblante</option>
                    <option value="Redoblante Mayor" <?php echo $instrumento['nombre'] == 'Redoblante Mayor' ? 'selected' : ''; ?>>Redoblante Mayor</option>
                    <option value="Lira" <?php echo $instrumento['nombre'] == 'Lira' ? 'selected' : ''; ?>>Lira</option>
                    <option value="Trompeta" <?php echo $instrumento['nombre'] == 'Trompeta' ? 'selected' : ''; ?>>Trompeta</option>
                    <option value="Granadera" <?php echo $instrumento['nombre'] == 'Granadera' ? 'selected' : ''; ?>>Granadera</option>
                    <option value="Bastón" <?php echo $instrumento['nombre'] == 'Bastón' ? 'selected' : ''; ?>>Bastón</option>
                    <option value="Platillos" <?php echo $instrumento['nombre'] == 'Platillos' ? 'selected' : ''; ?>>Platillos</option>
                    <option value="Otro" <?php echo !in_array($instrumento['nombre'], ['Caja', 'Bombo', 'Redoblante', 'Redoblante Mayor', 'Lira', 'Trompeta', 'Granadera', 'Bastón', 'Platillos']) ? 'selected' : ''; ?>>Otro</option>
                </select>
            </div>
            <div class="form-group">
                <label for="estado">Estado *</label>
                <select id="estado" name="estado" class="form-control" required <?php echo ($instrumento['estado'] == 'Prestado') ? 'disabled' : ''; ?>>
                    <option value="">Seleccionar estado</option>
                    <option value="Disponible" <?php echo $instrumento['estado'] == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                    <option value="Prestado" <?php echo $instrumento['estado'] == 'Prestado' ? 'selected' : ''; ?>>Prestado</option>
                    <option value="Dañado" <?php echo $instrumento['estado'] == 'Dañado' ? 'selected' : ''; ?>>Dañado</option>
                    <option value="En reparación" <?php echo $instrumento['estado'] == 'En reparación' ? 'selected' : ''; ?>>En reparación</option>
                </select>
                <?php if ($instrumento['estado'] == 'Prestado'): ?>
                    <small class="text-muted">El estado no puede ser cambiado mientras el instrumento esté prestado.</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label for="codigo_serie">Código o Número de Serie *</label>
            <input type="text" id="codigo_serie" name="codigo_serie" class="form-control" 
                   value="<?php echo htmlspecialchars($instrumento['codigo_serie']); ?>" 
                   placeholder="Ej: CAJA001, TROMP001, PLAT001" required>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Observaciones adicionales sobre el instrumento..."><?php echo htmlspecialchars($instrumento['observaciones']); ?></textarea>
        </div>

        <?php if ($prestado_a_estudiante): ?>
        <div class="card" style="margin-top: 20px; background-color: #e6f7ff; border-color: #91d5ff;">
            <h3>Información de Préstamo Activo</h3>
            <p>Este instrumento está actualmente prestado a: <strong><?php echo htmlspecialchars($prestado_a_estudiante['nombre_completo']); ?></strong></p>
            <p>Ver detalles del préstamo: <a href="../prestamos/ver.php?id=<?php echo $prestado_a_estudiante['prestamo_id']; ?>" class="btn btn-secondary btn-small">Ver Préstamo #<?php echo $prestado_a_estudiante['prestamo_id']; ?></a></p>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Actualizar Instrumento</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
