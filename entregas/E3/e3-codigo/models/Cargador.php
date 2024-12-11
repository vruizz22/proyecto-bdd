<?php
class Cargador
{
    public $conn_grupo15e3;
    public $conn_e3profesores;
    public $tablas;
    public $temptablas;

    public function __construct()
    {
        // Inicializar conexiones
        $this->conn_grupo15e3 = pg_connect("host=localhost port=5432 dbname=postgres user=postgres password=Elefante$15");
        $this->conn_e3profesores = pg_connect("host=localhost port=5432 dbname=e3profesores user=postgres password=Elefante$15");

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

        // Leer y ejecutar el archivo schema.sql ../sql/schema.sql
        $schemaFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'schema.sql';
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

            $update_set = implode(", ", array_map(function ($key, $val) {
                return "{$key} = EXCLUDED.{$key}";
            }, array_keys($row), $row));

            $insert_query = "INSERT INTO personas ({$columns}) VALUES ({$values}) ON CONFLICT (RUN, DV) DO UPDATE SET {$update_set}";
            $insert_result = pg_query($this->conn_grupo15e3, $insert_query);

            if (!$insert_result) {
                die("Error al insertar o actualizar datos en personas: " . pg_last_error($this->conn_grupo15e3));
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
        $tempschemaFile = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'tempschema.sql';
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
        } else {
            die("El archivo tempschema.sql no existe.");
        }

        $nombre_archivos = array('Estudiantes', 'notas_adivinacion_I', 'Planeacion');

        $ruta_base = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data';
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


    public function InsertarNota()
    {
        /* Funcion que va insertando las notas de la tabla
        temp acta en la tabla Nota
        */

        $this->CrearTriggers();

        // Iniciar la transacción
        pg_query($this->conn_grupo15e3, "BEGIN");

        // Insertar los datos de la tabla Acta en la tabla Nota
        // Tabla Nota:  sigla_curso | seccion_curso | periodo_curso | numero_de_estudiante |   run    | dv | nota |  descripcion  |               resultado               | calificacion       
        // ACTA : Numero_Alumno | Curso | Seccion | Periodo | Nombre_Estudiante | Nombre_Profesor | Nota_Final

        $query = "INSERT INTO Nota (sigla_curso, seccion_curso, periodo_curso, numero_de_estudiante, run, dv, nota, descripcion, resultado, calificacion)
            SELECT DISTINCT
                Acta.Curso AS sigla_curso,
                Acta.Seccion AS seccion_curso,
                Acta.Periodo AS periodo_curso,
                Acta.Numero_Alumno AS numero_de_estudiante,
                Acta.RUN,
                Personas.dv,
                Acta.Nota_Final AS Nota,
                '' AS Descripcion, -- Se actualiza en el trigger
                '' AS Resultado, -- Se actualiza en el trigger
                '' AS Calificacion -- Se actualiza en el trigger
            FROM Acta
            JOIN Personas ON Acta.RUN::VARCHAR = Personas.run
            --  primary key (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante)
            ON CONFLICT (sigla_curso, seccion_curso, periodo_curso, run, dv, numero_de_estudiante)
            DO UPDATE SET 
                run = EXCLUDED.run,
                dv = EXCLUDED.dv,
                nota = EXCLUDED.nota,
                descripcion = EXCLUDED.descripcion,
                resultado = EXCLUDED.resultado,
                calificacion = EXCLUDED.calificacion";

        $result = pg_query($this->conn_grupo15e3, $query);
        if (!$result) {
            die("Error al insertar o actualizar datos en Nota: " . pg_last_error($this->conn_grupo15e3));
        }

        // Confirmar la transacción
        pg_query($this->conn_grupo15e3, "COMMIT");
    }

    public function cerrarConexiones()
    {
        pg_close($this->conn_grupo15e3);
        pg_close($this->conn_e3profesores);
    }


    private function InsertarDatosTemporales($datos)
    {
        foreach ($datos['Planeacion'] as $planeacion) {
            $query = "INSERT INTO TempPlaneacion (Periodo, Id_Asignatura, Seccion, RUN) VALUES (
                        '{$planeacion[0]}',  -- Periodo
                        '{$planeacion[5]}',  -- Id_Asignatura
                        '{$planeacion[7]}',  -- Seccion
                        '{$planeacion[20]}'  -- RUN
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
            $query = "INSERT INTO TempNotasAdivinacion (numero_alumno, run, asignatura, seccion, periodo, oportunidad_dic, oportunidad_mar) VALUES (
                        '{$notas_adivinacion[0]}',  -- Numero_Alumno
                        '{$notas_adivinacion[1]}',  -- RUN
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

        $query = "INSERT INTO Acta (Numero_Alumno, RUN, Curso, Seccion, Periodo, Nombre_Estudiante, Nombre_Profesor, Nota_Final)
            SELECT DISTINCT
                TempNotasAdivinacion.numero_alumno,
                TempNotasAdivinacion.run,
                TempNotasAdivinacion.asignatura,
                TempNotasAdivinacion.seccion,
                TempPlaneacion.Periodo,
                CONCAT(TempEstudiantes.Nombre_1, ' ', TempEstudiantes.Nombre_2) AS Nombre_Estudiante,
                Personas.nombre_1 AS Nombre_Profesor,
                -- Si la oportunidad dic es P, la nota final es 0
                -- si dic es aprobatoria o (mar es vacio y dic reporbatoria), la nota final es dic
                -- la nota final es mar (rango completo) en otro caso
                CASE
                    WHEN TempNotasAdivinacion.oportunidad_dic = 'P' THEN 0
                    WHEN CAST(TempNotasAdivinacion.oportunidad_dic AS NUMERIC) BETWEEN " . APROBATORIO_MIN . " AND " . APROBATORIO_MAX . " THEN CAST(TempNotasAdivinacion.oportunidad_dic AS NUMERIC)
                    WHEN TRIM(TempNotasAdivinacion.oportunidad_mar) = '' AND CAST(TempNotasAdivinacion.oportunidad_dic AS NUMERIC) BETWEEN " . REPROBATORIO_MIN . " AND " . REPROBATORIO_MAX . " THEN CAST(TempNotasAdivinacion.oportunidad_dic AS NUMERIC)
                    WHEN CAST(TempNotasAdivinacion.oportunidad_mar AS NUMERIC) BETWEEN " . REPROBATORIO_MIN . " AND " . APROBATORIO_MAX . " THEN CAST(TempNotasAdivinacion.oportunidad_mar AS NUMERIC)
                    ELSE -1
                END AS Nota_Final
            FROM TempNotasAdivinacion
            LEFT JOIN TempEstudiantes ON TempNotasAdivinacion.numero_alumno = TempEstudiantes.Numero_de_alumno
            JOIN TempPlaneacion ON TempNotasAdivinacion.asignatura = TempPlaneacion.Id_Asignatura
            AND TempNotasAdivinacion.seccion = TempPlaneacion.Seccion
            JOIN Personas ON TempPlaneacion.RUN = Personas.run
            ON CONFLICT (Numero_Alumno) DO NOTHING";

        // Ejecutar la consulta
        $result = pg_query($this->conn_grupo15e3, $query);

        if (!$result) {

            // Registrar el error en el archivo de log
            $error_message = "Error: " . pg_last_error($this->conn_grupo15e3) . "\n";
            file_put_contents('error.log', $error_message, FILE_APPEND);
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

        //  View de la tabla temp Acta(Create VIEW VerActa AS SELECT * FROM Acta)
        $query = "CREATE VIEW VerActa AS SELECT * FROM Acta";
        $result = pg_query($this->conn_grupo15e3, $query);
        if (!$result) {
            die("Error en la creación de la vista VerActa: " . pg_last_error());
        }

        // Eliminar funcion si existe
        $query = "DROP FUNCTION IF EXISTS VerActa();";
        $result = pg_query($this->conn_grupo15e3, $query);
        if (!$result) {
            die("Error en la eliminación de la función VerActa: " . pg_last_error());
        }
        // crear una funcion para mostrar los datos de la View
        $query = "CREATE OR REPLACE FUNCTION VerActa() RETURNS TABLE (Numero_Alumno INT, RUN INT, Curso VARCHAR, Seccion INT, Periodo VARCHAR, Nombre_Estudiante VARCHAR, Nombre_Profesor VARCHAR, Nota_Final FLOAT) 
        AS $$
        BEGIN
            RETURN QUERY SELECT * FROM VerActa;
        END;
        $$ LANGUAGE plpgsql";
        $result = pg_query($this->conn_grupo15e3, $query);
        if (!$result) {
            die("Error en la creación de la función VerActa: " . pg_last_error());
        }
    }

    private function CrearTriggers()
    {
        // Eliminar los triggers y funciones si existen
        $queries = [
            "DROP TRIGGER IF EXISTS trigger_actualizar_nota ON Nota",
            "DROP FUNCTION IF EXISTS actualizar_nota() CASCADE"
        ];
        foreach ($queries as $query) {
            $result = pg_query($this->conn_grupo15e3, $query);
            if (!$result) {
                die("Error en la eliminación de triggers o funciones: " . pg_last_error($this->conn_grupo15e3));
            }
        }

        // Crear la función para actualizar la tabla Nota
        $funcion_actualizar_nota = "CREATE OR REPLACE FUNCTION actualizar_nota()
        RETURNS TRIGGER AS $$
        BEGIN
            -- SI nota es 0, Calificacion es P
            -- SI nota es -1, Calificacion es null y Nota es null
            -- SI nota es entre 1 y 3.99, Calificacion es Reprobatorio
            -- SI nota es entre 4 y 7, Calificacion es Aprobatorio
            IF NEW.Nota = 0 THEN
                NEW.Calificacion := 'P';
                NEW.Resultado := 'Curso incompleto';
                NEW.Descripcion := 'Nota Pendiente';
                NEW.Nota := NULL;
            ELSIF NEW.Nota = -1 THEN
                NEW.Calificacion := NULL;
                NEW.Resultado := 'Curso Vigente en el período académico';
                NEW.Descripcion := 'curso vigente';
                NEW.Nota := NULL;
            ELSIF NEW.Nota BETWEEN 6.6 AND 7.0 THEN
                NEW.Calificacion := 'SO';
                NEW.Descripcion := 'Sobresaliente';
                NEW.Resultado := 'Aprobatorio';
            ELSIF NEW.Nota BETWEEN 6.0 AND 6.5 THEN
                NEW.Calificacion := 'MB';
                NEW.Descripcion := 'Muy Bueno';
                NEW.Resultado := 'Aprobatorio';
            ELSIF NEW.Nota BETWEEN 5.0 AND 5.9 THEN
                NEW.Calificacion := 'B';
                NEW.Descripcion := 'Bueno';
                NEW.Resultado := 'Aprobatorio';
            ELSIF NEW.Nota BETWEEN 4.0 AND 4.9 THEN
                NEW.Calificacion := 'SU';
                NEW.Descripcion := 'Suficiente';
                NEW.Resultado := 'Aprobatorio';
            ELSIF NEW.Nota BETWEEN 3.0 AND 3.9 THEN
                NEW.Calificacion := 'I';
                NEW.Descripcion := 'Insuficiente';
                NEW.Resultado := 'Reprobatorio';
            ELSIF NEW.Nota BETWEEN 2.0 AND 2.9 THEN
                NEW.Calificacion := 'M';
                NEW.Descripcion := 'Malo';
                NEW.Resultado := 'Reprobatorio';
            ELSIF NEW.Nota BETWEEN 1.0 AND 1.9 THEN
                NEW.Calificacion := 'MM';
                NEW.Descripcion := 'Muy Malo';
                NEW.Resultado := 'Reprobatorio';
            END IF;
            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;";

        $result = pg_query($this->conn_grupo15e3, $funcion_actualizar_nota);
        if (!$result) {
            die("Error en la creación de la función actualizar_nota: " . pg_last_error($this->conn_grupo15e3));
        }

        // Crear el trigger para la tabla Nota
        $trigger_actualizar_nota = "CREATE TRIGGER trigger_actualizar_nota
        BEFORE INSERT OR UPDATE ON Nota
        FOR EACH ROW
        EXECUTE FUNCTION actualizar_nota();";

        $result = pg_query($this->conn_grupo15e3, $trigger_actualizar_nota);
        if (!$result) {
            die("Error en la creación del trigger trigger_actualizar_nota: " . pg_last_error($this->conn_grupo15e3));
        }
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
