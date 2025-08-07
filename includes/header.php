<?php
session_start(); // Iniciar la sesión en cada página

// Definir $base_url si no está ya definido (para páginas que no lo definen)
if (!isset($base_url)) {
    $base_url = './';
}

// Obtener el nombre del script actual
$current_page = basename($_SERVER['PHP_SELF']);

// Páginas que no requieren autenticación
$public_pages = ['login.php'];

// Verificar si el usuario está autenticado, excepto para las páginas públicas
if (!isset($_SESSION['user_id']) && !in_array($current_page, $public_pages)) {
    header('Location: ' . $base_url . 'login.php');
    exit;
}

// Si está logueado y en la página de login, redirigir al dashboard
if (isset($_SESSION['user_id']) && $current_page == 'login.php') {
    header('Location: ' . $base_url . 'index.php');
    exit;
}

// Incluir la configuración de la base de datos solo si es necesario (ya está en login.php)
if (!in_array($current_page, $public_pages)) {
    require_once $base_url . 'config/database.php';
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Sistema de Banda Marcial</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css">
    <script src="<?php echo $base_url; ?>js/main.js"></script>
</head>
<body>
    <header class="header">
        <div class="container">
            <h1>🎺 Sistema de Banda Marcial</h1>
            <p>Gestión de Instrumentos y Uniformes</p>
        </div>
    </header>
    
    <nav class="nav">
        <div class="container">
            <ul>
                <li><a href="<?php echo $base_url; ?>index.php" <?php echo (isset($current_module) && $current_module == 'dashboard') ? 'class="active"' : ''; ?>>Dashboard</a></li>
                <li><a href="<?php echo $base_url; ?>modules/instrumentos/index.php" <?php echo (isset($current_module) && $current_module == 'instrumentos') ? 'class="active"' : ''; ?>>Instrumentos</a></li>
                <li><a href="<?php echo $base_url; ?>modules/uniformes/index.php" <?php echo (isset($current_module) && $current_module == 'uniformes') ? 'class="active"' : ''; ?>>Uniformes</a></li>
                <li><a href="<?php echo $base_url; ?>modules/accesorios/index.php" <?php echo (isset($current_module) && $current_module == 'accesorios') ? 'class="active"' : ''; ?>>Accesorios</a></li>
                <li><a href="<?php echo $base_url; ?>modules/repuestos/index.php" <?php echo (isset($current_module) && $current_module == 'repuestos') ? 'class="active"' : ''; ?>>Repuestos</a></li>
                <li><a href="<?php echo $base_url; ?>modules/estudiantes/index.php" <?php echo (isset($current_module) && $current_module == 'estudiantes') ? 'class="active"' : ''; ?>>Estudiantes</a></li>
                <li><a href="<?php echo $base_url; ?>modules/prestamos/index.php" <?php echo (isset($current_module) && $current_module == 'prestamos') ? 'class="active"' : ''; ?>>Préstamos</a></li>
                
                <?php if (isset($_SESSION['user_id'])): // Mostrar enlace de cerrar sesión si está logueado ?>
                    <li class="nav-logout-item">
                        <a href="<?php echo $base_url; ?>logout.php" title="Cerrar Sesión (<?php echo htmlspecialchars($_SESSION['username']); ?>)">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-log-out">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                <polyline points="16 17 21 12 16 7"/>
                                <line x1="21" x2="9" y1="12" y2="12"/>
                            </svg>
                            <span class="sr-only"></span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>
    
    <div class="container main-wrapper-container">
        <!-- Content will be added here -->
    </div>
</body>
</html>
