<?php
class Corrector
{
    public $conn;

    public function __construct($env_string)
    {
        // Inicializar conexión
        $this->conn = pg_connect($env_string);

        // Verificar la conexión
        if (!$this->conn) {
            die("Error en la conexión con la base de datos: " .
                pg_last_error());
        }
    }

    public function InitTablas()
    {
        $this->CrearTablasTemporales();
        $this->InsertarDatosTemporales();
    }

    public function CorregirPersonas()
    {
        $query = "INSERT INTO Personas (RUN, DV, Nombre_1, Nombre_2, Apellido_1, Apellido_2, Correo_personal, Correo_institucional, Telefonos)
            SELECT DISTINCT
                TempPlaneacion.RUN AS RUN,
                'X' AS DV,
                COALESCE(TempPlaneacion.Nombre_Docente, 'X') AS Nombre_1,
                'X' AS Nombre_2,
                COALESCE(TempPlaneacion.Apellido_Docente_1, 'X') AS Apellido_1,
                TempPlaneacion.Apellido_Docente_2 AS Apellido_2,
                'X' AS Correo_personal,
                'X' AS Correo_institucional,
                'X' AS Telefonos
            FROM
                TempPlaneacion
            WHERE TempPlaneacion.RUN != ''
            ON CONFLICT (RUN, DV) DO NOTHING -- Evitar duplicados
        ";
        $this->InsertarDatosFinales($query, 'Personas');
    }

    public function CorregirDepto()
    {
        # Se insertan valores desconocidos para que los Cursos no pierdan datos, solo una vez.s
        $query = "INSERT INTO Departamento (Nombre, Codigo, Nombre_Facultad) VALUES ('X', 'X', 'X');";
        $this->InsertarDatosFinales($query, 'Departamento');
    }

    public function CorregirCursos()
    {
        $query = "INSERT INTO Cursos (Sigla_curso, Seccion_curso, Periodo_curso, Nombre, Nivel, Ciclo, Tipo, Oportunidad, Duracion, Nombre_Departamento, Codigo_Departamento, RUN_Academico, DV_Academico, Nombre_Academico, Apellido1_Academico, Apellido2_Academico, Principal, Plan_curso)
            SELECT DISTINCT 
                COALESCE(TempAsignaturas.Asignatura_id, TempNotas.Codigo_Asignatura) AS Sigla_curso, -- En caso de haber cursos que no estén en TempAsignaturas
                0 AS Seccion_curso, -- se agregan solo cursos viejos, sin sección determinada
                COALESCE(TempNotas.Periodo_Asignatura, 'X')  AS Periodo_curso,
                COALESCE(TempAsignaturas.Asignatura, TempNotas.Asignatura) AS Nombre, -- En caso de haber cursos que no estén en TempAsignaturas
                TempAsignaturas.Nivel AS Nivel,
                TempAsignaturas.Ciclo AS Ciclo,
                'X' AS Tipo, -- Valor desconocido.
                TempNotas.Convocatoria AS Oportunidad,
                -- Se desconoce toda la información de los cursos antiguos sobre los docentes
                'X' AS Duracion,
                'X' AS Nombre_Departamento,
                'X' AS Codigo_Departamento,
                null AS RUN_Academico,
                null AS DV, 
                null AS Nombre_Academico,
                null AS Apellido1_Academico,
                null AS Apellido2_Academico,
                null AS Principal,
                TempAsignaturas.Plan AS Plan_curso
            FROM
                TempAsignaturas
            FULL OUTER JOIN TempNotas ON TempAsignaturas.Asignatura_id = TempNotas.Codigo_Asignatura
            ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso) DO NOTHING -- Evitar duplicados 
        ";
        $this->InsertarDatosFinales($query, 'Cursos');

