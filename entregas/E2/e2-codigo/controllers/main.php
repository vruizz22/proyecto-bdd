<?php
// Conexión a la base de datos
$conn = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password=Elefante$15");

// Obtener el número de estudiante desde los parámetros de la URL
$numeroEstudiante = 2114;
// Pasar numero de estudiante a int
$numeroEstudiante = (int)$numeroEstudiante;

// Consulta SQL con el número de estudiante como parámetro
$query = "WITH EstudianteVigente AS (
    SELECT DISTINCT 
        e.RUN, e.DV, e.Numero_de_estudiante
    FROM 
        Estudiantes e
    JOIN Avance_Academico aa ON e.RUN = aa.RUN AND e.DV = aa.DV AND e.Numero_de_estudiante = aa.Numero_de_estudiante
    WHERE 
        e.Numero_de_estudiante = $1
        AND aa.Periodo_Oferta = '2024-02'
        AND (e.Bloqueo = 'N' OR e.Bloqueo = 'X')
),

CursosEnCurso AS (
    SELECT DISTINCT 
        aa.Sigla_curso
    FROM 
        Avance_Academico aa
    JOIN EstudianteVigente ev ON aa.RUN = ev.RUN AND aa.DV = ev.DV AND aa.Numero_de_estudiante = ev.Numero_de_estudiante
    WHERE 
        aa.Periodo_curso = '2024-02'
)

SELECT 
    cp.Sigla_curso
FROM 
    Cursos_prerequisitos cp
-- Eliminar plan de cec.Sigla_curso para comparar solo la sigla asignatura
JOIN CursosEnCurso cec ON SUBSTRING(cec.Sigla_curso, LENGTH((SELECT Planes_estudio.codigo_plan FROM Planes_estudio WHERE Planes_estudio.codigo_plan = SUBSTRING(cec.Sigla_curso, 1, 3))) + 1) = cp.Sigla_prerequisito
WHERE 
    cp.Sigla_curso NOT IN (SELECT Sigla_curso FROM CursosEnCurso)
";

// Preparar y ejecutar la consulta con el parámetro
$result = pg_query_params($conn, $query, array($numeroEstudiante));
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
