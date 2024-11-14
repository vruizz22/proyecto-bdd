<?php
class Cargador
{
    public $conn_grupo15e3;
    public $conn_e3profesores;
    public $tablas;

    public function __construct($env_string_1, $env_string_2)
    {
        // Inicializar conexiones
        $this->conn_grupo15e3 = pg_connect($env_string_1);
        $this->conn_e3profesores = pg_connect($env_string_2);

        // Verificar conexiones
        if (!$this->conn_grupo15e3 || !$this->conn_e3profesores) {
            die("Error de conexión: " . pg_last_error());
        }

        $this->tablas = array("Profesores", "Jerarquia");
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

    public function cerrarConexiones()
    {
        pg_close($this->conn_grupo15e3);
        pg_close($this->conn_e3profesores);
    }
}
