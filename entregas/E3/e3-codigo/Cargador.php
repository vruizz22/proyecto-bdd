<?php
class Cargador
{
    public $conn;
    public $tablas;
    public $temp_tablas;

    public function __construct($env_string)
    {
        // Inicializar conexión
        $this->conn = pg_connect($env_string);

        // Verificar la conexión
        if (!$this->conn) {
            die("Error en la conexión con la base de datos: " .
                pg_last_error());
        }

        $this->tablas = array(
            "Personas",
            "Estudiantes",
            "Academicos",
            "Administrativos",
            "Departamento",
            "Planes_Estudio",
            "Cursos",
            "Cursos_Equivalencias",
            "Cursos_Prerequisitos",
            "Cursos_Minimos",
            "Programacion_Academica",
            "Nota",
            "Avance_Academico"
        );

        $this->temp_tablas = array(
            "TempAsignaturas",
            "TempPlaneacion",
            "TempEstudiantes",
            "TempNotas",
            "TempDocentesPlanificados",
            "TempPlanes",
            "TempPrerequisitos",
            "TempPlanesMagia",
            "TempPlanesHechiceria",
            "TempMallaHechiceria",
            "TempMallaMagia"
        );
    }

    public function CrearTablas()
    {
        // Eliminar las tablas si existen
        foreach ($this->tablas as $tabla) {
            $result = pg_query($this->conn, "DROP TABLE IF EXISTS {$tabla} CASCADE");
            if (!$result) {
                die("Error en la eliminación de la tabla: " . pg_last_error());
            }
        }

        // Leer y ejecutar el archivo schema.sql
        $schemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'schema.sql';
        if (file_exists($schemaFile)) {
            $schema = file_get_contents($schemaFile);
            $queries = explode(";", $schema); // Dividir el contenido del archivo en consultas individuales

            foreach ($this->tablas as $index => $tabla) {
                $query = trim($queries[$index]);
                if (!empty($query)) {
                    $result = pg_query($this->conn, $query);
                    if (!$result) {
                        die("Error en la creación de la tabla '{$tabla}': " . pg_last_error());
                    }
                }
            }
        } else {
            die("El archivo schema.sql no existe.");
        }

        // Creamos todos los triggers
        $this->CrearTriggers();
    }

    public function CargarDatos()
    {
        $nombre_archivos = array(
            'Asignaturas',
            'Docentes_planificados',
            'Estudiantes',
            'Notas',
            'Planeacion',
            'Planes',
            'Prerequisitos',
            'Planes_Magia',
            'Planes_Hechiceria',
            'Malla_Hechiceria',
            'Malla_Magia'
        );

        $ruta_base = __DIR__ . DIRECTORY_SEPARATOR . 'data';
        $ruta_datos = array_map(function ($nombre) use ($ruta_base) {
            return $ruta_base . DIRECTORY_SEPARATOR . $nombre . '.csv';
        }, $nombre_archivos);

        // Leer los datos de los csv
        $datos_array = array_map(function ($ruta) {
            return $this->LeerArchivo($ruta);
        }, $ruta_datos);

        // Combinar los nombres de los archivos con los datos leídos
        $datos = array_combine($nombre_archivos, $datos_array);

        // Crear tablas temporales
        $this->CrearTablasTemporales();

        // Insertar datos en las tablas temporales
        $this->InsertarDatosTemporales($datos);

        // Leer y ejecutar inserts_schema.sql
        $insertsSchemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'inserts_schema.sql';
        if (file_exists($insertsSchemaFile)) {
            $insertsSchema = file_get_contents($insertsSchemaFile);
            $queries = explode(";", $insertsSchema); // Dividir el contenido del archivo en consultas individuales

            foreach ($this->tablas as $index => $tabla) {
                $query = trim($queries[$index]);
                if (!empty($query)) {
                    $result = pg_query($this->conn, $query);
                    if (!$result) {
                        die("Error en la inserción de datos en la tabla '{$tabla}': " . pg_last_error());
                    }
                }
            }
        } else {
            die("El archivo inserts_schema.sql no existe.");
        }

        // Cerrar la conexión
        pg_close($this->conn);
    }

