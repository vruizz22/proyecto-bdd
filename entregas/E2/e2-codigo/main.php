<?php
ini_set('memory_limit', '10G'); // Aumentar el límite de memoria a 10GB
require_once 'Cargador.php';
require_once 'Corrector.php';

// Cadena de conexión
$env_string = "host=localhost port=5432 dbname=postgres user=postgres password=Elefante$15"; # Ceniza11 clave visho

// Llamada al módulo Cargador
// $cargador = new Cargador($env_string);
// $cargador->CrearTablas();
// $cargador->CargarDatos();

// Llamada al módulo Corrector
$corrector = new Corrector($env_string);
$corrector->InitTablas();
$corrector->CorregirNotas();
$corrector->closeConn();
