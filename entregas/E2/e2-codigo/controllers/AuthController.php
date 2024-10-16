<?php

class AuthController {
    public function authenticate() {
        // Capturar los datos del formulario
        $email = $_POST['email'];
        $password = $_POST['password'];

        // Verificar si el usuario es el específico
        if ($email === 'bananer@lamejor.com' && $password === 'bananer0') {
            session_start();
            $_SESSION['user_email'] = $email;  // Iniciar sesión

            // Redirigir al menú especial
            header("Location: /grupo15/index.php?controller=MenuController&action=specialMenu");
            exit();
        }

        // Verificar si el email y password coinciden con los datos del CSV
        $userModel = new User();
        $user = $userModel->findUserByEmail($email);

        if ($user && $user['password'] === $password) {
            session_start();
            $_SESSION['user_email'] = $user['email'];  // Iniciar sesión

            // Redirigir al menú estándar
            header("Location: /grupo15/index.php?controller=MenuController&action=menu");
            exit();
        } else {
            // Si las credenciales son incorrectas, redirigir a la página de error
            header("Location: /grupo15/views/error.php");
            exit();
        }
    }
}
?>
