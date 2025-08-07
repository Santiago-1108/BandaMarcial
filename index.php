<?php
$base_url = './';
$page_title = 'Dashboard';
$current_module = 'dashboard'; // Definir el módulo actual
require_once 'config/database.php';
require_once 'includes/header.php';

// Obtener estadísticas del sistema
$conn = getDBConnection();

try {
    // Contar instrumentos por estado
    $stmt = $conn->query("SELECT estado, COUNT(*) as total FROM instrumentos GROUP BY estado");
    $instrumentos_stats_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Inicializar estadísticas de instrumentos para asegurar que todos los estados aparezcan
    $instrumentos_stats = [
        'Disponible' => 0,
        'Prestado' => 0,
        'Dañado' => 0,
        'En reparación' => 0
    ];
    foreach ($instrumentos_stats_raw as $estado => $cantidad) {
        $instrumentos_stats[$estado] = $cantidad;
    }
    $total_instrumentos = array_sum($instrumentos_stats); // Calcular el total de instrumentos

    // Contar uniformes por estado
    $stmt = $conn->query("SELECT estado, COUNT(*) as total FROM uniformes GROUP BY estado");
    $uniformes_stats_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Inicializar estadísticas de uniformes para asegurar que todos los estados aparezcan
    $uniformes_stats = [
        'Disponible' => 0,
        'Prestado' => 0,
        'Dañado' => 0,
        'En reparación' => 0
    ];
    foreach ($uniformes_stats_raw as $estado => $cantidad) {
        $uniformes_stats[$estado] = $cantidad;
    }
    $total_uniformes = array_sum($uniformes_stats);

    // Contar accesorios por estado
    $stmt = $conn->query("SELECT estado, COUNT(*) as total FROM accesorios GROUP BY estado");
    $accesorios_stats_raw = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Inicializar estadísticas de accesorios
    $accesorios_stats = [
        'Disponible' => 0,
        'Prestado' => 0,
        'Dañado' => 0,
        'En reparación' => 0
    ];
    foreach ($accesorios_stats_raw as $estado => $cantidad) {
        $accesorios_stats[$estado] = $cantidad;
    }
    $total_accesorios = array_sum($accesorios_stats);

    // NUEVO: Contar repuestos (total disponible)
    $stmt = $conn->query("SELECT SUM(cantidad_disponible) FROM repuestos");
    $total_repuestos_disponibles = $stmt->fetchColumn() ?? 0;
    
    // NUEVO: Contar instrumentos por nombre
    $stmt_instrumentos_por_nombre = $conn->query("SELECT nombre, COUNT(*) as total FROM instrumentos GROUP BY nombre ORDER BY nombre");
    $instrumentos_por_nombre = $stmt_instrumentos_por_nombre->fetchAll(PDO::FETCH_ASSOC);

    // NUEVO: Contar uniformes por tipo
    $stmt_uniformes_por_tipo = $conn->query("SELECT tipo, COUNT(*) as total FROM uniformes GROUP BY tipo ORDER BY tipo");
    $uniformes_por_tipo = $stmt_uniformes_por_tipo->fetchAll(PDO::FETCH_ASSOC);

    // NUEVO: Contar accesorios por nombre
    $stmt_accesorios_por_nombre = $conn->query("SELECT nombre, COUNT(*) as total FROM accesorios GROUP BY nombre ORDER BY nombre");
    $accesorios_por_nombre = $stmt_accesorios_por_nombre->fetchAll(PDO::FETCH_ASSOC);

    // NUEVO: Contar repuestos por nombre
    $stmt_repuestos_por_nombre = $conn->query("SELECT nombre, cantidad_disponible FROM repuestos ORDER BY nombre");
    $repuestos_por_nombre = $stmt_repuestos_por_nombre->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar estudiantes
    $stmt = $conn->query("SELECT COUNT(*) FROM estudiantes");
    $total_estudiantes = $stmt->fetchColumn();
    
    // Contar préstamos activos
    $stmt = $conn->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'Activo'");
    $prestamos_activos = $stmt->fetchColumn();
    
    // Contar préstamos vencidos
    $stmt = $conn->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'Vencido'");
    $prestamos_vencidos = $stmt->fetchColumn();
    
    // Obtener préstamos próximos a vencer (en los próximos 7 días)
    $stmt = $conn->prepare("
        SELECT p.*, e.nombre_completo 
        FROM prestamos p 
        JOIN estudiantes e ON p.estudiante_id = e.id 
        WHERE p.estado = 'Activo' 
        AND p.fecha_devolucion_esperada BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY p.fecha_devolucion_esperada ASC
        LIMIT 5
    ");
    $stmt->execute();
    $proximos_vencer = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<div class="main-content">
    <h2>Dashboard - Resumen del Sistema</h2>
    
    <!-- Estadísticas principales -->
    <div class="stats-grid">
        <div class="stat-card">
            <h3><?php echo $total_estudiantes; ?></h3>
            <p>Estudiantes Registrados</p>
        </div>
        <div class="stat-card" style="border-color: #28a745;">
            <h3><?php echo $total_instrumentos; ?></h3>
            <p>Total Instrumentos</p>
        </div>
        <div class="stat-card" style="border-color: #ff6a07ff;">
            <h3><?php echo $total_uniformes; ?></h3>
            <p>Total Uniformes</p>
        </div>
        <div class="stat-card" style="border-color: #9b59b6;">
            <h3><?php echo $total_accesorios; ?></h3>
            <p>Total Accesorios</p>
        </div>
        <div class="stat-card" style="border-color: #3498db;">
            <h3><?php echo $total_repuestos_disponibles; ?></h3>
            <p>Repuestos Disponibles</p>
        </div>
        <div class="stat-card" style="border-color: #d32828ff;">
            <h3><?php echo $prestamos_activos; ?></h3>
            <p>Préstamos Activos</p>
        </div>
    </div>
    
    <?php if ($prestamos_vencidos > 0): ?>
    <div class="alert alert-warning">
        <strong>⚠️ Atención:</strong> Hay <?php echo $prestamos_vencidos; ?> préstamo(s) vencido(s) que requieren atención.
        <a href="modules/prestamos/index.php?filtro=vencidos" class="btn btn-warning" style="margin-left: 10px;">Ver Préstamos Vencidos</a>
    </div>
    <?php endif; ?>
    
    <!-- Nueva distribución de secciones con colapsables -->
    <div class="dashboard-sections-grid">
        <!-- Sección de Instrumentos -->
        <div class="instrument-section">
            <div class="card">
                <button class="collapsible-toggle" data-target="instrument-status-content">
                    <h3>Estado de Instrumentos <span class="toggle-icon">▼</span></h3>
                </button>
                <div id="instrument-status-content" class="collapsible-content">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instrumentos_stats as $estado => $cantidad): ?>
                                <tr>
                                    <td><span class="estado estado-<?php echo strtolower(str_replace(' ', '-', $estado)); ?>"><?php echo $estado; ?></span></td>
                                    <td><?php echo $cantidad; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <button class="collapsible-toggle" data-target="instrument-type-content">
                    <h3>Total de Instrumentos por Tipo <span class="toggle-icon">▼</span></h3>
                </button>
                <div id="instrument-type-content" class="collapsible-content">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo de Instrumento</th>
                                    <th>Cantidad Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($instrumentos_por_nombre)): ?>
                                <tr>
                                    <td colspan="2" class="no-results-cell">No hay instrumentos registrados.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($instrumentos_por_nombre as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($item['total']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección de Uniformes -->
        <div class="uniform-section">
            <div class="card">
                <button class="collapsible-toggle" data-target="uniform-status-content">
                    <h3>Estado de Uniformes <span class="toggle-icon">▼</span></h3>
                </button>
                <div id="uniform-status-content" class="collapsible-content">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($uniformes_stats as $estado => $cantidad): ?>
                                <tr>
                                    <td><span class="estado estado-<?php echo strtolower(str_replace(' ', '-', $estado)); ?>"><?php echo $estado; ?></span></td>
                                    <td><?php echo $cantidad; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card">
                <button class="collapsible-toggle" data-target="uniform-type-content">
                    <h3>Total de Uniformes por Tipo <span class="toggle-icon">▼</span></h3>
                </button>
                <div id="uniform-type-content" class="collapsible-content">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo de Uniforme</th>
                                    <th>Cantidad Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($uniformes_por_tipo)): ?>
                                <tr>
                                    <td colspan="2" class="no-results-cell">No hay uniformes registrados.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($uniformes_por_tipo as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['tipo']); ?></td>
                                        <td><?php echo htmlspecialchars($item['total']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Accesorios -->
        <div class="accesorio-section">
            <div class="card">
                <button class="collapsible-toggle" data-target="accesorio-status-content">
                    <h3>Estado de Accesorios <span class="toggle-icon">▼</span></h3>
                </button>
                <div id="accesorio-status-content" class="collapsible-content">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Estado</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accesorios_stats as $estado => $cantidad): ?>
                                <tr>
                                    <td><span class="estado estado-<?php echo strtolower(str_replace(' ', '-', $estado)); ?>"><?php echo $estado; ?></span></td>
                                    <td><?php echo $cantidad; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <button class="collapsible-toggle" data-target="accesorio-type-content">
                    <h3>Total de Accesorios por Tipo <span class="toggle-icon">▼</span></h3>
                </button>
                <div id="accesorio-type-content" class="collapsible-content">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Tipo de Accesorio</th>
                                    <th>Cantidad Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accesorios_por_nombre)): ?>
                                <tr>
                                    <td colspan="2" class="no-results-cell">No hay accesorios registrados.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($accesorios_por_nombre as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($item['total']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de Repuestos (NUEVA) -->
        <div class="repuesto-section">
            <div class="card">
                <button class="collapsible-toggle" data-target="repuesto-summary-content">
                    <h3>Resumen de Repuestos <span class="toggle-icon">▼</span></h3>
                </button>
                <div id="repuesto-summary-content" class="collapsible-content">
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Repuesto</th>
                                    <th>Cantidad Disponible</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($repuestos_por_nombre)): ?>
                                <tr>
                                    <td colspan="2" class="no-results-cell">No hay repuestos registrados.</td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($repuestos_por_nombre as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['nombre']); ?></td>
                                        <td><?php echo htmlspecialchars($item['cantidad_disponible']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Préstamos próximos a vencer -->
    <?php if (!empty($proximos_vencer)): ?>
    <div class="card" style="margin-top: 15px;">
        <button class="collapsible-toggle" data-target="upcoming-loans-content">
            <h3>Préstamos Próximos a Vencer (Próximos 7 días) <span class="toggle-icon">▼</span></h3>
        </button>
        <div id="upcoming-loans-content" class="collapsible-content">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Estudiante</th>
                            <th>Fecha Préstamo</th>
                            <th>Fecha Devolución</th>
                            <th>Días Restantes</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($proximos_vencer as $prestamo): 
                            $dias_restantes = (strtotime($prestamo['fecha_devolucion_esperada']) - strtotime(date('Y-m-d'))) / (60 * 60 * 24);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($prestamo['nombre_completo']); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_prestamo'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($prestamo['fecha_devolucion_esperada'])); ?></td>
                            <td>
                                <span class="<?php echo $dias_restantes <= 2 ? 'estado-vencido' : 'estado-prestado'; ?>" style="padding: 2px 8px; border-radius: 10px;">
                                    <?php echo max(0, ceil($dias_restantes)); ?> días
                                </span>
                            </td>
                            <td>
                                <a href="modules/prestamos/ver.php?id=<?php echo $prestamo['id']; ?>" class="btn btn-secondary">Ver Detalles</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Accesos rápidos -->
    <div class="card" style="margin-top: 15px;">
        <h3>Accesos Rápidos</h3>
        <div class="btn-group">
            <a href="modules/prestamos/crear.php" class="btn btn-success">Nuevo Préstamo</a>
            <a href="modules/estudiantes/crear.php" class="btn btn-success">Registrar Estudiante</a>
            <a href="modules/instrumentos/crear.php" class="btn btn-success">Agregar Instrumento</a>
            <a href="modules/uniformes/crear.php" class="btn btn-success">Agregar Uniforme</a>
            <a href="modules/accesorios/crear.php" class="btn btn-success">Agregar Accesorio</a>
            <a href="modules/repuestos/crear.php" class="btn btn-success">Agregar Repuesto</a>
            <a href="modules/prestamos/index.php?filtro=activos" class="btn">Ver Préstamos Activos</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
