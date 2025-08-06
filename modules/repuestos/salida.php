<?php
$base_url = '../../';
$page_title = 'Registrar Salida de Repuesto';
$current_module = 'repuestos';
require_once '../../config/database.php';
require_once '../../includes/header.php';

$conn = getDBConnection();
$mensaje = '';
$tipo_mensaje = '';

// Obtener ID del repuesto si viene de la URL
$repuesto_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$repuesto = null;

if ($repuesto_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM repuestos WHERE id = ?");
        $stmt->execute([$repuesto_id]);
        $repuesto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$repuesto) {
            header('Location: index.php?error=' . urlencode('Repuesto no encontrado.'));
            exit;
        }
    } catch (PDOException $e) {
        header('Location: index.php?error=' . urlencode('Error al cargar el repuesto: ' . $e->getMessage()));
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $repuesto_id_post = (int)$_POST['repuesto_id'];
    $cantidad = (int)$_POST['cantidad'];
    $instrumento_id = isset($_POST['instrumento_id']) && $_POST['instrumento_id'] !== '' ? (int)$_POST['instrumento_id'] : null;
    $observaciones = trim($_POST['observaciones']);

    // Validaciones
    if (empty($repuesto_id_post) || $cantidad <= 0) {
        $mensaje = 'Por favor seleccione un repuesto y una cantidad válida.';
        $tipo_mensaje = 'error';
    } else {
        try {
            $conn->beginTransaction();

            // Obtener cantidad disponible actual
            $stmt = $conn->prepare("SELECT cantidad_disponible FROM repuestos WHERE id = ? FOR UPDATE");
            $stmt->execute([$repuesto_id_post]);
            $current_cantidad = $stmt->fetchColumn();

            if ($current_cantidad === false) {
                $conn->rollback();
                $mensaje = 'Repuesto no encontrado.';
                $tipo_mensaje = 'error';
            } elseif ($current_cantidad < $cantidad) {
                $conn->rollback();
                $mensaje = 'No hay suficiente cantidad disponible de este repuesto. Cantidad actual: ' . $current_cantidad;
                $tipo_mensaje = 'error';
            } else {
                // Actualizar cantidad disponible
                $stmt = $conn->prepare("UPDATE repuestos SET cantidad_disponible = cantidad_disponible - ? WHERE id = ?");
                $stmt->execute([$cantidad, $repuesto_id_post]);

                // Registrar movimiento de salida
                $stmt = $conn->prepare("INSERT INTO movimientos_repuestos (repuesto_id, tipo_movimiento, cantidad, instrumento_id, observaciones) VALUES (?, 'salida', ?, ?, ?)");
                $stmt->execute([$repuesto_id_post, $cantidad, $instrumento_id, $observaciones]);

                $conn->commit();
                header('Location: index.php?success=' . urlencode('Salida de repuesto registrada exitosamente.'));
                exit;
            }
        } catch(PDOException $e) {
            $conn->rollback();
            $mensaje = 'Error al registrar la salida: ' . $e->getMessage();
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

// Obtener lista de repuestos para el select
try {
    $stmt_repuestos = $conn->query("SELECT id, nombre, cantidad_disponible FROM repuestos ORDER BY nombre");
    $lista_repuestos = $stmt_repuestos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener lista de instrumentos para el select
    $stmt_instrumentos = $conn->query("SELECT id, nombre, codigo_serie FROM instrumentos ORDER BY nombre, codigo_serie");
    $lista_instrumentos = $stmt_instrumentos->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo "Error al cargar datos: " . $e->getMessage();
    $lista_repuestos = [];
    $lista_instrumentos = [];
}
?>

<div class="main-content">
    <div class="header-actions">
        <h2>Registrar Salida de Repuesto</h2>
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">Volver a Lista</a>
            <a href="movimientos.php" class="btn btn-secondary">Ver Movimientos</a>
        </div>
    </div>
    
    <?php if ($mensaje): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php echo $mensaje; ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="form-salida-repuesto">
        <div class="form-group">
            <label for="repuesto_id">Repuesto *</label>
            <select id="repuesto_id" name="repuesto_id" class="form-control" required>
                <option value="">Seleccionar repuesto</option>
                <?php foreach ($lista_repuestos as $item): ?>
                <option value="<?php echo $item['id']; ?>" 
                        data-cantidad-disponible="<?php echo $item['cantidad_disponible']; ?>"
                        <?php echo ($repuesto_id == $item['id'] || (isset($_POST['repuesto_id']) && $_POST['repuesto_id'] == $item['id'])) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($item['nombre'] . ' (Disp: ' . $item['cantidad_disponible'] . ')'); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="cantidad">Cantidad a Retirar *</label>
            <input type="number" id="cantidad" name="cantidad" class="form-control" 
                   value="<?php echo isset($_POST['cantidad']) ? htmlspecialchars($_POST['cantidad']) : '1'; ?>" 
                   min="1" required>
            <small id="cantidad-disponible-msg" class="text-muted"></small>
        </div>

        <div class="form-group">
            <label for="instrumento_id">Instrumento Asociado (Opcional)</label>
            <select id="instrumento_id" name="instrumento_id" class="form-control">
                <option value="">Ninguno</option>
                <?php foreach ($lista_instrumentos as $instrumento): ?>
                <option value="<?php echo $instrumento['id']; ?>" <?php echo (isset($_POST['instrumento_id']) && $_POST['instrumento_id'] == $instrumento['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($instrumento['nombre'] . ' - ' . $instrumento['codigo_serie']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            <small class="text-muted">Seleccione el instrumento al que se le aplicó este repuesto.</small>
        </div>
        
        <div class="form-group">
            <label for="observaciones">Observaciones</label>
            <textarea id="observaciones" name="observaciones" class="form-control" rows="3" 
                      placeholder="Motivo de la salida, detalles de la reparación, etc."><?php echo isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : ''; ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">Registrar Salida</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const repuestoSelect = document.getElementById('repuesto_id');
    const cantidadInput = document.getElementById('cantidad');
    const cantidadMsg = document.getElementById('cantidad-disponible-msg');

    function updateCantidadMsg() {
        const selectedOption = repuestoSelect.options[repuestoSelect.selectedIndex];
        const disponible = selectedOption.dataset.cantidadDisponible;
        if (disponible !== undefined) {
            cantidadMsg.textContent = `Cantidad disponible: ${disponible}`;
            cantidadInput.max = disponible; // Establecer el máximo del input
        } else {
            cantidadMsg.textContent = '';
            cantidadInput.removeAttribute('max');
        }
    }

    repuestoSelect.addEventListener('change', updateCantidadMsg);
    // Initial call to set the message if a repuesto is pre-selected (e.g., after a failed submission)
    updateCantidadMsg();
});
</script>

<?php require_once '../../includes/footer.php'; ?>