    private function CrearTablasTemporales()
    {
        // Leer y ejecutar el archivo temp_schema.sql
        $tempSchemaFile = __DIR__ . DIRECTORY_SEPARATOR . 'sql' . DIRECTORY_SEPARATOR . 'temp_schema.sql';
        if (file_exists($tempSchemaFile)) {
            $tempSchema = file_get_contents($tempSchemaFile);
            $queries = explode(";", $tempSchema); // Dividir el contenido del archivo en consultas individuales

            foreach ($this->temp_tablas as $index => $tabla) {
                $query = trim($queries[$index]);
                if (!empty($query)) {
                    $result = pg_query($this->conn, $query);
                    if (!$result) {
                        die("Error en la creación de la tabla temporal '{$tabla}': " . pg_last_error());
                    }
                }
            }
        } else {
            die("El archivo temp_schema.sql no existe.");
        }
    }

    private function InsertarDatosTemporales($datos)
    {
        foreach ($datos['Asignaturas'] as $asignatura) {
            $query = "INSERT INTO TempAsignaturas (Plan, Asignatura_id, Asignatura, Nivel, Ciclo) VALUES (
                '{$asignatura[0]}',  -- Plan
                '{$asignatura[1]}',  -- Asignatura_id
                '{$asignatura[2]}',  -- Asignatura
                '{$asignatura[3]}',  -- Nivel
                '{$asignatura[4]}'   -- Ciclo
            )";
            $this->InsertarDatos($query, 'TempAsignaturas');
        }

