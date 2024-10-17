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

    public function obtenerEstudiante() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_estudiante'])) {
            $numeroEstudiante = $_POST['numero_estudiante'];

            $db = new Database();
            $historial = $db->obtenerEstudiantePorNumero($numeroEstudiante);
            $db->close();

            // Incluir la vista y pasarle los resultados
            include 'views/menu.php';
        } else {
            // Redirigir al menú si no se envió el formulario correctamente
            $this->menu();
        }
    }

    public function specialMenu() {
        echo 'xddd';

        include 'views/special_menu.php';
    }

    public function registrarUsuario() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = $_POST['correo'];
            $clave = $_POST['clave'];
    
            $db = new Database();
    
            // Verificar si el correo está en la tabla Personas
            $persona = $db->obtenerPersonaPorCorreo($correo);
    
            if ($persona) {
                $rol = '';
    
                // Verificar si es académico o administrativo
                $esAcademico = $db->esAcademico($persona['RUN']);
                $esAdministrativo = $db->esAdministrativo($persona['RUN']);
    
                if ($esAcademico) {
                    $rol = 'Academico/Administrativo';
                } elseif ($esAdministrativo) {
                    $rol = 'Administrativo';
                }
    
                if ($rol !== '') {
                    // Guardar en el CSV (encriptar la clave antes de guardar)
                    $this->guardarEnCSV($correo, password_hash($clave, PASSWORD_BCRYPT), $rol);
                    echo "Usuario registrado correctamente.";
                } else {
                    echo "El correo no está asociado a un académico o administrativo.";
                }
            } else {
                echo "El correo no está registrado en la base de datos.";
            }
    
            $db->close();
        }
    }
    
    // Función para guardar en el CSV
    private function guardarEnCSV($correo, $clave, $rol) {
        $file = fopen('data/users.csv', 'a'); // Abre el archivo en modo de añadir
        fputcsv($file, [$correo, $clave, $rol]);
        fclose($file);
    }

    
    
}
?>

