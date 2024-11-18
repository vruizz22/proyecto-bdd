<?php
class Cargador
{
    public $conn_grupo15e3;
    public $conn_e3profesores;
    public $tablas;
    public $temptablas;

    public function __construct($env_string_1, $env_string_2)
    {
        // Inicializar conexiones
        $this->conn_grupo15e3 = pg_connect($env_string_1);
        $this->conn_e3profesores = pg_connect($env_string_2);

        // Verificar conexiones
        if (!$this->conn_grupo15e3 || !$this->conn_e3profesores) {
            die("Error de conexión: " . pg_last_error());
        }

        $this->tablas = array("profesores", "jerarquia");
        $this->temptablas = array("TempNotasAdivinacion", "TempPlaneacion", "TempEstudiantes", "Acta");
    }

    public function CrearTablas()
    {
        // Eliminar las tablas si existen y crear nuevas desde schema.sql
        foreach ($this->tablas as $tabla) {
            $result = pg_query($this->conn_grupo15e3, "DROP TABLE IF EXISTS {$tabla} CASCADE");
            if (!$result) {
                die("Error en la eliminación de la tabla: " . pg_last_error());
            }
        }

        // Leer y ejecutar el archivo schema.sql
        $schemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            $queries = explode(";", $schema);

            foreach ($this->tablas as $index => $tabla) {
                $query = trim($queries[$index]);
                if (!empty($query)) {
                    $result = pg_query($this->conn_grupo15e3, $query);
                    if (!$result) {
                        die("Error en la creación de la tabla '{$tabla}': " . pg_last_error());
                    }
                }
            }
        } else {
            die("El archivo schema.sql no existe.");
        }
    }

    public function CopiarTablas()
    {
        // Seleccionar solo las columnas necesarias de la tabla profesores
        $select_query = "SELECT run, sexo, jerarquizacion, dedicacion, contrato, jornada, sede, carrera, grado_academico, detalle FROM profesores";
        $result = pg_query($this->conn_e3profesores, $select_query);

        if (!$result) {
            die("Error al obtener datos de profesores: " . pg_last_error($this->conn_e3profesores));
        }

        // Iterar sobre los resultados e insertar en la base de datos grupo15e3
        while ($row = pg_fetch_assoc($result)) {
            // Agregar el valor 'X' para la columna DV
            $row['dv'] = 'X';

            $columns = implode(", ", array_keys($row));
            $values = implode(", ", array_map(function ($val) {
                return "'" . pg_escape_string($this->conn_e3profesores, $val ?? 'X') . "'";
            }, array_values($row)));

            $insert_query = "INSERT INTO profesores ({$columns}) VALUES ({$values})";
            $insert_result = pg_query($this->conn_grupo15e3, $insert_query);

            if (!$insert_result) {
                die("Error al insertar datos en profesores: " . pg_last_error($this->conn_grupo15e3));
            }
        }

        $select_query = "SELECT * FROM jerarquia";
        $result = pg_query($this->conn_e3profesores, $select_query);

        if (!$result) {
            die("Error al obtener datos de jerarquia: " . pg_last_error());
        }

        // Iterar sobre los resultados e insertar en la base de datos grupo15e3
        while ($row = pg_fetch_assoc($result)) {
            $columns = implode(", ", array_keys($row));
            $values = implode(", ", array_map(function ($val) {
                return "'" . pg_escape_string($this->conn_grupo15e3, $val ?? 'X') . "'";
            }, array_values($row)));

            $insert_query = "INSERT INTO jerarquia ({$columns}) VALUES ({$values})";
            $insert_result = pg_query($this->conn_grupo15e3, $insert_query);

            if (!$insert_result) {
                die("Error al insertar datos en jerarquia: " . pg_last_error());
            }
        }
    }


    public function CargarDatos()
    {
        // Seleccionar los datos de la tabla en e3profesores necesarios en la tabla Personas
        $select_query = "SELECT run, nombre, apellido1, apellido2, email_personal, email_institucional, telefono FROM profesores";
        $result = pg_query($this->conn_e3profesores, $select_query);

        if (!$result) {
            die("Error al obtener datos de profesores: " . pg_last_error($this->conn_e3profesores));
        }

        // Iterar sobre los resultados e insertar en la base de datos grupo15e3
        while ($row = pg_fetch_assoc($result)) {
            // Agregar el valor 'X' para la columna DV
            $row['dv'] = 'X';
            // Cambiar nombre de columnas
            $row['correo_personal'] = $row['email_personal'];
            $row['correo_institucional'] = $row['email_institucional'];
            $row['nombre_1'] = $row['nombre'];
            $row['apellido_1'] = $row['apellido1'];
            $row['apellido_2'] = $row['apellido2'];

            // Eliminar las columnas originales que no se necesitan
            unset($row['email_personal']);
            unset($row['email_institucional']);
            unset($row['nombre']);
            unset($row['apellido1']);
            unset($row['apellido2']);

            // Si nombre o apellido 1 es nulo, se asigna un valor X
            if (empty($row['nombre_1'])) {
                $row['nombre_1'] = 'X';
            }
            if (empty($row['apellido_1'])) {
                $row['apellido_1'] = 'X';
            }

            $columns = implode(", ", array_keys($row));
            $values = implode(", ", array_map(function ($val) {
                return "'" . pg_escape_string($this->conn_grupo15e3, $val ?? 'X') . "'";
            }, array_values($row)));

            $insert_query = "INSERT INTO personas ({$columns}) VALUES ({$values}) ON CONFLICT (RUN, DV) DO NOTHING";
            $insert_result = pg_query($this->conn_grupo15e3, $insert_query);

            if (!$insert_result) {
                die("Error al insertar datos en personas: " . pg_last_error($this->conn_grupo15e3));
            }
        }
    }

    public function TablasExistentes()
    {
        foreach ($this->tablas as $tabla) {
            $query = "SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '{$tabla}')";
            $result = pg_query($this->conn_grupo15e3, $query);

            if (!$result) {
                die("Error al verificar la existencia de la tabla '{$tabla}': " . pg_last_error($this->conn_grupo15e3));
            }

            $exists = pg_fetch_result($result, 0, 0);
            if ($exists === 'f') {
                return false;
            }
        }
        return true;
    }

    public function CrearTablasTemporales()
    {
        // Leer y ejecutar el archivo tempschema.sql
        $tempschemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'tempschema.sql';
        if (file_exists($tempschemaFile)) {
            $schema = file_get_contents($tempschemaFile);
            $queries = explode(";", $schema);

            foreach ($this->temptablas as $index => $tabla) {
                $query = trim($queries[$index]);
                if (!empty($query)) {
                    $result = pg_query($this->conn_grupo15e3, $query);
                    if (!$result) {
                        die("Error en la creación de la tabla '{$tabla}': " . pg_last_error());
                    }
                }
            }
            echo "Tablas temporales creadas\n";
        } else {
            die("El archivo tempschema.sql no existe.");
        }

        $nombre_archivos = array('Estudiantes', 'notas_adivinacion_I', 'Planeacion');

        $ruta_base = __DIR__ . DIRECTORY_SEPARATOR . 'data';
        $ruta_datos = array_map(function ($nombre) use ($ruta_base) {
            return $ruta_base . DIRECTORY_SEPARATOR . $nombre . '.csv';
        }, $nombre_archivos);

        // Leer los datos de los csv
        $datos_array = array_map(function ($ruta) {
            return $this->LeerArchivo($ruta);
        }, $ruta_datos);

        // Combinar los nombres de los archivos con los datos leídos
        # Diccionario que contiene los datos de los archivos asociados a su nombre
        $datos = array_combine($nombre_archivos, $datos_array);

        // Insertar datos en las tablas temporales
        $this->InsertarDatosTemporales($datos);
    }


    public function cerrarConexiones()
    {
        pg_close($this->conn_grupo15e3);
        pg_close($this->conn_e3profesores);
    }


    private function InsertarDatosTemporales($datos)
    {
        echo "Insertando datos en las tablas temporales...\n";
        foreach ($datos['Planeacion'] as $planeacion) {
            $query = "INSERT INTO TempPlaneacion (Id_Asignatura, Nombre_Docente) VALUES (
                        '{$planeacion[5]}',  -- Id_Asignatura
                        '{$planeacion[21]}'  -- Nombre_Docente
            )";
            $result = pg_query($this->conn_grupo15e3, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempPlaneacion: " . pg_last_error());
            }
        }

        foreach ($datos['Estudiantes'] as $estudiante) {
            $query = "INSERT INTO TempEstudiantes (Numero_de_alumno, Nombre_1, Nombre_2) VALUES (
                        '{$estudiante[3]}',    -- Numero_de_alumno
                        '{$estudiante[8]}',    -- Nombre_1
                        '{$estudiante[9]}'     -- Nombre_2

            )";
            $result = pg_query($this->conn_grupo15e3, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempEstudiantes: " . pg_last_error());
            }
        }

        foreach ($datos['notas_adivinacion_I'] as $notas_adivinacion) {
            $query = "INSERT INTO TempNotasAdivinacion (numero_alumno, asignatura, seccion, periodo, oportunidad_dic, oportunidad_mar) VALUES (
                        '{$notas_adivinacion[0]}',  -- Numero_Alumno
                        '{$notas_adivinacion[2]}',  -- ASIGNATURA
                        '{$notas_adivinacion[3]}',  -- SECCION
                        '{$notas_adivinacion[4]}',  -- PERIODO
                        '{$notas_adivinacion[5]}',  -- OPORTUNIDAD DIC
                        '{$notas_adivinacion[6]}'   -- OPORTUNIDAD MAR
                    )";
            $result = pg_query($this->conn_grupo15e3, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal Acta: " . pg_last_error());
            }
        }

        // Iniciar la transacción
        pg_query($this->conn_grupo15e3, "BEGIN");

        define('REPROBATORIO_MIN', 1);
        define('REPROBATORIO_MAX', 3.99);
        define('APROBATORIO_MIN', 4);
        define('APROBATORIO_MAX', 7);

        $query = "INSERT INTO Acta (Numero_Alumno, Curso, Periodo, Nombre_Estudiante, Nombre_Profesor, Nota_Final)
            SELECT DISTINCT
            -- CAMBIAR NOTAS POR ESTUDIANTES
                TempNotasAdivinacion.numero_alumno,
                TempNotasAdivinacion.asignatura,
                TempNotasAdivinacion.periodo,
                CONCAT(TempEstudiantes.Nombre_1, ' ', TempEstudiantes.Nombre_2) AS Nombre_Estudiante,
                TempPlaneacion.Nombre_Docente,
                -- Si la oportunidad dic es P, la nota final es 0
                -- si dic es aprobatoria o (mar es vacio y dic reporbatoria), la nota final es dic
                -- la nota final es mar (rango completo) en otro caso
                CASE
                    WHEN TempNotasAdivinacion.oportunidad_dic = 'P' THEN 0
                    WHEN CAST(TempNotasAdivinacion.oportunidad_dic AS NUMERIC) BETWEEN " . APROBATORIO_MIN . " AND " . APROBATORIO_MAX . " THEN CAST(TempNotasAdivinacion.oportunidad_dic AS NUMERIC)
                    WHEN TRIM(TempNotasAdivinacion.oportunidad_mar) = '' AND CAST(TempNotasAdivinacion.oportunidad_dic AS NUMERIC) BETWEEN " . REPROBATORIO_MIN . " AND " . REPROBATORIO_MAX . " THEN CAST(TempNotasAdivinacion.oportunidad_dic AS NUMERIC)
                    WHEN CAST(TempNotasAdivinacion.oportunidad_mar AS NUMERIC) BETWEEN " . REPROBATORIO_MIN . " AND " . APROBATORIO_MAX . " THEN CAST(TempNotasAdivinacion.oportunidad_mar AS NUMERIC)
                    ELSE 0
                END AS Nota_Final
            FROM TempNotasAdivinacion
            LEFT JOIN TempEstudiantes ON TempNotasAdivinacion.numero_alumno = TempEstudiantes.Numero_de_alumno
            LEFT JOIN TempPlaneacion ON TempNotasAdivinacion.asignatura = TempPlaneacion.Id_Asignatura";

        // Ejecutar la consulta
        $result = pg_query($this->conn_grupo15e3, $query);

        if (!$result) {

            // Registrar el error en el archivo de log
            $error_message = "Error: " . pg_last_error($this->conn_grupo15e3) . "\n";
            file_put_contents('error_log.txt', $error_message, FILE_APPEND);
            echo "Error en la transacción: " . pg_last_error($this->conn_grupo15e3) . "\n";
            // Revertir la transacción
            pg_query($this->conn_grupo15e3, "ROLLBACK");
            return;
        }
        // ver tabla temporal acta
        /*
        $query = "SELECT * FROM Acta";
        $result = pg_query($this->conn_grupo15e3, $query);
        $rows = pg_fetch_all($result);
        print_r($rows);
        */
        // Confirmar la transacción
        pg_query($this->conn_grupo15e3, "COMMIT");
        echo "Transacción completada con éxito \n";
    }

    private function LeerArchivo($archivo)
    {
        /* LeerArchivo recibe un archivo .csv
        y realiza la lectura para retornalo como array */

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
}
