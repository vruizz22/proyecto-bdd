<?php
// Conexión a la base de datos
$conn = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password=Elefante$15");

// funcion para leer el archivo csv
function leerArchivo($archivo)
{
    // abrir con encodig utf-8
    $file = fopen($archivo, 'r', 'UTF-8');
    $array = [];
    $primeralinea = true;
    while (($linea = fgetcsv($file)) !== FALSE) {
        if ($primeralinea) {
            $primeralinea = false;
            continue; // Ignorar la primera línea
        }
        $array[] = $linea;
    }
    fclose($file);
    return $array;
}

// Recibir un curso vigente de data/cursos_vigentes.csv
$cursos_vigentes = '../data/cursos_vigentes.csv';
// Tiene la forma: [Id Asignatura, Asignatura]
$codigoCurso = leerArchivo($cursos_vigentes);
$codigoCurso = array_column($codigoCurso, 0);
$nombreCurso = leerArchivo($cursos_vigentes);
$nombreCurso = array_column($nombreCurso, 1);

// Curso seleccionado por el grupo (por ejemplo, el primero del archivo)
$curso_seleccionado_id = $codigoCurso[0];
$curso_seleccionado_nombre = $nombreCurso[0];

$query = "SELECT DISTINCT Nota.Numero_de_estudiante, Nota.Nota, Nota.Calificacion
-- Se asume JUL y DIC (examenes)
FROM Nota
JOIN Cursos ON Nota.Sigla_curso = Cursos.Sigla_curso
WHERE Nota.Sigla_curso = 'GH13458'
AND Nota.nota IS NOT NULL 
AND (Cursos.Oportunidad = 'JUL' OR Cursos.Oportunidad = 'DIC')";

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
