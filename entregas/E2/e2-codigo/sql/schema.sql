CREATE TABLE Personas (
    RUN varchar(100) NOT NULL,
    DV varchar(2) NOT NULL,
    Nombre_1 varchar(100) NOT NULL,
    Nombre_2 varchar(100),
    Apellido_1 varchar(100) NOT NULL,
    Apellido_2 varchar(100),
    Correo_personal varchar(100),
    Correo_institucional varchar(100),
    Telefonos varchar(100),
    PRIMARY KEY (RUN, DV)
);

CREATE TABLE Estudiantes (
    RUN varchar(100) NOT NULL,
    DV varchar(2) NOT NULL,
    Causal_de_bloqueo TEXT,
    Bloqueo varchar(1) NOT NULL,
    Numero_de_estudiante int NOT NULL,
    Cohorte varchar(100) NOT NULL,
    Nombre_Carrera varchar(100) NOT NULL,
    FOREIGN KEY (RUN, DV) REFERENCES Personas(RUN, DV),
    PRIMARY KEY (RUN, DV, Numero_de_estudiante)
);

CREATE TABLE Academicos (
    RUN varchar(100) NOT NULL,
    DV varchar(2) NOT NULL,
    Estamento varchar(100),
    Grado_academico varchar(100),
    Contrato varchar(100),
    Jerarquia varchar(100),
    Jornada varchar(100),
    FOREIGN KEY (RUN, DV) REFERENCES Personas(RUN, DV),
    PRIMARY KEY (RUN, DV)
);

CREATE TABLE Administrativos (
    RUN varchar(100) NOT NULL,
    DV varchar(2) NOT NULL,
    Estamento varchar(100),
    Grado_academico varchar(100),
    Contrato varchar(100),
    Cargo varchar(100),
    FOREIGN KEY (RUN, DV) REFERENCES Personas(RUN, DV),
    PRIMARY KEY (RUN, DV)
);

CREATE TABLE Departamento (
    Nombre varchar(100) NOT NULL,
    Codigo varchar(100) NOT NULL,
    Nombre_Facultad varchar(100) NOT NULL,
    PRIMARY KEY (Nombre, Codigo)
);

CREATE TABLE Planes_Estudio (
    Codigo_Plan varchar(100) PRIMARY KEY UNIQUE NOT NULL,
    Inicio_Vigencia DATE NOT NULL,
    Jornada varchar(100) NOT NULL,
    Modalidad varchar(100) NOT NULL,
    Sede varchar(100) NOT NULL,
    Plan varchar(100) NOT NULL,
    Nombre_Facultad varchar(100) NOT NULL,
    Grado varchar(100) NOT NULL,
    Nombre_Carrera varchar(100) NOT NULL
);

CREATE TABLE Cursos (
    Sigla_curso varchar(100) NOT NULL,
    Seccion_curso int NOT NULL,
    Periodo_curso varchar(100) NOT NULL,
    Nombre varchar(100) NOT NULL,
    Nivel varchar(100),
    Ciclo varchar(100),
    Tipo varchar(100),
    Oportunidad varchar(3),
    Duracion varchar(1) NOT NULL,
    Nombre_Departamento varchar(100) NOT NULL,
    Codigo_Departamento varchar(100) NOT NULL,
    RUN_Academico varchar(100),
    DV_Academico varchar(2),
    Nombre_Academico varchar(100) DEFAULT 'POR DESIGNAR',
    Apellido1_Academico varchar(100),
    Apellido2_Academico varchar(100),
    Principal varchar(1),
    Plan_curso varchar(100), -- Plan de estudio al que pertenece el curso
    FOREIGN KEY (Nombre_Departamento, Codigo_Departamento) REFERENCES Departamento(Nombre, Codigo),
    FOREIGN KEY (Plan_curso) REFERENCES Planes_Estudio(Codigo_Plan),
    PRIMARY KEY (Sigla_curso, Seccion_curso, Periodo_curso)
);

