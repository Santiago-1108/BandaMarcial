<?php
$base_url = '../../';
$page_title = 'Crear Uniforme';
$current_module = 'uniformes';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tipo = $_POST['tipo'];
    $talla = trim($_POST['talla']);
    $codigo_serie = trim($_POST['codigo_serie']);
    $observaciones = trim($_POST['observaciones']);
    $estado = $_POST['estado']; // Ahora se selecciona el estado al crear
    
    // Validaciones
    if (empty($tipo) || empty($codigo_serie) || empty($estado)) {
        header('Location: crear.php?error=' . urlencode('Por favor complete el tipo, código de serie y estado.'));
        exit;
    }
    
    if ($tipo == 'Chaleco' && empty($talla)) {
        header('Location: crear.php?error=' . urlencode('La talla es obligatoria para los chalecos.'));
        exit;
    }
    if ($tipo == 'Boina' && (!is_numeric($talla) || $talla < 1 || $talla > 10)) {
        header('Location: crear.php?error=' . urlencode('La talla para boinas debe ser un número entre 1 y 10.'));
        exit;
    }
    
    // Nueva validación: no se puede crear un uniforme directamente como "Prestado"
    if ($estado == 'Prestado') {
        header('Location: crear.php?error=' . urlencode('No se puede crear un uniforme con estado "Prestado". Los uniformes deben estar "Disponibles" para ser prestados a través del módulo de Préstamos.'));
        exit;
    }

    try {
        // Verificar si ya existe un uniforme con el mismo código de serie
        $stmt = $conn->prepare("SELECT id FROM uniformes WHERE codigo_serie = ?");
        $stmt->execute([$codigo_serie]);
        
        if ($stmt->rowCount() > 0) {
            header('Location: crear.php?error=' . urlencode('Ya existe un uniforme con ese código de serie.'));
            exit;
        } else {
            // Insertar nuevo registro de uniforme individual
            $stmt = $conn->prepare("INSERT INTO uniformes (tipo, talla, codigo_serie, estado, observaciones) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$tipo, $talla, $codigo_serie, $estado, $observaciones])) {
                header('Location: index.php?success=' . urlencode('Uniforme creado exitosamente.'));
                exit;
            }
        }
    } catch(PDOException $e) {
        header('Location: crear.php?error=' . urlencode('Error al crear el uniforme: ' . $e->getMessage()));
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
        <h2>Crear Nuevo Uniforme</h2>
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
                    <option value="Chaleco" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Chaleco') ? 'selected' : ''; ?>>Chaleco</option>
                    <option value="Boina" <?php echo (isset($_POST['tipo']) && $_POST['tipo'] == 'Boina') ? 'selected' : ''; ?>>Boina</option>
                </select>
            </div>
            <div class="form-group">
                <label for="codigo_serie">Código de Serie *</label>
                <input type="text" id="codigo_serie" name="codigo_serie" class="form-control" 
                       value="<?php echo isset($_POST['codigo_serie']) ? htmlspecialchars($_POST['codigo_serie']) : ''; ?>" 
                       placeholder="Ej: CHALECOS001, BOINA005" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group" id="talla-group" style="display: none;">
                <label for="talla">Talla *</label>
                <select id="talla" name="talla" class="form-control">
                    <option value="">Seleccionar talla</option>
                    <!-- Opciones de talla se cargarán con JavaScript -->
                </select>
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
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Observaciones adicionales sobre el uniforme..."><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Crear Uniforme</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once '../../includes/footer.php'; ?>
