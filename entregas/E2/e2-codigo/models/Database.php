<?php

class Database {
    private $connection;

    public function __construct() {
        
        // Conectar a PostgreSQL
        $this->connection = pg_connect("host=localhost port=5432 dbname=grupo15 user=grupo15 password=Elefante$15");
        
        if (!$this->connection) {
            die("Error en la conexión con la base de datos: " . pg_last_error());
        }
    }

    public function consulta1() {
        $sql = "SELECT COUNT(DISTINCT Numero_de_estudiante) AS total_estudiantes FROM Avance_Academico aa WHERE aa.Periodo_Oferta = '2024-02'";
        $result = pg_query($this->connection, $sql);
        if (!$result) {
            die("Error en la consulta: " . pg_last_error());
        }
        return pg_fetch_assoc($result);
    }

    public function close() {
        pg_close($this->connection);
    }

}

?>