        foreach ($datos['Planeacion'] as $planeacion) {
            $fechaInicio = DateTime::createFromFormat('d/m/y', $planeacion[15])->format('Y-m-d');
            $fechaFin = DateTime::createFromFormat('d/m/y', $planeacion[16])->format('Y-m-d');

            $query = "INSERT INTO TempPlaneacion (Periodo, Sede, Facultad, Codigo_Depto, Departamento, Id_Asignatura, Asignatura, Seccion, Duracion, Jornada, Cupo, Inscritos, Dia, Hora_Inicio, Hora_Fin, Fecha_Inicio, Fecha_Fin, Lugar, Edificio, Profesor_Principal, RUN, Nombre_Docente, Apellido_Docente_1, Apellido_Docente_2, Jerarquizacion) VALUES (
                '{$planeacion[0]}',  -- Periodo
                '{$planeacion[1]}',  -- Sede
                '{$planeacion[2]}',  -- Facultad
                '{$planeacion[3]}',  -- Codigo_Depto
                '{$planeacion[4]}',  -- Departamento
                '{$planeacion[5]}',  -- Id_Asignatura
                '{$planeacion[6]}',  -- Asignatura
                '{$planeacion[7]}',  -- Seccion
                '{$planeacion[8]}',  -- Duracion
                '{$planeacion[9]}',  -- Jornada
                '{$planeacion[10]}',  -- Cupo
                '{$planeacion[11]}',  -- Inscritos
                '{$planeacion[12]}',  -- Dia
                '{$planeacion[13]}',  -- Hora_Inicio
                '{$planeacion[14]}',  -- Hora_Fin
                '{$fechaInicio}',  -- Fecha_Inicio
                '{$fechaFin}',  -- Fecha_Fin
                '{$planeacion[17]}',  -- Lugar
                '{$planeacion[18]}',  -- Edificio
                '{$planeacion[19]}',  -- Profesor_Principal
                '{$planeacion[20]}',  -- RUN
                '{$planeacion[21]}',  -- Nombre_Docente
                '{$planeacion[22]}',  -- Apellido_Docente_1
                '{$planeacion[23]}',  -- Apellido_Docente_2
                '{$planeacion[24]}'   -- Jerarquizacion
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Docentes_planificados'] as $docente) {
            $query = "INSERT INTO TempDocentesPlanificados (RUN, Nombre, Apellido_P, Telefono, Email_personal, Email_institucional, Dedicacion, Contrato, Diurno, Vespertino, Sede, Carrera, Grado_academico, Jerarquia, Cargo, Estamento) VALUES (
                " . (is_numeric($docente[0]) ? $docente[0] : "NULL") . ",  -- RUN
                '{$docente[1]}',  -- Nombre
                '{$docente[2]}',  -- Apellido_P
                " . (is_numeric($docente[3]) ? $docente[3] : "NULL") . ",  -- Telefono
                '{$docente[4]}',  -- Email_personal
                '{$docente[5]}',  -- Email_institucional
                '{$docente[6]}',  -- Dedicacion
                '{$docente[7]}',  -- Contrato
                '{$docente[8]}',  -- Diurno
                '{$docente[9]}',  -- Vespertino
                '{$docente[10]}',  -- Sede
                '{$docente[11]}',  -- Carrera
                '{$docente[12]}',  -- Grado_academico
                '{$docente[13]}',  -- Jerarquia
                '{$docente[14]}',  -- Cargo
                '{$docente[15]}'   -- Estamento
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Estudiantes'] as $estudiante) {
            // Escapar cadenas con pg_escape_string
            $segundoApellido = pg_escape_string($this->conn, $estudiante[11]);
            $primeraApellido = pg_escape_string($this->conn, $estudiante[10]);

            $query = "INSERT INTO TempEstudiantes (Codigo_Plan, Carrera, Cohorte, Numero_de_alumno, Bloqueo, Causal_Bloqueo, RUN, DV, Nombre_1, Nombre_2, Primer_Apellido, Segundo_Apellido, Logro, Fecha_Logro, Ultima_Carga) VALUES (
                '{$estudiante[0]}',  -- Codigo_Plan
                '{$estudiante[1]}',  -- Carrera
                '{$estudiante[2]}',  -- Cohorte
                {$estudiante[3]},    -- Numero_de_alumno
                '{$estudiante[4]}',  -- Bloqueo
                '{$estudiante[5]}',  -- Causal_Bloqueo
                {$estudiante[6]},    -- RUN
                '{$estudiante[7]}',  -- DV
                '{$estudiante[8]}',  -- Nombre_1
                '{$estudiante[9]}',  -- Nombre_2
                '{$primeraApellido}', -- Primer_Apellido
                '{$segundoApellido}', -- Segundo_Apellido
                '{$estudiante[12]}', -- Logro
                '{$estudiante[13]}', -- Fecha_Logro
                '{$estudiante[14]}'  -- Ultima_Carga
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Notas'] as $nota) {
            // Escapar cadenas con pg_escape_string
            $Apellido_Materno = pg_escape_string($this->conn, $nota[8]);
            $Apellido_Paterno = pg_escape_string($this->conn, $nota[7]);
            $query = "INSERT INTO TempNotas (Codigo_Plan, Plan, Cohorte, Sede, RUN, DV, Nombres, Apellido_Paterno, Apellido_Materno, Numero_de_alumno, Periodo_Asignatura, Codigo_Asignatura, Asignatura, Convocatoria, Calificacion, Nota) VALUES (
                '{$nota[0]}',  -- Codigo_Plan
                '{$nota[1]}',  -- Plan
                '{$nota[2]}',  -- Cohorte
                '{$nota[3]}',  -- Sede
                {$nota[4]},    -- RUN
                '{$nota[5]}',  -- DV
                '{$nota[6]}',  -- Nombres
                '{$Apellido_Paterno}', -- Apellido_Paterno
                '{$Apellido_Materno}', -- Apellido_Materno
                '{$nota[9]}',  -- Numero_de_alumno
                '{$nota[10]}', -- Periodo_Asignatura
                '{$nota[11]}', -- Codigo_Asignatura
                '{$nota[12]}', -- Asignatura
                '{$nota[13]}', -- Convocatoria
                '{$nota[14]}', -- Calificacion
                " . (is_numeric($nota[15]) ? $nota[15] : "NULL") . " -- Nota
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Planes'] as $plan) {
            $iniciovigencia = DateTime::createFromFormat('d/m/y', $plan[8])->format('Y-m-d');
            $query = "INSERT INTO TempPlanes (Codigo_Plan, Facultad, Carrera, Plan, Jornada, Sede, Grado, Modalidad, Inicio_Vigencia) VALUES (
                '{$plan[0]}',  -- Codigo_Plan
                '{$plan[1]}',  -- Facultad
                '{$plan[2]}',  -- Carrera
                '{$plan[3]}',  -- Plan
                '{$plan[4]}',  -- Jornada
                '{$plan[5]}',  -- Sede
                '{$plan[6]}',  -- Grado
                '{$plan[7]}',  -- Modalidad
                '{$iniciovigencia}' -- Inicio_Vigencia
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Prerequisitos'] as $prerequisito) {
            $query = "INSERT INTO TempPrerequisitos (Plan, Asignatura_id, Asignatura, Nivel, Prerequisitos, Prerequisitos_1) VALUES (
                '{$prerequisito[0]}',  -- Plan
                '{$prerequisito[1]}',  -- Asignatura_id
                '{$prerequisito[2]}',  -- Asignatura
                " . (is_numeric($prerequisito[3]) ? $prerequisito[3] : "NULL") . ",  -- Nivel
                '{$prerequisito[4]}',  -- Prerequisitos
                " . (is_numeric($prerequisito[5]) ? $prerequisito[5] : "NULL") . " -- Prerequisitos_1
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Planes_Magia'] as $planMagia) {
            $query = "INSERT INTO TempPlanesMagia (Planes_Vigentes) VALUES (
                '{$planMagia[0]}'  -- Planes_Vigentes
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Planes_Hechiceria'] as $planHechiceria) {
            $query = "INSERT INTO TempPlanesHechiceria (Planes_Vigentes) VALUES (
                '{$planHechiceria[0]}'  -- Planes_Vigentes
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Malla_Magia'] as $mallaMagia) {
            $query = "INSERT INTO TempMallaMagia (Col1, Col2, Col3, Col4, Col5, Col6, Col7, Col8, Col9, Col10) VALUES (
                '{$mallaMagia[0]}',  -- Col1
                '{$mallaMagia[1]}',  -- Col2
                '{$mallaMagia[2]}',  -- Col3
                '{$mallaMagia[3]}',  -- Col4
                '{$mallaMagia[4]}',  -- Col5
                '{$mallaMagia[5]}',  -- Col6
                '{$mallaMagia[6]}',  -- Col7
                '{$mallaMagia[7]}',  -- Col8
                '{$mallaMagia[8]}',  -- Col9
                '{$mallaMagia[9]}'   -- Col10
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }

