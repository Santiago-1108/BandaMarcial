<?php
$base_url = '../../';
$page_title = 'Registrar Estudiante';
$current_module = 'estudiantes';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre_completo = trim($_POST['nombre_completo']);
    $grado = trim($_POST['grado']);
    $documento_identidad = trim($_POST['documento_identidad']);
    
    // Validaciones
    if (empty($nombre_completo) || empty($grado) || empty($documento_identidad)) {
        header('Location: crear.php?error=' . urlencode('Por favor complete todos los campos obligatorios.'));
        exit;
    }
    
    try {
        // Verificar que el documento no exista
        $stmt = $conn->prepare("SELECT id FROM estudiantes WHERE documento_identidad = ?");
        $stmt->execute([$documento_identidad]);
        
        if ($stmt->rowCount() > 0) {
            header('Location: crear.php?error=' . urlencode('Ya existe un estudiante con ese documento de identidad.'));
            exit;
        }
        
        // Insertar estudiante
        $stmt = $conn->prepare("INSERT INTO estudiantes (nombre_completo, grado, documento_identidad) VALUES (?, ?, ?)");
        if ($stmt->execute([$nombre_completo, $grado, $documento_identidad])) {
            header('Location: index.php?success=' . urlencode('Estudiante registrado exitosamente.'));
            exit;
        }
    } catch(PDOException $e) {
        header('Location: crear.php?error=' . urlencode('Error al registrar el estudiante: ' . $e->getMessage()));
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
        <h2>Registrar Nuevo Estudiante</h2>
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
                       placeholder="Nombre completo del estudiante" required>
            </div>
            <div class="form-group">
                <label for="grado">Grado *</label>
                <select id="grado" name="grado" class="form-control" required>
                    <option value="">Seleccionar grado</option>
                    <option value="5°">5°</option>
                    <option value="6°">6°</option>
                    <option value="7°">7°</option>
                    <option value="8°">8°</option>
                    <option value="9°">9°</option>
                    <option value="10°">10°</option>
                    <option value="11°">11°</option>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="documento_identidad">Documento de Identidad o Número Escolar *</label>
            <input type="text" id="documento_identidad" name="documento_identidad" class="form-control" 
                   placeholder="Documento de identidad o número escolar" required>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Registrar Estudiante</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
