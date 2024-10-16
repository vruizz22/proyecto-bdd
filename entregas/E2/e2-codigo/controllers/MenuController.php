<?php
class MenuController {
    public function menu() {
        // Solo se muestra el menú inicial
        include 'views/menu.php';
    }

    public function realizarConsulta() {
        $db = new Database();

        // Consulta 1: Contar estudiantes en 2024-2
        $result1 = $db->consulta1();
        $total_estudiantes = $result1['total_estudiantes'];

        // Incluir la vista y pasarle los resultados
        include 'views/menu.php';
    }

    public function specialMenu() {
        echo 'xddd';
    }
}
?>
