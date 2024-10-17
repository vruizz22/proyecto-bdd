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

    public function close()
    {
        pg_close($this->connection);
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
}
