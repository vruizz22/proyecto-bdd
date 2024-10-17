<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú Especial - Bananer</title>
</head>
<body>
    <h1>Bienvenido al Menú Especial de Bananer</h1>

    <!-- Formulario para registrar un nuevo usuario -->
    <h2>Registrar Nuevo Usuario</h2>
    <form action="/grupo15/index.php?controller=AuthController&action=verificarUsuario" method="POST">
    <label for="email">Correo:</label>
    <input type="email" name="email" id="email" required>
    
    <label for="password">Clave:</label>
    <input type="password" name="password" id="password" required>
    
    <input type="submit" value="Registrar">
    </form>
    


</body>
</html>
