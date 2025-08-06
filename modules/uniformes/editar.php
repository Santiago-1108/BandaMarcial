<?php
$base_url = '../../';
$page_title = 'Editar Uniforme';
$current_module = 'uniformes';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();
$prestado_a_estudiante = null; // Variable para almacenar el nombre del estudiante si está prestado

// Obtener ID del uniforme
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del uniforme
try {
    $stmt = $conn->prepare("SELECT * FROM uniformes WHERE id = ?");
    $stmt->execute([$id]);
    $uniforme = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$uniforme) {
        header('Location: index.php?error=' . urlencode('Uniforme no encontrado.'));
        exit;
    }

    // Si el uniforme está prestado, obtener información del préstamo activo
    if ($uniforme['estado'] == 'Prestado') {
        $stmt_loan = $conn->prepare("
            SELECT e.nombre_completo, p.id as prestamo_id
            FROM prestamo_items pi
            JOIN prestamos p ON pi.prestamo_id = p.id
            JOIN estudiantes e ON p.estudiante_id = e.id
            WHERE pi.tipo_item = 'uniforme' AND pi.item_id = ? AND p.estado = 'Activo' AND pi.cantidad_devuelta < pi.cantidad_prestada
            LIMIT 1
        ");
        $stmt_loan->execute([$id]);
        $prestamo_info = $stmt_loan->fetch(PDO::FETCH_ASSOC);
        if ($prestamo_info) {
            $prestado_a_estudiante = $prestamo_info;
        }
    }

} catch(PDOException $e) {
    header('Location: index.php?error=' . urlencode('Error al obtener el uniforme.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo = $_POST['tipo'];
    $talla = trim($_POST['talla']);
    $codigo_serie = trim($_POST['codigo_serie']);
    $estado = $_POST['estado'];
    $observaciones = trim($_POST['observaciones']);

    // Validaciones
    if (empty($tipo) || empty($codigo_serie) || empty($estado)) {
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('Por favor complete todos los campos obligatorios.'));
        exit;
    }
    
    if ($tipo == 'Chaleco' && empty($talla)) {
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('La talla es obligatoria para los chalecos.'));
        exit;
    }
    if ($tipo == 'Boina' && (!is_numeric($talla) || $talla < 1 || $talla > 10)) {
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('La talla para boinas debe ser un número entre 1 y 10.'));
        exit;
    }

    try {
        // Verificar si el nuevo código de serie ya existe para otro ID
        $stmt_check_duplicate = $conn->prepare("SELECT id FROM uniformes WHERE codigo_serie = ? AND id != ?");
        $stmt_check_duplicate->execute([$codigo_serie, $id]);
        if ($stmt_check_duplicate->rowCount() > 0) {
            header('Location: editar.php?id=' . $id . '&error=' . urlencode('Ya existe un uniforme con el mismo código de serie.'));
            exit;
        }

        // Validaciones de estado:
        if ($uniforme['estado'] == 'Prestado' && $estado != 'Prestado') {
            // Si el uniforme estaba prestado y se intenta cambiar su estado a algo diferente de "Prestado"
            header('Location: editar.php?id=' . $id . '&error=' . urlencode('No se puede cambiar el estado de un uniforme que está actualmente prestado. Debe ser devuelto a través del módulo de Préstamos.'));
            exit;
        } elseif ($uniforme['estado'] != 'Prestado' && $estado == 'Prestado') {
            // Si el uniforme NO estaba prestado y se intenta cambiar su estado a "Prestado"
            header('Location: editar.php?id=' . $id . '&error=' . urlencode('No se puede cambiar el estado de un uniforme a "Prestado" directamente. Los uniformes se marcan como prestados al crear un nuevo préstamo en el módulo de Préstamos.'));
            exit;
        }

        // Actualizar uniforme
        $stmt = $conn->prepare("UPDATE uniformes SET tipo = ?, talla = ?, codigo_serie = ?, estado = ?, observaciones = ? WHERE id = ?");
        if ($stmt->execute([$tipo, $talla, $codigo_serie, $estado, $observaciones, $id])) {
            // Actualizar datos mostrados
            $uniforme['tipo'] = $tipo;
            $uniforme['talla'] = $talla;
            $uniforme['codigo_serie'] = $codigo_serie;
            $uniforme['estado'] = $estado;
            $uniforme['observaciones'] = $observaciones;

            header('Location: index.php?success=' . urlencode('Uniforme actualizado exitosamente.'));
            exit;
        }
    } catch(PDOException $e) {
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('Error al actualizar el uniforme: ' . $e->getMessage()));
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
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Editar Uniforme</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="form-uniforme">
        <div class="form-row">
            <div class="form-group">
                <label for="tipo">Tipo de Uniforme *</label>
                <select id="tipo" name="tipo" class="form-control" required onchange="toggleTallaField()">
                    <option value="">Seleccionar tipo</option>
                    <option value="Chaleco" <?php echo $uniforme['tipo'] == 'Chaleco' ? 'selected' : ''; ?>>Chaleco</option>
                    <option value="Boina" <?php echo $uniforme['tipo'] == 'Boina' ? 'selected' : ''; ?>>Boina</option>
                </select>
            </div>
            <div class="form-group">
                <label for="codigo_serie">Código de Serie *</label>
                <input type="text" id="codigo_serie" name="codigo_serie" class="form-control" 
                       value="<?php echo htmlspecialchars($uniforme['codigo_serie']); ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" id="talla-group" style="display: <?php echo $uniforme['tipo'] == 'Chaleco' || $uniforme['tipo'] == 'Boina' ? 'block' : 'none'; ?>;">
                <label for="talla">Talla *</label>
                <select id="talla" name="talla" class="form-control" data-current-talla="<?php echo htmlspecialchars($uniforme['talla']); ?>">
                    <option value="">Seleccionar talla</option>
                    <!-- Opciones de talla se cargarán con JavaScript -->
                </select>
            </div>
            <div class="form-group">
                <label for="estado">Estado *</label>
                <select id="estado" name="estado" class="form-control" required <?php echo ($uniforme['estado'] == 'Prestado') ? 'disabled' : ''; ?>>
                    <option value="">Seleccionar estado</option>
                    <option value="Disponible" <?php echo $uniforme['estado'] == 'Disponible' ? 'selected' : ''; ?>>Disponible</option>
                    <option value="Prestado" <?php echo $uniforme['estado'] == 'Prestado' ? 'selected' : ''; ?>>Prestado</option>
                    <option value="Dañado" <?php echo $uniforme['estado'] == 'Dañado' ? 'selected' : ''; ?>>Dañado</option>
                    <option value="En reparación" <?php echo $uniforme['estado'] == 'En reparación' ? 'selected' : ''; ?>>En reparación</option>
                </select>
                <?php if ($uniforme['estado'] == 'Prestado'): ?>
                    <small class="text-muted">El estado no puede ser cambiado mientras el uniforme esté prestado.</small>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Observaciones adicionales sobre el uniforme..."><?php echo htmlspecialchars($uniforme['observaciones']); ?></textarea>
        </div>

        <?php if ($prestado_a_estudiante): ?>
        <div class="card" style="margin-top: 20px; background-color: #e6f7ff; border-color: #91d5ff;">
            <h3>Información de Préstamo Activo</h3>
            <p>Este uniforme está actualmente prestado a: <strong><?php echo htmlspecialchars($prestado_a_estudiante['nombre_completo']); ?></strong></p>
            <p>Ver detalles del préstamo: <a href="../prestamos/ver.php?id=<?php echo $prestado_a_estudiante['prestamo_id']; ?>" class="btn btn-secondary btn-small">Ver Préstamo #<?php echo $prestado_a_estudiante['prestamo_id']; ?></a></p>
        </div>
        <?php endif; ?>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Actualizar Uniforme</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar el campo de talla al cargar la página
    toggleTallaField();
});
</script>

<?php require_once '../../includes/footer.php'; ?>
