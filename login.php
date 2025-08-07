<?php
session_start();
$base_url = './'; // Base URL for login page
$page_title = 'Iniciar Sesión';

require_once 'config/database.php';

$conn = getDBConnection();
$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $mensaje = 'Por favor ingrese su nombre de usuario y contraseña.';
        $tipo_mensaje = 'error';
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, username, password, role FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && $password === $user['password']) {

                // Autenticación exitosa
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                header('Location: index.php'); // Redirigir al dashboard
                exit;
            } else {
                $mensaje = 'Nombre de usuario o contraseña incorrectos.';
                $tipo_mensaje = 'error';
            }
        } catch (PDOException $e) {
            $mensaje = 'Error de base de datos: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Incluir el header sin la navegación completa para la página de login
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
<body class="login-page-body">
    <div class="login-container">
        <div class="login-logo">
            <img src="<?php echo $base_url; ?>public/images/badaton-logo.jpeg" alt="Logo Badaton - Instrumentos que Inspiran">
        </div>
        <h2>Iniciar Sesión</h2>
        
        <?php if ($mensaje): ?>
        <div class="alert alert-<?php echo $tipo_mensaje; ?>">
            <?php echo htmlspecialchars($mensaje); ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Usuario:</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Contraseña:</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Acceder</button>
        </form>
    </div>
</body>
</html>
