<?php

class Database
{
    private $connection;

    public function __construct()
    {
        // Conectar a PostgreSQL
        $this->connection = pg_connect("host=localhost port=5432 dbname=grupo15 user=grupo15 password=Elefante$15");

        if (!$this->connection) {
            die("Error en la conexión con la base de datos: " . pg_last_error());
        }
    }

    public function consulta1()
    {
        $sql = "SELECT COUNT(DISTINCT Numero_de_estudiante) AS total_estudiantes FROM Avance_Academico aa WHERE aa.Periodo_Oferta = '2024-02'";
        $result = pg_query($this->connection, $sql);
        if (!$result) {
            die("Error en la consulta: " . pg_last_error());
        }
        return pg_fetch_assoc($result);
    }
    public function PorcentajeAprobacion($periodo)
    {
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
        $result = pg_query_params($this->connection, $query, array($periodo));
        if (!$result) {
            die("Error en la consulta: " . pg_last_error($this->connection));
        }

        // Convertir todos los registros en JSON y retornarlos
        $porcentajeAprobacion = [];
        while ($row = pg_fetch_assoc($result)) {
            $porcentajeAprobacion[] = $row;
        }

        return $porcentajeAprobacion;
    }
    public function PromedioPorcentajeAprobacion($codigo_curso)
    {
        $query = "SELECT 
            Sigla_curso,
            AVG(Porcentaje_Aprobacion) AS Promedio_Porcentaje_Aprobacion
        FROM (
            SELECT 
                Cursos.Sigla_curso,
                ROUND(
                    SUM(CASE WHEN Avance_Academico.Nota >= 4.0 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(Avance_Academico.Numero_de_estudiante), 0), 
                2) AS Porcentaje_Aprobacion
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
                Cursos.Sigla_curso = $1
            GROUP BY 
                Cursos.Sigla_curso, Cursos.Seccion_curso, Cursos.Periodo_curso
        ) AS Subconsulta
        GROUP BY 
            Sigla_curso;";

        // Preparar y ejecutar la consulta con el parámetro
        $result = pg_query_params($this->connection, $query, array($codigo_curso));
        if (!$result) {
            die("Error en la consulta: " . pg_last_error($this->connection));
        }

        // Convertir todos los registros en JSON y printearlos
        $promedioPorcentajeAprobacion = [];
        while ($row = pg_fetch_assoc($result)) {
            $promedioPorcentajeAprobacion[] = $row;
        }

        return $promedioPorcentajeAprobacion;
    }

    public function TomaRamos($numeroEstudiante)
    {

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
        $result = pg_query_params($this->connection, $query, array($numeroEstudiante));
        if (!$result) {
            die("Error en la consulta: " . pg_last_error($this->connection));
        }

        // Convertir todos los registros en JSON y printearlos
        $tomaRamos = [];
        while ($row = pg_fetch_assoc($result)) {
            $tomaRamos[] = $row;
        }

        return $tomaRamos;
    }

