<?php
ini_set('memory_limit', '10G'); // Aumentar el límite de memoria a 10GB
require_once 'Cargador.php';

// Cadena de conexión
$env_string_grupo15e3 = "host=localhost port=5432 dbname=postgres user=postgres password=Elefante$15";
$env_string_e3profesores = "host=localhost port=5432 dbname=e3profesores user=postgres password=Elefante$15";

// Llamada al módulo Cargador
$cargador = new Cargador($env_string_grupo15e3, $env_string_e3profesores);

// Si no existen las tablas, se crean y se cargan los datos
if (!$cargador->TablasExistentes()) {
    echo "Cargando tablas...\n";
    $cargador->CrearTablas();
    echo "Tablas creadas\n";
    echo "Copiando tablas...\n";
    $cargador->CopiarTablas();
    echo "Tablas copiadas\n";
    echo "Cargando datos...\n";
    $cargador->CargarDatos();
    echo "Datos cargados\n";
    echo "Proceso finalizado\n";
    $cargador->CerrarConexiones();
}

echo "Las tablas de Profesores y Jerarquias ya existen los datos ya fueron cargados\n";

echo "Creando tablas temporales...\n";
$cargador->CrearTablasTemporales();

echo "Proceso finalizado\n";
$cargador->CerrarConexiones();

// Crear la tabla temporal llamada "acta"