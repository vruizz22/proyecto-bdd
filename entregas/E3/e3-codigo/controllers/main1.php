<?php
// Conexión a la base de datos
$conn = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password=Elefante$15");

$query = "WITH Estudiantes_Ultimos_Planes AS (
    -- Obtenemos el último plan de estudios en que cada estudiante estuvo inscrito
    SELECT 
        Estudiantes.RUN,
        Estudiantes.Numero_de_estudiante, 
        Estudiantes.Ultima_Carga AS Ultimo_Periodo
    FROM 
        Estudiantes
    JOIN 
        Programacion_Academica P ON E.RUN = P.RUN
    GROUP BY 
        E.RUN, E.Numero_de_estudiante
),

Estudiantes_Desertores AS (
    -- Filtramos los estudiantes que se retiraron o suspendieron por más de 2 periodos
    SELECT 
        E.RUN, 
        E.Numero_de_estudiante, 
        E.Nombre_1, 
        E.Apellido_1, 
        E.Apellido_2,
        EU.Ultimo_Periodo,
        CASE 
            WHEN E.Causal_de_bloqueo = 'Retiro Oficial' THEN 'Retiro Oficial'
            WHEN P.Periodo_curso IS NULL AND EU.Ultimo_Periodo < '2023-01' THEN 'Suspensión por más de 2 semestres'
            ELSE 'Activo'
        END AS Estado_Desercion
    FROM 
        Estudiantes E
    LEFT JOIN 
        Estudiantes_Ultimos_Planes EU ON E.RUN = EU.RUN
    LEFT JOIN 
        Programacion_Academica P ON E.RUN = P.RUN AND P.Periodo_curso > EU.Ultimo_Periodo
    WHERE 
        E.Causal_de_bloqueo = 'Retiro Oficial' 
        OR (EU.Ultimo_Periodo IS NOT NULL AND EU.Ultimo_Periodo <= '2023-01') -- Cambia este periodo por el periodo actual menos 2 semestres
)

-- Finalmente, seleccionamos los desertores
SELECT 
    RUN, 
    Numero_de_estudiante, 
    Nombre_1, 
    Apellido_1, 
    Apellido_2, 
    Estado_Desercion
FROM 
    Estudiantes_Desertores
WHERE 
    Estado_Desercion IN ('Retiro Oficial', 'Suspensión por más de 2 semestres');
";

// Preparar y ejecutar la consulta con el parámetro
$result = pg_query($conn, $query);
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
