<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Menú</title>
</head>
<body>
    <h1>Menú Principal</h1>

    <form action="/grupo15/index.php?controller=MenuController&action=realizarConsulta" method="POST">
        <input type="submit" value="Contar Estudiantes en 2024-2">
    </form>

    <?php if (isset($total_estudiantes)): ?>
        <p>Total de Estudiantes en 2024-2: <?php echo $total_estudiantes; ?></p>
    <?php endif; ?>

    <!-- Otras secciones del menú aquí -->
</body>
</html>
