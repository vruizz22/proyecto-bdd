<?php
ini_set('memory_limit', '10G'); // Aumentar el límite de memoria a 10GB
require_once 'models/Cargador.php';

// Llamada al módulo Cargador
$cargador = new Cargador();

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
}

echo "Las tablas de Profesores y Jerarquias ya existen los datos ya fueron cargados\n";

echo "Creando tablas temporales...\n";
$cargador->CrearTablasTemporales();

echo "Insertando valores de Acta en Notas...\n";
$cargador->InsertarNota();
echo "Valores insertados\n";

echo "Proceso finalizado\n";
$cargador->CerrarConexiones();
