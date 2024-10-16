<?php
session_start();
require_once 'models/User.php';
require_once 'models/Database.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/MenuController.php';

// Manejar la lógica de la redirección
if (isset($_GET['controller']) && isset($_GET['action'])) {
    $controllerName = $_GET['controller'];
    $actionName = $_GET['action'];

    if (class_exists($controllerName)) {
        $controller = new $controllerName();
        if (method_exists($controller, $actionName)) {
            $controller->$actionName();
        } else {
            // Acción no encontrada
            echo "Acción no encontrada.";
        }
    } else {
        // Controlador no encontrado
        echo "Controlador no encontrado.";
    }
} else {
    // Verificar si el usuario ya ha iniciado sesión
    if (isset($_SESSION['user_email'])) {
        // Si ya ha iniciado sesión, redirigir al menú
        header("Location: /grupo15/index.php?controller=MenuController&action=menu");
        exit();
    } else {
        // Si no ha iniciado sesión, mostrar el formulario de login
        header("Location: /grupo15/views/login.php");
        exit();
    }
}
?>
