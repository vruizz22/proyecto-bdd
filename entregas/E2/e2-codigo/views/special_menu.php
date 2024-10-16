<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú Especial</title>
</head>
<body>
    <h1>Menú Especial</h1>
    <p>Bienvenido, <?php echo $_SESSION['user_email']; ?>. Aquí tienes acceso a funciones especiales.</p>
    <a href="index.php?controller=AuthController&action=logout">Cerrar Sesión</a>
</body>
</html>
