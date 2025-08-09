<?php
$base_url = '../../';
$page_title = 'Editar Estudiante';
$current_module = 'estudiantes';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();

// Obtener ID del estudiante
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del estudiante
try {
    $stmt = $conn->prepare("SELECT *, direccion, telefono FROM estudiantes WHERE id = ?"); // Incluir nuevos campos
    $stmt->execute([$id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$estudiante) {
        header('Location: index.php?error=' . urlencode('Estudiante no encontrado.'));
        exit;
    }
} catch(PDOException $e) {
    header('Location: index.php?error=' . urlencode('Error al obtener el estudiante.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_completo = trim($_POST['nombre_completo']);
    $grado = trim($_POST['grado']);
    $documento_identidad = trim($_POST['documento_identidad']);
    $direccion = trim($_POST['direccion'] ?? ''); // Nuevo campo
    $telefono = trim($_POST['telefono'] ?? '');   // Nuevo campo
    
    // Validaciones
    if (empty($nombre_completo) || empty($grado) || empty($documento_identidad)) {
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('Por favor complete todos los campos obligatorios.'));
        exit;
    }
    
    try {
        // Verificar que el documento no exista en otro estudiante
        $stmt = $conn->prepare("SELECT id FROM estudiantes WHERE documento_identidad = ? AND id != ?");
        $stmt->execute([$documento_identidad, $id]);
        
        if ($stmt->rowCount() > 0) {
            header('Location: editar.php?id=' . $id . '&error=' . urlencode('Ya existe otro estudiante con ese documento de identidad.'));
            exit;
        }
        
        // Actualizar estudiante con los nuevos campos
        $stmt = $conn->prepare("UPDATE estudiantes SET nombre_completo = ?, grado = ?, documento_identidad = ?, direccion = ?, telefono = ? WHERE id = ?");
        if ($stmt->execute([$nombre_completo, $grado, $documento_identidad, $direccion, $telefono, $id])) {
            // Actualizar datos mostrados (opcional, pero buena práctica si no se redirige inmediatamente)
            $estudiante['nombre_completo'] = $nombre_completo;
            $estudiante['grado'] = $grado;
            $estudiante['documento_identidad'] = $documento_identidad;
            $estudiante['direccion'] = $direccion;
            $estudiante['telefono'] = $telefono;

            header('Location: index.php?success=' . urlencode('Estudiante actualizado exitosamente.'));
            exit;
        }
    } catch(PDOException $e) {
        header('Location: editar.php?id=' . $id . '&error=' . urlencode('Error al actualizar el estudiante: ' . $e->getMessage()));
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
        <h2>Editar Estudiante</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo htmlspecialchars($mensaje); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="form-estudiante">
        <div class="form-row">
            <div class="form-group">
                <label for="nombre_completo">Nombre Completo *</label>
                <input type="text" id="nombre_completo" name="nombre_completo" class="form-control" 
                       value="<?php echo htmlspecialchars($estudiante['nombre_completo']); ?>"
                       placeholder="Nombre completo del estudiante" required>
            </div>
            <div class="form-group">
                <label for="grado">Grado *</label>
                <select id="grado" name="grado" class="form-control" required>
                    <option value="">Seleccionar grado</option>
                    <option value="5°" <?php echo $estudiante['grado'] == '5°' ? 'selected' : ''; ?>>5°</option>
                    <option value="6°" <?php echo $estudiante['grado'] == '6°' ? 'selected' : ''; ?>>6°</option>
                    <option value="7°" <?php echo $estudiante['grado'] == '7°' ? 'selected' : ''; ?>>7°</option>
                    <option value="8°" <?php echo $estudiante['grado'] == '8°' ? 'selected' : ''; ?>>8°</option>
                    <option value="9°" <?php echo $estudiante['grado'] == '9°' ? 'selected' : ''; ?>>9°</option>
                    <option value="10°" <?php echo $estudiante['grado'] == '10°' ? 'selected' : ''; ?>>10°</option>
                    <option value="11°" <?php echo $estudiante['grado'] == '11°' ? 'selected' : ''; ?>>11°</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="documento_identidad">Documento de Identidad o Número Escolar *</label>
            <input type="text" id="documento_identidad" name="documento_identidad" class="form-control" 
                   value="<?php echo htmlspecialchars($estudiante['documento_identidad']); ?>"
                   placeholder="Documento de identidad o número escolar" required>
        </div>

        <div class="form-group">
            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" class="form-control" 
                   value="<?php echo htmlspecialchars($estudiante['direccion'] ?? ''); ?>"
                   placeholder="Dirección de residencia del estudiante" required>
        </div>
        
        <div class="form-group">
            <label for="telefono">Número de Teléfono</label>
            <input type="text" id="telefono" name="telefono" class="form-control" 
                   value="<?php echo htmlspecialchars($estudiante['telefono'] ?? ''); ?>"
                   placeholder="Número de teléfono del estudiante" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Actualizar Estudiante</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