        foreach ($datos['Malla_Hechiceria'] as $mallaHechiceria) {
            $query = "INSERT INTO TempMallaHechiceria (Col1, Col2, Col3, Col4, Col5, Col6, Col7, Col8, Col9, Col10) VALUES (
                '{$mallaHechiceria[0]}',  -- Col1
                '{$mallaHechiceria[1]}',  -- Col2
                '{$mallaHechiceria[2]}',  -- Col3
                '{$mallaHechiceria[3]}',  -- Col4
                '{$mallaHechiceria[4]}',  -- Col5
                '{$mallaHechiceria[5]}',  -- Col6
                '{$mallaHechiceria[6]}',  -- Col7
                '{$mallaHechiceria[7]}',  -- Col8
                '{$mallaHechiceria[8]}',  -- Col9
                '{$mallaHechiceria[9]}'   -- Col10
            )";
            $this->InsertarDatos($query, 'TempPlaneacion');
        }
    }

    private function InsertarDatos($query, $nombre_tabla)
    {
        $result = pg_query($this->conn, $query);
        if (!$result) {
            die("Error en la inserción de datos en la tabla $nombre_tabla: " . pg_last_error());
        }
    }

    private function CrearTriggers()
    {
        // Eliminar los triggers y funciones si existen
        $queries = [
            "DROP TRIGGER IF EXISTS trigger_actualizar_nota ON Nota",
            "DROP TRIGGER IF EXISTS trigger_actualizar_nota_avance ON Avance_Academico",
            "DROP FUNCTION IF EXISTS actualizar_nota() CASCADE"
        ];
        foreach ($queries as $query) {
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la eliminación de triggers o funciones: " . pg_last_error($this->conn));
            }
        }

        // Crear la función para actualizar la tabla Nota
        $funcion_actualizar_nota = "CREATE OR REPLACE FUNCTION actualizar_nota()
        RETURNS TRIGGER AS $$
        BEGIN
            IF NEW.Calificacion IS NULL OR NEW.Nota IS NULL THEN
                NEW.Resultado := 'Curso Vigente en el período académico';
                NEW.Descripcion := 'curso vigente';
            ELSIF NEW.Calificacion = 'P' THEN
                NEW.Resultado := 'Nota Pendiente';
                NEW.Descripcion := 'Curso incompleto';
            ELSIF NEW.Calificacion = 'NP' THEN
                NEW.Resultado := 'No se Presenta';
                NEW.Descripcion := 'Reprobatorio';
            ELSIF NEW.Calificacion = 'EX' THEN
                NEW.Resultado := 'Eximido';
                NEW.Descripcion := 'Aprobatorio';
            ELSIF NEW.Calificacion = 'A' THEN
                NEW.Resultado := 'Aprobado';
                NEW.Descripcion := 'Aprobatorio';
            ELSIF NEW.Calificacion = 'R' THEN
                NEW.Resultado := 'Reprobado';
                NEW.Descripcion := 'Reprobatorio';
            ELSIF NEW.Calificacion = 'SO' THEN
                NEW.Resultado := 'Sobresaliente';
                NEW.Descripcion := 'Aprobatorio';
            ELSIF NEW.Calificacion = 'MB' THEN
                NEW.Resultado := 'Muy Bueno';
                NEW.Descripcion := 'Aprobatorio';
            ELSIF NEW.Calificacion = 'B' THEN
                NEW.Resultado := 'Bueno';
                NEW.Descripcion := 'Aprobatorio';
            ELSIF NEW.Calificacion = 'SU' THEN
                NEW.Resultado := 'Suficiente';
                NEW.Descripcion := 'Aprobatorio';
            ELSIF NEW.Calificacion = 'I' THEN
                NEW.Resultado := 'Insuficiente';
                NEW.Descripcion := 'Reprobatorio';
            ELSIF NEW.Calificacion = 'M' THEN
                NEW.Resultado := 'Malo';
                NEW.Descripcion := 'Reprobatorio';
            END IF;
            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;";

        $result = pg_query($this->conn, $funcion_actualizar_nota);
        if (!$result) {
            die("Error en la creación de la función actualizar_nota: " . pg_last_error($this->conn));
        }

        // Crear el trigger para la tabla Nota
        $trigger_actualizar_nota = "CREATE TRIGGER trigger_actualizar_nota
        BEFORE INSERT OR UPDATE ON Nota
        FOR EACH ROW
        EXECUTE FUNCTION actualizar_nota();";

        $result = pg_query($this->conn, $trigger_actualizar_nota);
        if (!$result) {
            die("Error en la creación del trigger trigger_actualizar_nota: " . pg_last_error($this->conn));
        }

        // Crear el trigger para la tabla Avance_Academico
        $trigger_actualizar_nota_avance = "CREATE TRIGGER trigger_actualizar_nota_avance
        BEFORE INSERT OR UPDATE ON Avance_Academico
        FOR EACH ROW
        EXECUTE FUNCTION actualizar_nota();";

        $result = pg_query($this->conn, $trigger_actualizar_nota_avance);
        if (!$result) {
            die("Error en la creación del trigger trigger_actualizar_nota_avance: " . pg_last_error($this->conn));
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
