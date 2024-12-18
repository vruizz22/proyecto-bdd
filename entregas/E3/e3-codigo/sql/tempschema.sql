CREATE TEMP TABLE TempNotasAdivinacion (
    numero_alumno INT,
    RUN INT,
    asignatura VARCHAR(50),
    seccion INTEGER,
    periodo VARCHAR(20),
    oportunidad_dic VARCHAR(20),
    oportunidad_mar VARCHAR(20)
);

CREATE TEMP TABLE TempPlaneacion (
    Periodo VARCHAR(100),
    Id_Asignatura VARCHAR(100),
    Seccion INT,
    RUN VARCHAR(100)
);

CREATE TEMP TABLE TempEstudiantes (
    Numero_de_alumno INT,
    Nombre_1 VARCHAR(100),
    Nombre_2 VARCHAR(100)
);

-- El acta de notas contiene el n´umero de alumno, curso, periodo, nombre del estudiante, nombre del profesor, la ´unica nota final 

CREATE TEMP TABLE Acta (
    Numero_Alumno INT PRIMARY KEY NOT NULL,
    RUN INT NOT NULL,
    Curso VARCHAR(100),
    seccion INT,
    Periodo VARCHAR(100),
    Nombre_Estudiante VARCHAR(100),
    Nombre_Profesor VARCHAR(100),
    Nota_Final FLOAT NOT NULL
);