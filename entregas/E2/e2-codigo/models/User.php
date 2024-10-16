<?php

class User {
    private $usersFile;

    public function __construct() {
        // Definir la ubicación del archivo de usuarios
        $this->usersFile = __DIR__ . '/../data/users.csv';
    }

    public function findUserByEmail($email) {
        // Abrir el archivo CSV
        if (($file = fopen($this->usersFile, 'r')) !== false) {
            // Leer el archivo línea por línea
            while (($data = fgetcsv($file, 1000, ',')) !== false) {
                // Extraer las columnas del CSV
                list($storedEmail, $storedPassword) = $data;
                
                // Verificar si el email coincide
                if ($storedEmail === $email) {
                    // Cerrar el archivo y devolver la información
                    fclose($file);
                    return [
                        'email' => $storedEmail,
                        'password' => $storedPassword
                    ];
                }
            }

            // Cerrar el archivo si no se encontró el usuario
            fclose($file);
        }

        return null; // Retornar null si el usuario no se encuentra
    }
}
?>