        $query = "INSERT INTO Cursos (Sigla_curso, Seccion_curso, Periodo_curso, Nombre, Nivel, Ciclo, Tipo, Oportunidad, Duracion, Nombre_Departamento, Codigo_Departamento, RUN_Academico, DV_Academico, Nombre_Academico, Apellido1_Academico, Apellido2_Academico, Principal, Plan_curso)
            SELECT DISTINCT ON (TempPlaneacion.Id_Asignatura, TempPlaneacion.Seccion, TempPlaneacion.Periodo)
                TempPlaneacion.Id_Asignatura AS Sigla_curso,
                TempPlaneacion.Seccion AS Seccion_curso,
                TempPlaneacion.Periodo AS Periodo_curso,
                TempPlaneacion.Asignatura AS Nombre,
                COALESCE(TempAsignaturas.Nivel, 'X') AS Nivel,
                COALESCE(TempAsignaturas.Ciclo, 'X') AS Ciclo,
                'X' AS Tipo, -- Valor desconocido.
                'X' AS Oportunidad, -- Valor desconocido.
                TempPlaneacion.Duracion AS Duracion,
                TempPlaneacion.Departamento AS Nombre_Departamento,
                TempPlaneacion.Codigo_Depto AS Codigo_Departamento,
                TempPlaneacion.RUN AS RUN_Academico,
                'X' AS DV_Academico, 
                TempPlaneacion.Nombre_Docente AS Nombre_Academico,
                TempPlaneacion.Apellido_Docente_1 AS Apellido1_Academico,
                TempPlaneacion.Apellido_Docente_2 AS Apellido2_Academico,
                TempPlaneacion.Profesor_Principal AS Principal,
                TempAsignaturas.Plan AS Plan_curso
            FROM
                TempPlaneacion
            LEFT JOIN TempAsignaturas ON TempPlaneacion.Id_Asignatura = TempAsignaturas.Asignatura_id
            ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso) DO UPDATE -- Actualizar valores
            SET RUN_Academico = EXCLUDED.RUN_Academico,
                Nombre_Academico = EXCLUDED.Nombre_Academico,
                Apellido1_Academico = EXCLUDED.Apellido1_Academico,
                Apellido2_Academico = EXCLUDED.Apellido2_Academico,
                Principal = EXCLUDED.Principal,
                Ciclo = EXCLUDED.Ciclo
        ";
        $this->InsertarDatosFinales($query, 'Cursos');

        // Crear Triggers para actualizar los datos de la tabla Cursos
        $this->CrearTriggers();
        // Actualizar datos de la tabla Cursos con los triggers
        $query = "UPDATE Cursos SET 
            Sigla_curso = Sigla_curso, 
            Seccion_curso = Seccion_curso, 
            Periodo_curso = Periodo_curso, 
            Nombre = Nombre, 
            Nivel = Nivel, 
            Ciclo = Ciclo, 
            Tipo = Tipo, 
            Oportunidad = Oportunidad, 
            Duracion = Duracion, 
            Nombre_Departamento = Nombre_Departamento, 
            Codigo_Departamento = Codigo_Departamento, 
            RUN_Academico = RUN_Academico, 
            DV_Academico = DV_Academico, 
            Nombre_Academico = Nombre_Academico, 
            Apellido1_Academico = Apellido1_Academico, 
            Apellido2_Academico = Apellido2_Academico, 
            Principal = Principal,
            Plan_curso = Plan_curso;";
        $result = pg_query($this->conn, $query);
        if (!$result) {
            die("Error en la actualización de datos en la tabla Cursos: " . pg_last_error($this->conn));
        }

        // Insertar datos de tempPrequisitos en la tabla Cursos
        $query = "INSERT INTO Cursos (Sigla_curso, Seccion_curso, Periodo_curso, Nombre, Nivel, Ciclo, Tipo, Oportunidad, Duracion, Nombre_Departamento, Codigo_Departamento, RUN_Academico, DV_Academico, Nombre_Academico, Apellido1_Academico, Apellido2_Academico, Principal, Plan_curso)
            SELECT DISTINCT
                COALESCE(TempAsignaturas.Asignatura_id, TempPrerequisitos.Asignatura_id) AS Sigla_curso,
                0 AS Seccion_curso, -- seccion no considerada pues no se conocen periodos antiguos
                'X' AS Periodo_curso,
                COALESCE(TempAsignaturas.Asignatura, TempPrerequisitos.Asignatura) AS Nombre,
                COALESCE(TempAsignaturas.Nivel, 'X') AS Nivel,
                COALESCE(TempAsignaturas.Ciclo, 'X') AS Ciclo,
                'X' AS Tipo, -- Valor desconocido.
                'X' AS Oportunidad, -- Valor desconocido.
                'X' AS Duracion, -- Valor desconocido.
                'X' AS Nombre_Departamento, -- Valor desconocido.
                'X' AS Codigo_Departamento, -- Valor desconocido.
                null AS RUN_Academico,
                null AS DV_Academico, 
                null AS Nombre_Academico,
                null AS Apellido1_Academico,
                null AS Apellido2_Academico,
                null AS Principal,
                TempAsignaturas.Plan AS Plan_curso
            FROM
                TempPrerequisitos
            LEFT JOIN TempAsignaturas ON TempPrerequisitos.Asignatura_id = TempAsignaturas.Asignatura_id
            ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso) DO NOTHING -- Evitar duplicados
        ";
        $this->InsertarDatosFinales($query, 'Cursos');
    }

    public function CorregirAcademicos()
    {
        // Insertar datos en la tabla Academicos
        // agregar desde planeacion
        $query_academicos = "INSERT INTO Academicos (RUN, DV, Estamento, Grado_academico, Contrato, Jerarquia, Jornada)
            SELECT DISTINCT
                TempPlaneacion.RUN AS RUN,
                'X' AS DV,
                'Académico' AS Estamento,
                'X' AS Grado_academico,
                'X' AS Contrato,
                TempPlaneacion.Jerarquizacion AS Jerarquia,
                'X' AS Jornada
            FROM
                TempPlaneacion
            WHERE TempPlaneacion.RUN != ''
            AND TempPlaneacion.profesor_principal = 'S'
            AND TempPlaneacion.Nombre_Docente IS NOT NULL
            AND TempPlaneacion.Nombre_Docente != 'POR'
            AND TempPlaneacion.Apellido_Docente_1 != 'DESIGNAR'
            AND TempPlaneacion.Apellido_Docente_1 != 'POR'
            AND TempPlaneacion.Nombre_Docente != 'DESIGNAR'
            ON CONFLICT (RUN, DV) DO NOTHING -- Evitar duplicados
        ";
        $this->InsertarDatosFinales($query_academicos, 'Academicos');

        $query_academicos = "INSERT INTO Academicos (RUN, DV, Estamento, Grado_academico, Contrato, Jerarquia, Jornada)
            SELECT DISTINCT
                TempDocentesPlanificados.RUN AS RUN,
                'X' AS DV,
                'Académico' AS Estamento,
                TempDocentesPlanificados.Grado_academico AS Grado_academico,
                TempDocentesPlanificados.Contrato AS Contrato,
                TempDocentesPlanificados.Jerarquia AS Jerarquia,
                'X' AS Jornada
            FROM
                TempDocentesPlanificados
            --Agregar desde docente planificados cuando Jerarquia contiene docente, 
            -- por ej: ASISTENTE DOCENTE, PROFESOR ASOCIADO DOCENTE o que contenga Profesor 
            WHERE TempDocentesPlanificados.Jerarquia ILIKE '%DOCENTE%'
            OR TempDocentesPlanificados.Jerarquia ILIKE '%PROFESOR%'
            ON CONFLICT (RUN, DV) DO NOTHING -- Evitar duplicados
        ";
        $this->InsertarDatosFinales($query_academicos, 'Academicos');
    }

    public function CorregirNotas()
    {
        // Insertar datos en la tabla Nota
        $query = "INSERT INTO Nota (Sigla_curso, Seccion_curso, Periodo_curso, Numero_de_estudiante, RUN, DV, Nota, Descripcion, Resultado, Calificacion)
            SELECT DISTINCT
                COALESCE(TempAsignaturas.Asignatura_id, TempNotas.Codigo_Asignatura) AS Sigla_curso,
                0 AS Seccion_curso, -- seccion no considerada pues no se conocen periodos antiguos
                COALESCE(TempNotas.Periodo_Asignatura, 'X') AS Periodo_curso,
                TempNotas.Numero_de_alumno AS Numero_de_estudiante,
                TempNotas.RUN AS RUN,
                TempNotas.DV AS DV,
                TempNotas.Nota AS Nota,
                '' AS Descripcion, -- Valor a agregar con la función actualizar_nota
                '' AS Resultado, -- Valor a agregar con la función actualizar_nota
                TempNotas.Calificacion AS Calificacion
            FROM
                TempAsignaturas
            FULL OUTER JOIN TempNotas ON TempAsignaturas.Asignatura_id = TempNotas.Codigo_Asignatura
            -- Se descartan notas en las que no se conozca el número de estudiante, el RUN y el DV
            WHERE TempNotas.Numero_de_alumno IS NOT NULL AND TempNotas.RUN IS NOT NULL AND TempNotas.DV IS NOT NULL
            ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante) DO NOTHING -- Evitar duplicados
        ";
        $this->InsertarDatosFinales($query, 'Nota');
    }

    public function CorregirAvanceAcademico()
    {
        // Insertar datos en la tabla Avance_Academico
        $query = "INSERT INTO Avance_Academico (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante, Periodo_Oferta, Nota, Descripcion, Resultado, Calificacion, Ultima_Carga, Ultimo_Logro, Fecha_logro)
                  SELECT DISTINCT
                    COALESCE(TempAsignaturas.Asignatura_id, TempNotas.Codigo_Asignatura) AS Sigla_curso,
                    0 AS Seccion_curso,
                    TempNotas.Periodo_Asignatura AS Periodo_curso,
                    TempNotas.RUN AS RUN,
                    TempNotas.DV AS DV,
                    TempNotas.Numero_de_alumno AS Numero_de_estudiante,
                    TempNotas.Periodo_Asignatura AS Periodo_Oferta,
                    TempNotas.Nota AS Nota,
                    '' AS Descripcion, -- Valor a agregar con la función actualizar_nota
                    '' AS Resultado, -- Valor a agregar con la función actualizar_nota
                    TempNotas.Calificacion AS Calificacion,
                    TempEstudiantes.Ultima_Carga AS Ultima_Carga,
                    TempEstudiantes.Logro AS Ultimo_Logro,
                    TempEstudiantes.Fecha_Logro AS Fecha_logro
                  FROM
                    TempNotas
                  LEFT JOIN TempAsignaturas ON TempAsignaturas.Asignatura_id = TempNotas.Codigo_Asignatura
                  LEFT JOIN TempEstudiantes ON TempNotas.RUN = TempEstudiantes.RUN 
                  AND TempNotas.Numero_de_alumno = TempEstudiantes.Numero_de_alumno AND TempNotas.DV = TempEstudiantes.DV
                  WHERE TempNotas.Numero_de_alumno IS NOT NULL AND TempNotas.RUN IS NOT NULL AND TempNotas.DV IS NOT NULL
                  ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante) DO NOTHING -- Evitar duplicados
        ";
        $this->InsertarDatosFinales($query, 'Avance_Academico');
    }

    public function CorregirProgramacionAcademica()
    {
        // Insertar datos en la tabla Programacion_Academica
        $query = "INSERT INTO Programacion_Academica (Sigla_curso, Seccion_curso, Periodo_Oferta, Cupos, Sala, Hora_Inicio, Hora_Fin, Fecha_Inicio, Fecha_Fin, Inscritos)
            SELECT DISTINCT
                TempAsignaturas.Asignatura_id AS Sigla_curso,
                0 AS Seccion_curso, -- seccion no considerada pues no se conocen periodos antiguos
                TempNotas.Periodo_Asignatura AS Periodo_Oferta,
                0 AS Cupos,
                'X' AS Sala,
                -- Se desconocen las horas de inicio y fin (tipo time)
                CAST('00:00:00' AS time) AS Hora_Inicio,
                CAST('00:00:00' AS time) AS Hora_Fin,
                -- Se desconocen las fechas de inicio y fin (tipo date)
                CAST('2000-01-01' AS date) AS Fecha_Inicio,
                CAST('2000-01-01' AS date) AS Fecha_Fin,
                0 AS Inscritos
            FROM
                TempNotas
            JOIN TempAsignaturas ON TempNotas.Codigo_Asignatura = TempAsignaturas.Asignatura_id
            ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_Oferta) DO NOTHING -- Evitar duplicados
        ";
        $this->InsertarDatosFinales($query, 'Programacion_Academica');
    }

    public function CorregirCursosPrerrequisitos()
    {
        // Insertar datos en la tabla Cursos_Prerequisitos
        $query = "INSERT INTO Cursos_Prerequisitos (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_prerequisito, Ciclo)
            SELECT DISTINCT
                TempAsignaturas.Asignatura_id AS Sigla_curso,
                0 AS Seccion_curso,
                'X' AS Periodo_curso,
                TempPrerequisitos.Prerequisitos AS Sigla_prerequisito,
                TempAsignaturas.Ciclo AS Ciclo
            FROM
                TempPrerequisitos
            JOIN TempAsignaturas ON TempAsignaturas.Asignatura_id = TempPrerequisitos.Asignatura_id
            ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_prerequisito) DO NOTHING -- Evitar duplicados
        ";
        $this->InsertarDatosFinales($query, 'Cursos_Prerequisitos');
    }

    public function closeConn()
    {
        // Cerrar la conexión a la base de datos
        pg_close($this->conn);
        echo "Procesamiento completado.\n";
    }

    //Función para validar RUN
    private function validarRUN($run)
    {
        $run = trim($run);
        if (is_numeric($run)) { #asi redondeamos el 0.1
            $run = round(floatval($run));
        }
        $run = (string)$run;
        #Con esta parte nos aseguramos que el RUN sea numerico y positivo
        if (ctype_digit($run) && strlen($run) >= 7 && strlen($run) <= 8) {
            return $run;
        } else {
            return false;
        }
    }

    //Función para validar Nombre
    private function validarNombre($nombre)
    {
        // Expresión regular que permite letras, espacios, guiones y apóstrofes
        return preg_match("/^[a-zA-ZáéíóúÁÉÍÓÚñÑ' -]+$/u", $nombre);
    }

    //Función para corregir telefonos: 
    private function corregirTelefono($telefono)
    {
        // Eliminar caracteres no numéricos
        $telefono = preg_replace('/\D/', '', $telefono);
        // Si el teléfono tiene 8 dígitos, agregar un '9' al inicio
        if (strlen($telefono) == 8) {
            $telefono = '9' . $telefono;
        }
        return $telefono;
    }

    //Función para manejar el ENCODING de los caracteres 
    private function convertirEncoding($cadena)
    {
        // Detectar la codificación de la cadena
        $encoding = mb_detect_encoding($cadena, 'UTF-8, ISO-8859-1', true);
        // Convertir a UTF-8 
        if ($encoding != 'UTF-8') {
            $cadena = mb_convert_encoding($cadena, 'UTF-8', $encoding);
        }
        return $cadena;
    }

    private function registrarError($tabla, $registro, $motivo)
    {
        // Definir la ruta de la carpeta data
        $ruta_carpeta = __DIR__ . "/data";

        // Crear la carpeta data si no existe
        if (!file_exists($ruta_carpeta)) {
            mkdir($ruta_carpeta, permissions: 0777, recursive: true);
        }

        // Definir la ruta del archivo de errores dentro de la carpeta data
        $ruta_archivo = $ruta_carpeta . "/errores_{$tabla}.csv";

        // Verificar si el archivo ya existe para escribir las cabeceras
        $es_nuevo = !file_exists($ruta_archivo);

        // Abrir el archivo en modo append
        $fp = fopen($ruta_archivo, 'a');
        if ($fp === false) {
            die("No se pudo abrir el archivo de errores: $ruta_archivo");
        }
        // Si es un archivo nuevo, escribir la cabecera
        if ($es_nuevo) {
            // Obtener las claves del arreglo para las cabeceras
            $cabecera = array_keys($registro);
            $cabecera[] = 'Motivo'; // Añadir una columna para el motivo del error
            fputcsv($fp, $cabecera);
        }
        // Añadir el motivo al registro
        $registro[] = $motivo; // Cambiar de $registro['Motivo'] a añadir al final
        // Escribir el registro en el archivo CSV
        fputcsv($fp, $registro);
        // Cerrar el archivo
        fclose($fp);
    }

    private function CrearTablasTemporales()
    {
        $queries = [
            "CREATE TEMP TABLE TempAsignaturas (
                Plan VARCHAR(100),
                Asignatura_id VARCHAR(100),
                Asignatura VARCHAR(100),
                Nivel VARCHAR(100),
                Ciclo VARCHAR(100)
            )",
            "CREATE TEMP TABLE TempPlaneacion (
                Periodo VARCHAR(100),
                Sede VARCHAR(100),
                Facultad VARCHAR(100),
                Codigo_Depto VARCHAR(100),
                Departamento VARCHAR(100),
                Id_Asignatura VARCHAR(100),
                Asignatura VARCHAR(100),
                Seccion INT,
                Duracion VARCHAR(100),
                Jornada VARCHAR(100),
                Cupo INT,
                Inscritos INT,
                Dia VARCHAR(100),
                Hora_Inicio TIME,
                Hora_Fin TIME,
                Fecha_Inicio DATE,
                Fecha_Fin DATE,
                Lugar VARCHAR(100),
                Edificio VARCHAR(100),
                Profesor_Principal VARCHAR(100),
                RUN VARCHAR(100),
                Nombre_Docente VARCHAR(100),
                Apellido_Docente_1 VARCHAR(100),
                Apellido_Docente_2 VARCHAR(100),
                Jerarquizacion VARCHAR(100)
            )",
            "CREATE TEMP TABLE TempEstudiantes (
                Codigo_Plan VARCHAR(100),
                Carrera VARCHAR(100),
                Cohorte VARCHAR(100),
                Numero_de_alumno INT,
                Bloqueo varchar(1),
                Causal_Bloqueo TEXT,
                RUN VARCHAR(100),
                DV VARCHAR(2),
                Nombre_1 VARCHAR(100),
                Nombre_2 VARCHAR(100),
                Primer_Apellido VARCHAR(100),
                Segundo_Apellido VARCHAR(100),
                Logro VARCHAR(100),
                Fecha_Logro VARCHAR(100), -- Periodo (2024-2)
                Ultima_Carga VARCHAR(100) -- Periodo (2024-2)
            )",
            "CREATE TEMP TABLE TempNotas (
                Codigo_Plan VARCHAR(100),
                Plan VARCHAR(100),
                Cohorte VARCHAR(100),
                Sede VARCHAR(100),
                RUN VARCHAR(100),
                DV VARCHAR(2),
                Nombres VARCHAR(100),
                Apellido_Paterno VARCHAR(100),
                Apellido_Materno VARCHAR(100),
                Numero_de_alumno INT,
                Periodo_Asignatura VARCHAR(100),
                Codigo_Asignatura VARCHAR(100),
                Asignatura VARCHAR(100),
                Convocatoria VARCHAR(100),
                Calificacion VARCHAR(100),
                Nota FLOAT
            )",
            "CREATE TEMP TABLE TempDocentesPlanificados (
                RUN VARCHAR(100),
                Nombre VARCHAR(100),
                Apellido_P VARCHAR(100),
                Telefono INT,
                Email_personal VARCHAR(100),
                Email_institucional VARCHAR(100),
                Dedicacion VARCHAR(100),
                Contrato VARCHAR(100),
                Diurno varchar(100),
                Vespertino varchar(100),
                Sede VARCHAR(100),
                Carrera VARCHAR(100),
                Grado_academico VARCHAR(100),
                Jerarquia VARCHAR(100),
                Cargo VARCHAR(100),
                Estamento VARCHAR(100)
            )",
            "CREATE TEMP TABLE TempPlanes (
                Codigo_Plan VARCHAR(100),
                Facultad VARCHAR(100),
                Carrera VARCHAR(100),
                Plan VARCHAR(100),
                Jornada VARCHAR(100),
                Sede VARCHAR(100),
                Grado VARCHAR(100),
                Modalidad VARCHAR(100),
                Inicio_Vigencia DATE
            )",
            "CREATE TEMP TABLE TempPrerequisitos (
                Plan VARCHAR(100),
                Asignatura_id VARCHAR(100),
                Asignatura VARCHAR(100),
                Nivel VARCHAR(100),
                Prerequisitos VARCHAR(100),
                Prerequisitos_1 VARCHAR(100)
            )",
            "CREATE TEMP TABLE TempPlanesMagia (
                Planes_Vigentes VARCHAR(100)
            )",
            "CREATE TEMP TABLE TempPlanesHechiceria (
                Planes_Vigentes VARCHAR(100)
            )",
            "CREATE TEMP TABLE TempMallaMagia (
                Col1 VARCHAR(100),
                Col2 VARCHAR(100),
                Col3 VARCHAR(100),
                Col4 VARCHAR(100),
                Col5 VARCHAR(100),
                Col6 VARCHAR(100),
                Col7 VARCHAR(100),
                Col8 VARCHAR(100),
                Col9 VARCHAR(100),
                Col10 VARCHAR(100)
            )",
            "CREATE TEMP TABLE TempMallaHechiceria (
                Col1 VARCHAR(100),
                Col2 VARCHAR(100),
                Col3 VARCHAR(100),
                Col4 VARCHAR(100),
                Col5 VARCHAR(100),
                Col6 VARCHAR(100),
                Col7 VARCHAR(100),
                Col8 VARCHAR(100),
                Col9 VARCHAR(100),
                Col10 VARCHAR(100)
            )"
        ];
        foreach ($queries as $query) {
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la creación de la tabla temporal: " . pg_last_error());
            }
        }
    }

    private function InsertarDatosTemporales()
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
        $datos = array_combine($nombre_archivos, $datos_array);

        foreach ($datos['Asignaturas'] as $asignatura) {
            // ACA QUIERO VALIDAR DATOS Y TIRAR ERRORES AL ARCHIVO DE ERROR .CSV
            if (empty($asignatura[0]) || empty($asignatura[1])) {
                $this->registrarError('Asignaturas', $asignatura, 'Plan o Asignatura_id vacío');
                continue; // Saltar al siguiente registro
            }

            $query = "INSERT INTO TempAsignaturas (Plan, Asignatura_id, Asignatura, Nivel, Ciclo) VALUES (
                '{$asignatura[0]}',  -- Plan
                '{$asignatura[1]}',  -- Asignatura_id
                '{$asignatura[2]}',  -- Asignatura
                '{$asignatura[3]}',  -- Nivel
                '{$asignatura[4]}'   -- Ciclo
            )";
            $result = pg_query($this->conn, $query);
            if (!$result) {
                $error = pg_last_error($this->conn);
                $this->registrarError('Asignaturas', $asignatura, "Error de inserción: $error");
                die("Error en la inserción de datos en la tabla temporal TempAsignaturas: " . pg_last_error());
            }
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
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempPlaneacion: " . pg_last_error());
            }
        }

        foreach ($datos['Docentes_planificados'] as $docente) {
            // ACA QUIERO VALIDAR DATOS Y TIRAR ERRORES AL ARCHIVO DE ERROR .CSV
            $RUN = $this->validarRUN($docente[0]);
            if ($RUN === false) {
                $this->registrarError('Docentes_planificados', $docente, 'RUN inválido');
                continue; // Saltar al siguiente registro
            }


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
            $result = pg_query($this->conn, $query);
            if (!$result) {
                $error = pg_last_error($this->conn);
                $this->registrarError('Docentes_planificados', $docente, "Error de inserción: $error");
                die("Error en la inserción de datos en la tabla temporal TempDocentesPlanificados: " . pg_last_error());
            }
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
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempEstudiantes: " . pg_last_error());
            }
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
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempNotas: " . pg_last_error());
            }
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
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempPlanes: " . pg_last_error());
            }
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
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempPrerequisitos: " . pg_last_error());
            }
        }

        foreach ($datos['Planes_Magia'] as $planMagia) {
            $query = "INSERT INTO TempPlanesMagia (Planes_Vigentes) VALUES (
                '{$planMagia[0]}'  -- Planes_Vigentes
            )";
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempPlanesMagia: " . pg_last_error());
            }
        }

        foreach ($datos['Planes_Hechiceria'] as $planHechiceria) {
            $query = "INSERT INTO TempPlanesHechiceria (Planes_Vigentes) VALUES (
                '{$planHechiceria[0]}'  -- Planes_Vigentes
            )";
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempPlanesHechiceria: " . pg_last_error());
            }
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
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempMallaMagia: " . pg_last_error());
            }
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
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la inserción de datos en la tabla temporal TempMallaHechiceria: " . pg_last_error());
            }
        }
    }

    private function InsertarDatosFinales($query, $nombre_tabla)
    {
        $result = pg_query($this->conn, $query);
        if (!$result) {
            # PARA REGISTRAR ERRORES .CSV
            $registro_vacio = array_fill(0, 1, ''); // Ajusta según la estructura de la tabla
            $this->registrarError($nombre_tabla, $registro_vacio, "Error en la inserción: " . pg_last_error($this->conn));

            die("Error en la inserción de datos en la tabla $nombre_tabla: " . pg_last_error($this->conn));
        } else {
            // Obtener el número de filas afectadas
            $filas_insertadas = pg_affected_rows($result);
            echo "Filas insertadas en $nombre_tabla: $filas_insertadas\n";
        }
    }

    private function CrearTriggers()
    {
        // Eliminar los triggers si existen
        $queries = [
            "DROP TRIGGER IF EXISTS before_update_cursos ON Cursos",
            "DROP FUNCTION IF EXISTS before_update_cursos_func() CASCADE",
        ];
        foreach ($queries as $query) {
            $result = pg_query($this->conn, $query);
            if (!$result) {
                die("Error en la eliminación de triggers: " . pg_last_error($this->conn));
            }
        }

        // Crear la función para actualizar la tabla Cursos
        $funcion_actualizar_cursos = "CREATE OR REPLACE FUNCTION before_update_cursos_func()
        RETURNS TRIGGER AS $$
        BEGIN
            IF NEW.RUN_Academico IS NULL OR TRIM(NEW.RUN_Academico) = '' THEN
                NEW.RUN_Academico := NEW.Codigo_departamento;
            END IF;
            IF TRIM(NEW.Nombre_Academico) = '' THEN
                NEW.Nombre_Academico := NULL;
            END IF;
            IF TRIM(NEW.Apellido1_Academico) = '' THEN
                NEW.Apellido1_Academico := NULL;
            END IF;
            IF TRIM(NEW.Apellido2_Academico) = '' OR NEW.Apellido2_Academico = '0' THEN
                NEW.Apellido2_Academico := NULL;
            END IF;
            IF TRIM(NEW.Ciclo) = '' THEN
                NEW.Ciclo := NULL;
            END IF;
            IF TRIM(NEW.Principal) = '' THEN
                NEW.Principal := NULL;
            END IF;
            IF TRIM(NEW.Nivel) = '' THEN
                NEW.Nivel := NULL;
            END IF;
            RETURN NEW;
        END;
        $$ LANGUAGE plpgsql;";

        $result = pg_query($this->conn, $funcion_actualizar_cursos);
        if (!$result) {
            die("Error en la creación de la funcion_actualizar_cursos: " . pg_last_error($this->conn));
        }

        // Crear el trigger para la tabla Cursos
        $trigger_cursos = "CREATE TRIGGER before_update_cursos
        BEFORE UPDATE ON Cursos
        FOR EACH ROW
        EXECUTE FUNCTION before_update_cursos_func();";

        $result = pg_query($this->conn, $trigger_cursos);
        if (!$result) {
            die("Error en la creación del trigger(cursos): " . pg_last_error($this->conn));
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
