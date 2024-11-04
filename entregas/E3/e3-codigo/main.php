<?php
ini_set('memory_limit', '10G'); // Aumentar el límite de memoria a 10GB
require_once 'Cargador.php';
require_once 'Corrector.php';

// Cadena de conexión
$env_string = "host=localhost port=5432 dbname=postgres user=postgres password=Elefante$15"; # Ceniza11 clave visho
# Elefante$15
// Llamada al módulo Cargador

$cargador = new Cargador($env_string);
echo "Cargando tablas...\n";
$cargador->CrearTablas();
echo "Tablas creadas\n";
echo "Cargando datos...\n";
$cargador->CargarDatos();
echo "Datos cargados\n";

// Llamada al módulo Corrector
$corrector = new Corrector($env_string);
echo "Comienza la corrección de datos\n";
echo "Iniciando tablas... Se crean las tablas temporales y se insertan los datos\n";
$corrector->InitTablas();
echo "Corrigiendo tablas... Se insertan los datos erroneos en la carga\n";
$corrector->CorregirPersonas();
$corrector->CorregirDepto();
$corrector->CorregirCursos();
$corrector->CorregirNotas();
$corrector->CorregirAcademicos();
$corrector->CorregirAvanceAcademico();
$corrector->CorregirProgramacionAcademica();
$corrector->CorregirCursosPrerrequisitos();
$corrector->closeConn();
