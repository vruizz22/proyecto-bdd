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

    <h2>Obtener Porcentaje de Aprobación</h2>

    <form action="/grupo15/index.php?controller=MenuController&action=obtenerPorcentajeAprobacion" method="POST">
        <label for="periodo">Período:</label>
        <input type="text" id="periodo" name="periodo" required>
        <input type="submit" value="Obtener Porcentaje de Aprobación">
    </form>

    <?php if (isset($porcentajeAprobacion) && is_array($porcentajeAprobacion) && count($porcentajeAprobacion) > 0): ?>
        <h2>Porcentaje de Aprobación por Curso</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Sigla Curso</th>
                    <th>Nombre</th>
                    <th>Porcentaje de Aprobación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($porcentajeAprobacion as $curso): ?>
                    <tr>
                        <td><?php echo $curso['sigla_curso']; ?></td>
                        <td><?php echo $curso['nombre']; ?></td>
                        <td><?php echo $curso['porcentaje_aprobacion']; ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>


    <h2>Obtener Promedio Porcentaje de Aprobación</h2>

    <form action="/grupo15/index.php?controller=MenuController&action=obtenerPromedioPorcentajeAprobacion" method="POST">
        <label for="codigo_curso">Código de Curso:</label>
        <input type="text" id="codigo_curso" name="codigo_curso" required>
        <input type="submit" value="Obtener Promedio Porcentaje de Aprobación">
    </form>

    <?php if (isset($promedioPorcentajeAprobacion) && is_array($promedioPorcentajeAprobacion) && count($promedioPorcentajeAprobacion) > 0): ?>
        <h2>Promedio del Porcentaje de Aprobación Histórico</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Sigla Curso</th>
                    <th>Promedio Porcentaje de Aprobación</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($promedioPorcentajeAprobacion as $curso): ?>
                    <tr>
                        <td><?php echo $curso['sigla_curso']; ?></td>
                        <td><?php echo number_format($curso['promedio_porcentaje_aprobacion'], 2); ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h2>Obtener Toma de Ramos 2025-01</h2>

    <form action="/grupo15/index.php?controller=MenuController&action=obtenerTomaRamos" method="POST">
        <label for="numero_estudiante">Número de Estudiante:</label>
        <input type="number" id="numero_estudiante" name="numero_estudiante" required>
        <input type="submit" value="Obtener Toma de Ramos">
    </form>

    <?php if (isset($tomaRamos) && is_array($tomaRamos) && count($tomaRamos) > 0): ?>
        <h2>Cursos Que Puedes Tomar en 2025-01</h2>
        <table border="1">
            <thead>
                <tr>
                    <th>Sigla Curso</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tomaRamos as $curso): ?>
                    <tr>
                        <td><?php echo $curso['sigla_curso']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>


    <h2>Obtener Historial de Estudiante</h2>

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

            <h2>Ver Acta</h2>
            <form action="/grupo15/index.php?controller=MenuController&action=VerActa" method="POST">
                <input type="submit" value="Ver Acta">
            </form>

            <?php if (isset($acta) && is_array($acta) && count($acta) > 0): ?>
                <h2>Acta</h2>
                <table border="1">
                    <thead>
                        <tr>
                            <th>Numero Alumno</th>
                            <th>Curso</th>
                            <th>Periodo</th>
                            <th>Nombre Estudiante</th>
                            <th>Nombre Profesor</th>
                            <th>Nota Final</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($acta as $fila): ?>
                            <tr>
                                <td><?php echo $fila['numero_alumno']; ?></td>
                                <td><?php echo $fila['curso']; ?></td>
                                <td><?php echo $fila['periodo']; ?></td>
                                <td><?php echo $fila['nombre_estudiante']; ?></td>
                                <td><?php echo $fila['nombre_profesor']; ?></td>
                                <td><?php echo $fila['nota_final']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>


    

</body>

</html>