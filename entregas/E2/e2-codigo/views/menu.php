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

    <form action="/grupo15/index.php?controller=MenuController&action=obtenerEstudiante" method="POST">
        <label for="numero_estudiante">Número de Estudiante:</label>
        <input type="number" id="numero_estudiante" name="numero_estudiante" required>
        <input type="submit" value="Obtener Estudiante">
    </form>

    <?php if (isset($historial) && is_array($historial) && count($historial) > 0): ?>
        <h2>detalles del estudiante</h2>
        <?php 
        $current_period = '';
        $resumen_periodo = [];
        foreach ($historial as $curso): 
            if ($current_period !== $curso['periodo_curso']):
                if ($current_period !== ''): ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6">Resumen del Período</td>
                            <td><?php echo $resumen_periodo['cursos_aprobados']; ?></td>
                            <td><?php echo $resumen_periodo['cursos_reprobados']; ?></td>
                            <td><?php echo $resumen_periodo['cursos_vigentes']; ?></td>
                            <td><?php echo $resumen_periodo['pps']; ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                    </table>
                <?php endif; ?>
                <h3>Período: <?php echo $curso['periodo_curso']; ?></h3>
                <table border="1">
                    <thead>
                        <tr>
                            <th>periodo curso</th>
                            <th>sigla curso</th>
                            <th>nombre curso</th>
                            <th>nota</th>
                            <th>calificación</th>
                            <th>estado curso</th>
                            <th>cursos aprobados</th>
                            <th>cursos reprobados</th>
                            <th>cursos vigentes</th>
                            <th>pps</th>
                        </tr>
                    </thead>
                    <tbody>
                <?php 
                $current_period = $curso['periodo_curso'];
            endif; ?>
            <tr>
                <td><?php echo $curso['periodo_curso']; ?></td>
                <td><?php echo $curso['sigla_curso']; ?></td>
                <td><?php echo $curso['nombre_curso']; ?></td>
                <td><?php echo $curso['nota']; ?></td>
                <td><?php echo $curso['calificacion']; ?></td>
                <td><?php echo $curso['estado_curso']; ?></td>
                <td><?php echo $curso['cursos_aprobados']; ?></td>
                <td><?php echo $curso['cursos_reprobados']; ?></td>
                <td><?php echo $curso['cursos_vigentes']; ?></td>
                <td><?php echo $curso['pps']; ?></td>
            </tr>
            <?php 
            // Actualizar el resumen del período actual
            $resumen_periodo = [
                'cursos_aprobados' => $curso['cursos_aprobados'],
                'cursos_reprobados' => $curso['cursos_reprobados'],
                'cursos_vigentes' => $curso['cursos_vigentes'],
                'pps' => $curso['pps']
            ];
            ?>
        <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6">Resumen del Período</td>
                <td><?php echo $resumen_periodo['cursos_aprobados']; ?></td>
                <td><?php echo $resumen_periodo['cursos_reprobados']; ?></td>
                <td><?php echo $resumen_periodo['cursos_vigentes']; ?></td>
                <td><?php echo $resumen_periodo['pps']; ?></td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        </table>
    
        <!-- Tabla de resumen total -->
        <h3>Resumen Total</h3>
        <table border="1">
            <thead>
                <tr>
                    <th>total cursos aprobados</th>
                    <th>total cursos reprobados</th>
                    <th>total cursos vigentes</th>
                    <th>ppa</th>
                    <th>estado estudiante</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo $curso['total_cursos_aprobados']; ?></td>
                    <td><?php echo $curso['total_cursos_reprobados']; ?></td>
                    <td><?php echo $curso['total_cursos_vigentes']; ?></td>
                    <td><?php echo $curso['ppa']; ?></td>
                    <td><?php echo $curso['estado_estudiante']; ?></td>
                </tr>
            </tbody>
        </table>
    <?php endif; ?>

</html>