CREATE TABLE Cursos_Equivalencias (
    Sigla_curso varchar(100) NOT NULL,
    Seccion_curso int NOT NULL,
    Periodo_curso varchar(100) NOT NULL,
    Sigla_equivalente varchar(100),
    Ciclo varchar(100),
    PRIMARY KEY (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_equivalente),
    FOREIGN KEY (Sigla_curso, Seccion_curso, Periodo_curso) REFERENCES Cursos(Sigla_curso, Seccion_curso, Periodo_curso)
);

CREATE TABLE Cursos_Prerequisitos (
    Sigla_curso varchar(100) NOT NULL,
    Seccion_curso int NOT NULL,
    Periodo_curso varchar(100) NOT NULL,
    Sigla_prerequisito varchar(100),
    Ciclo varchar(100),
    PRIMARY KEY (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_prerequisito),
    FOREIGN KEY (Sigla_curso, Seccion_curso, Periodo_curso) REFERENCES Cursos(Sigla_curso, Seccion_curso, Periodo_curso)
);

CREATE TABLE Cursos_Minimos (
    Sigla_curso varchar(100) NOT NULL,
    Seccion_curso int NOT NULL,
    Periodo_curso varchar(100) NOT NULL,
    Sigla_minimo varchar(100),
    Ciclo varchar(100),
    Nombre varchar(100),
    Tipo varchar(100),
    Nivel varchar(100),
    PRIMARY KEY (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_minimo),
    FOREIGN KEY (Sigla_curso, Seccion_curso, Periodo_curso) REFERENCES Cursos(Sigla_curso, Seccion_curso, Periodo_curso)
);

CREATE TABLE Programacion_Academica (
    Sigla_curso varchar(100) NOT NULL,
    Seccion_curso int NOT NULL,
    Periodo_Oferta varchar(100) NOT NULL,
    Cupos int NOT NULL,
    Sala varchar(100) NOT NULL,
    Hora_Inicio TIME NOT NULL,
    Hora_Fin TIME NOT NULL,
    Fecha_Inicio DATE NOT NULL,
    Fecha_Fin DATE NOT NULL,
    Inscritos int NOT NULL,
    PRIMARY KEY (Sigla_curso, Seccion_curso, Periodo_Oferta),
    FOREIGN KEY (Sigla_curso, Seccion_curso, Periodo_Oferta) REFERENCES Cursos(Sigla_curso, Seccion_curso, Periodo_curso)
);

CREATE TABLE Nota (
    Sigla_curso varchar(100) NOT NULL,
    Seccion_curso int NOT NULL,
    Periodo_curso varchar(100) NOT NULL,
    Numero_de_estudiante int NOT NULL,
    RUN varchar(100) NOT NULL,
    DV varchar(2) NOT NULL,
    Nota float,
    Descripcion varchar(100),
    Resultado varchar(100),
    Calificacion varchar(100),
    PRIMARY KEY (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante),
    FOREIGN KEY (Sigla_curso, Seccion_curso, Periodo_curso) REFERENCES Cursos(Sigla_curso, Seccion_curso, Periodo_curso),
    FOREIGN KEY (RUN, DV, Numero_de_estudiante) REFERENCES Estudiantes(RUN, DV, Numero_de_estudiante)
);

CREATE TABLE Avance_Academico (
    Sigla_curso varchar(100) NOT NULL,
    Seccion_curso int NOT NULL,
    Periodo_curso varchar(100) NOT NULL,
    RUN varchar(100) NOT NULL,
    DV varchar(2) NOT NULL,
    Numero_de_estudiante int NOT NULL,
    Periodo_Oferta varchar(100) NOT NULL,
    Nota float,
    Descripcion varchar(100),
    Resultado varchar(100),
    Calificacion varchar(100),
    Ultima_Carga varchar(100),
    Ultimo_Logro varchar(100),
    Fecha_logro varchar(100),
    PRIMARY KEY (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante),
    FOREIGN KEY (Sigla_curso, Seccion_curso, Periodo_curso) REFERENCES Cursos(Sigla_curso, Seccion_curso, Periodo_curso),
    FOREIGN KEY (RUN, DV, Numero_de_estudiante) REFERENCES Estudiantes(RUN, DV, Numero_de_estudiante)
);