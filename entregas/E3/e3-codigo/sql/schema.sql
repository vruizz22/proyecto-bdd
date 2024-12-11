-- run    |    nombre    |    apellido1    |   apellido2    | sexo | jerarquizacion | telefono  |                 email_personal                 |               email_institucional                | dedicacion | contrato  |  jornada   |    sede    |  carrera   | grado_academico |                detalle
CREATE TABLE Profesores (
    RUN VARCHAR(9) NOT NULL,
    DV CHAR(1) NOT NULL,
    SEXO CHAR(1),
    JERARQUIZACION VARCHAR(50),
    DEDICACION VARCHAR(50),
    CONTRATO VARCHAR(50),
    JORNADA VARCHAR(50),
    SEDE VARCHAR(50),
    CARRERA VARCHAR(50),
    GRADO_ACADEMICO VARCHAR(50),
    DETALLE VARCHAR(50),
    PRIMARY KEY (RUN, DV),
    FOREIGN KEY (RUN, DV) REFERENCES Personas(RUN, DV)
);

 -- jerarquizacion |          generico          |          femenino

CREATE TABLE Jerarquia (
    JERARQUIZACION VARCHAR(50) PRIMARY KEY NOT NULL,
    GENERICO VARCHAR(50),
    FEMENINO VARCHAR(50)
);



