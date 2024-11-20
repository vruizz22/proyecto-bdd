<?php

class MenuController
{
    public function menu()
    {
        // Solo se muestra el menú inicial
        include 'views/menu.php';
    }

    public function realizarConsulta()
    {
        $db = new Database();

        // Consulta 1: Contar estudiantes en 2024-2
        $result1 = $db->consulta1();
        $total_estudiantes = $result1['total_estudiantes'];

        // Incluir la vista y pasarle los resultados
        include 'views/menu.php';

        //
    }

    public function obtenerPorcentajeAprobacion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['periodo'])) {
            $periodo = $_POST['periodo'];

            $db = new Database();
            $porcentajeAprobacion = $db->PorcentajeAprobacion($periodo);
            $db->close();

            // Incluir la vista y pasarle los resultados
            include 'views/menu.php';
        } else {
            // Redirigir al menú si no se envió el formulario correctamente
            $this->menu();
        }
    }

    public function obtenerPromedioPorcentajeAprobacion()
    {
        // codigo curso
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['codigo_curso'])) {
            $codigo_curso = $_POST['codigo_curso'];

            $db = new Database();
            $promedioPorcentajeAprobacion = $db->PromedioPorcentajeAprobacion($codigo_curso);
            $db->close();

            // Incluir la vista y pasarle los resultados
            include 'views/menu.php';
        } else {
            // Redirigir al menú si no se envió el formulario correctamente
            $this->menu();
        }
    }

    public function obtenerTomaRamos()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['numero_estudiante'])) {
            $numeroEstudiante = $_POST['numero_estudiante'];

            # FUnciona con 2114
            $db = new Database();
            $tomaRamos = $db->TomaRamos($numeroEstudiante);
            $db->close();

            // Incluir la vista y pasarle los resultados
            include 'views/menu.php';
        } else {
            // Redirigir al menú si no se envió el formulario correctamente
            $this->menu();
        }
    }

    public function obtenerEstudiante()
    {
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

    public function VerActa()
    {
        // Funcion para visualizar la funcion que muestra una View de la tabla temp ACTA
        $db = new Database();
        $cargador = new Cargador();
        $cargador->CrearTablasTemporales();
        $acta = $db->VerActa();
        $db->close();
        // incluir la vista y pasarle los resultados
        include 'views/menu.php';
    }

    public function obtenerInterfaz()
    {

        // Obtener A T C, textos por input 

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['A']) && isset($_POST['T']) && isset($_POST['C'])) {
            $A = $_POST['A'];
            $T = $_POST['T'];
            $C = $_POST['C'];

            $db = new Database();
            $interfaz = $db->Interfaz($A, $T, $C);
            $db->close();

            // incluir la vista y pasarle los resultados
            include 'views/menu.php';
        }
    }

    public function specialMenu()
    {
        // llamar a registrarUsuario
        include 'views/special_menu.php';
    }

    public function Interfaz()
    {
        include 'views/interfaz.php';
    }

    public function registrarUsuario()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
            $correo = $_POST['email'];
            $clave = $_POST['password'];

            $db = new Database();

            // Verificar si el correo está en la tabla Personas
            $persona = $db->obtenerPersonaPorCorreo($correo);

            if ($persona) {
                // Verificar si es académico o administrativo
                $esAcademico = $db->esAcademico($persona['run']);
                $esAdministrativo = $db->esAdministrativo($persona['run']);

                $rol = $esAcademico ? 'Academico/Administrativo' : ($esAdministrativo ? 'Administrativo' : 'x');

                $this->guardarEnCSV($correo, $clave, $rol);

                // Mostrar alerta y redirigir
                echo "<script>
                        alert('Usuario registrado correctamente.');
                        window.location.href = '/grupo15e3/views/login.php';
                      </script>";
            } else {
                // Mostrar alerta de error
                echo "<script>
                        alert('El correo no está registrado en la base de datos.');
                        window.location.href = '/grupo15e3/index.php?controller=MenuController&action=specialMenu';
                      </script>";
            }

            $db->close();
        }
    }

    // Función para guardar en el CSV
    private function guardarEnCSV($correo, $clave, $rol)
    {
        $file = fopen('data/users.csv', 'a'); // Abre el archivo en modo de añadir
        if ($file !== false) {
            fputcsv($file, [$correo, $clave, $rol]);
            fclose($file);
        } else {
            echo "Error al abrir el archivo CSV.";
        }
    }
}
