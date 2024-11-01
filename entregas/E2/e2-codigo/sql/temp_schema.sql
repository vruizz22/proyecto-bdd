CREATE TEMP TABLE TempAsignaturas (
    Plan VARCHAR(100),
    Asignatura_id VARCHAR(100),
    Asignatura VARCHAR(100),
    Nivel VARCHAR(100),
    Ciclo VARCHAR(100)
);

CREATE TEMP TABLE TempPlaneacion (
    Periodo VARCHAR(100),
    Sede VARCHAR(100),
    Facultad VARCHAR(100),
    Codigo_Depto VARCHAR(100),
    Departamento VARCHAR(100),
    Id_Asignatura VARCHAR(100),
    Asignatura VARCHAR(100),
    Seccion INT,
    Duracion VARCHAR(100),
    Jornada VARCHAR(100),
    Cupo INT,
    Inscritos INT,
    Dia VARCHAR(100),
    Hora_Inicio TIME,
    Hora_Fin TIME,
    Fecha_Inicio DATE,
    Fecha_Fin DATE,
    Lugar VARCHAR(100),
    Edificio VARCHAR(100),
    Profesor_Principal VARCHAR(100),
    RUN VARCHAR(100),
    Nombre_Docente VARCHAR(100),
    Apellido_Docente_1 VARCHAR(100),
    Apellido_Docente_2 VARCHAR(100),
    Jerarquizacion VARCHAR(100)
);

CREATE TEMP TABLE TempEstudiantes (
    Codigo_Plan VARCHAR(100),
    Carrera VARCHAR(100),
    Cohorte VARCHAR(100),
    Numero_de_alumno INT,
    Bloqueo varchar(1),
    Causal_Bloqueo TEXT,
    RUN VARCHAR(100),
    DV VARCHAR(2),
    Nombre_1 VARCHAR(100),
    Nombre_2 VARCHAR(100),
    Primer_Apellido VARCHAR(100),
    Segundo_Apellido VARCHAR(100),
    Logro VARCHAR(100),
    Fecha_Logro VARCHAR(100), -- Periodo (2024-2)
    Ultima_Carga VARCHAR(100) -- Periodo (2024-2)
);

CREATE TEMP TABLE TempNotas (
    Codigo_Plan VARCHAR(100),
    Plan VARCHAR(100),
    Cohorte VARCHAR(100),
    Sede VARCHAR(100),
    RUN VARCHAR(100),
    DV VARCHAR(2),
    Nombres VARCHAR(100),
    Apellido_Paterno VARCHAR(100),
    Apellido_Materno VARCHAR(100),
    Numero_de_alumno INT,
    Periodo_Asignatura VARCHAR(100),
    Codigo_Asignatura VARCHAR(100),
    Asignatura VARCHAR(100),
    Convocatoria VARCHAR(100),
    Calificacion VARCHAR(100),
    Nota FLOAT
);

CREATE TEMP TABLE TempDocentesPlanificados (
    RUN VARCHAR(100),
    Nombre VARCHAR(100),
    Apellido_P VARCHAR(100),
    Telefono INT,
    Email_personal VARCHAR(100),
    Email_institucional VARCHAR(100),
    Dedicacion VARCHAR(100),
    Contrato VARCHAR(100),
    Diurno varchar(100),
    Vespertino varchar(100),
    Sede VARCHAR(100),
    Carrera VARCHAR(100),
    Grado_academico VARCHAR(100),
    Jerarquia VARCHAR(100),
    Cargo VARCHAR(100),
    Estamento VARCHAR(100)
);

CREATE TEMP TABLE TempPlanes (
    Codigo_Plan VARCHAR(100),
    Facultad VARCHAR(100),
    Carrera VARCHAR(100),
    Plan VARCHAR(100),
    Jornada VARCHAR(100),
    Sede VARCHAR(100),
    Grado VARCHAR(100),
    Modalidad VARCHAR(100),
    Inicio_Vigencia DATE
);

CREATE TEMP TABLE TempPrerequisitos (
    Plan VARCHAR(100),
    Asignatura_id VARCHAR(100),
    Asignatura VARCHAR(100),
    Nivel VARCHAR(100),
    Prerequisitos VARCHAR(100),
    Prerequisitos_1 VARCHAR(100)
);

CREATE TEMP TABLE TempPlanesMagia (
    Planes_Vigentes VARCHAR(100)
);

CREATE TEMP TABLE TempPlanesHechiceria (
    Planes_Vigentes VARCHAR(100)
);

CREATE TEMP TABLE TempMallaMagia (
    Col1 VARCHAR(100),
    Col2 VARCHAR(100),
    Col3 VARCHAR(100),
    Col4 VARCHAR(100),
    Col5 VARCHAR(100),
    Col6 VARCHAR(100),
    Col7 VARCHAR(100),
    Col8 VARCHAR(100),
    Col9 VARCHAR(100),
    Col10 VARCHAR(100)
);

CREATE TEMP TABLE TempMallaHechiceria (
    Col1 VARCHAR(100),
    Col2 VARCHAR(100),
    Col3 VARCHAR(100),
    Col4 VARCHAR(100),
    Col5 VARCHAR(100),
    Col6 VARCHAR(100),
    Col7 VARCHAR(100),
    Col8 VARCHAR(100),
    Col9 VARCHAR(100),
    Col10 VARCHAR(100)
);