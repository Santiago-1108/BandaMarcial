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
            </ul>
        </div>
    </nav>
    
    <div class="container main-wrapper-container">
        <!-- Content will be added here -->
    </div>
</body>
</html>