    public function obtenerEstudiantePorNumero($numeroEstudiante)
    {
        // Pasar numero de estudiante a int
        $numeroEstudiante = (int)$numeroEstudiante;
        // Preparar la consulta
        $sql = "WITH Historial AS (
            SELECT 
                Avance_Academico.Numero_de_estudiante,
                Avance_Academico.Periodo_curso,
                Cursos.Sigla_curso,
                Cursos.Nombre AS Nombre_Curso,
                Nota.nota AS Nota,
                Avance_Academico.Calificacion,
                CASE 
                    WHEN Nota.nota >= 4.0 THEN 'Aprobado'
                    WHEN Nota.nota < 4.0 AND Nota.nota IS NOT NULL THEN 'Reprobado'
                    ELSE 'Vigente' 
                END AS Estado_Curso
            FROM 
                Avance_Academico
            JOIN 
                Cursos ON Avance_Academico.Sigla_curso = Cursos.Sigla_curso 
                    AND Avance_Academico.Seccion_curso = Cursos.Seccion_curso
                    AND Avance_Academico.Periodo_curso = Cursos.Periodo_curso
            LEFT JOIN 
                Nota ON Avance_Academico.Numero_de_estudiante = Nota.numero_de_estudiante
                    AND Avance_Academico.Sigla_curso = Nota.sigla_curso
                    AND Avance_Academico.Seccion_curso = Nota.seccion_curso
                    AND Avance_Academico.Periodo_curso = Nota.periodo_curso
            WHERE 
                Avance_Academico.Numero_de_estudiante = $numeroEstudiante
        ),
        Resumen_Periodo AS (
            SELECT
                Periodo_curso,
                COUNT(CASE WHEN Estado_Curso = 'Aprobado' THEN 1 END) AS Cursos_Aprobados,
                COUNT(CASE WHEN Estado_Curso = 'Reprobado' THEN 1 END) AS Cursos_Reprobados,
                COUNT(CASE WHEN Estado_Curso = 'Vigente' THEN 1 END) AS Cursos_Vigentes,
                ROUND(CAST(AVG(Nota) FILTER (WHERE Nota IS NOT NULL) AS numeric), 2) AS PPS -- Promedio del Período por estudiante
            FROM 
                Historial
            GROUP BY 
                Periodo_curso
        ),
        Resumen_Total AS (
            SELECT
                COUNT(CASE WHEN Estado_Curso = 'Aprobado' THEN 1 END) AS Total_Cursos_Aprobados,
                COUNT(CASE WHEN Estado_Curso = 'Reprobado' THEN 1 END) AS Total_Cursos_Reprobados,
                COUNT(CASE WHEN Estado_Curso = 'Vigente' THEN 1 END) AS Total_Cursos_Vigentes,
                ROUND(CAST(AVG(Nota) FILTER (WHERE Nota IS NOT NULL) AS numeric), 2) AS PPA -- Promedio ponderado del estudiante
            FROM 
                Historial
        )
        SELECT 
            Historial.Periodo_curso,
            Historial.Sigla_curso,
            Historial.Nombre_Curso,
            Historial.Nota,
            Historial.Calificacion,
            Historial.Estado_Curso,
            Resumen_Periodo.Cursos_Aprobados,
            Resumen_Periodo.Cursos_Reprobados,
            Resumen_Periodo.Cursos_Vigentes,
            Resumen_Periodo.PPS,
            Resumen_Total.Total_Cursos_Aprobados,
            Resumen_Total.Total_Cursos_Reprobados,
            Resumen_Total.Total_Cursos_Vigentes,
            Resumen_Total.PPA,
            CASE 
                WHEN Resumen_Periodo.Cursos_Vigentes > 0 THEN 'Vigente'
                WHEN Resumen_Total.Total_Cursos_Vigentes = 0 AND Resumen_Total.Total_Cursos_Aprobados > 0 THEN 'Licenciado o Titulado'
                ELSE 'No Vigente'
            END AS Estado_Estudiante
        FROM 
            Historial
        JOIN 
            Resumen_Periodo ON Historial.Periodo_curso = Resumen_Periodo.Periodo_curso,
            Resumen_Total
        ORDER BY 
            Historial.Periodo_curso ASC;";

        $result = pg_query($this->connection, $sql);

        if (!$result) {
            die("Error en la consulta: " . pg_last_error());
        }

        // Obtenemos todas las filas de la consulta
        $historial = [];
        while ($row = pg_fetch_assoc($result)) {
            $historial[] = $row;
        }

        return $historial;
    }

    public function obtenerPersonaPorCorreo($correo) {
        $correo = pg_escape_string($correo);
        $sql = "SELECT * FROM Personas WHERE Correos = '$correo'";
        $result = pg_query($this->connection, $sql);
    
        if (!$result) {
            die("Error en la consulta: " . pg_last_error());
        }
    
        return pg_fetch_assoc($result);
    }
    
    public function esAcademico($run) {
        $run = pg_escape_string($run);
        $sql = "SELECT * FROM Academicos WHERE RUN = '$run'";
        $result = pg_query($this->connection, $sql);
    
        return pg_num_rows($result) > 0;
    }
    
    public function esAdministrativo($run) {
        $run = pg_escape_string($run);
        $sql = "SELECT * FROM Administrativos WHERE RUN = '$run'";
        $result = pg_query($this->connection, $sql);
    
        return pg_num_rows($result) > 0;
    }

    public function close()
    {
        pg_close($this->connection);
    }
}
