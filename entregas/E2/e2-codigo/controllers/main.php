<?php
// Conexión a la base de datos
$conn = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password=Elefante$15");

$periodo = '2024-02';
// Consulta SQL con el período como parámetro
$query = "SELECT 
    Cursos.Sigla_curso,
    Cursos.Nombre,
    COALESCE(
        ROUND(
            SUM(CASE WHEN Avance_Academico.Nota >= 4.0 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(Avance_Academico.Numero_de_estudiante), 0), 
        2), 0) AS Porcentaje_Aprobacion
FROM 
    Cursos
JOIN 
    Programacion_Academica ON Cursos.Sigla_curso = Programacion_Academica.Sigla_curso
                            AND Cursos.Seccion_curso = Programacion_Academica.Seccion_curso
                            AND Cursos.Periodo_curso = Programacion_Academica.Periodo_Oferta
LEFT JOIN 
    Avance_Academico ON Cursos.Sigla_curso = Avance_Academico.Sigla_curso
                     AND Cursos.Seccion_curso = Avance_Academico.Seccion_curso
                     AND Cursos.Periodo_curso = Avance_Academico.Periodo_curso
WHERE 
    Programacion_Academica.Periodo_Oferta = $1
GROUP BY 
    Cursos.Sigla_curso, 
    Cursos.Nombre;";

// Preparar y ejecutar la consulta con el parámetro
$result = pg_query_params($conn, $query, array($periodo));
if (!$result) {
    die("Error en la consulta: " . pg_last_error($conn));
}

// Convertir todos los registros en JSON y printearlos
$rows = [];
while ($row = pg_fetch_assoc($result)) {
    $rows[] = $row;
}
echo json_encode($rows);

// Cerrar la conexión
pg_close($conn);
