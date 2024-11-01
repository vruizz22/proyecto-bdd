-- Inserción en la tabla Personas
INSERT INTO Personas (RUN, DV, Nombre_1, Nombre_2, Apellido_1, Apellido_2, Correo_personal, Correo_institucional, Telefonos)
SELECT DISTINCT
    COALESCE(TempEstudiantes.RUN, TempDocentesPlanificados.RUN, TempNotas.RUN) AS RUN,
    COALESCE(TempEstudiantes.DV, TempNotas.DV, 'X') AS DV,
    COALESCE(TempEstudiantes.Nombre_1, TempDocentesPlanificados.Nombre, TempNotas.Nombres) AS Nombre_1,
    TempEstudiantes.Nombre_2 AS Nombre_2,
    COALESCE(TempEstudiantes.Primer_Apellido, TempDocentesPlanificados.Apellido_P, TempNotas.Apellido_Paterno, TempPlaneacion.Apellido_Docente_1) AS Apellido_1,
    COALESCE(TempEstudiantes.Segundo_Apellido, TempNotas.Apellido_Materno, TempPlaneacion.Apellido_Docente_2) AS Apellido_2,
    TempDocentesPlanificados.Email_personal AS Correo_personal,
    TempDocentesPlanificados.Email_institucional AS Correo_institucional,
    TempDocentesPlanificados.Telefono AS Telefonos
FROM
    TempEstudiantes
FULL OUTER JOIN TempDocentesPlanificados -- Para casos donde Docente no sea estudiante
    ON TempEstudiantes.RUN = TempDocentesPlanificados.RUN
LEFT JOIN TempNotas
    ON TempEstudiantes.RUN = TempNotas.RUN
LEFT JOIN TempPlaneacion
    ON TempDocentesPlanificados.RUN = TempPlaneacion.RUN
WHERE
    COALESCE(TempEstudiantes.RUN, TempDocentesPlanificados.RUN, TempNotas.RUN) IS NOT NULL;

-- Inserción en la tabla Estudiantes
INSERT INTO Estudiantes (RUN, DV, Causal_de_bloqueo, Bloqueo, Numero_de_estudiante, Cohorte, Nombre_Carrera)
SELECT DISTINCT
    TempEstudiantes.RUN,
    TempEstudiantes.DV,
    TempEstudiantes.Causal_Bloqueo,
    TempEstudiantes.Bloqueo,
    TempEstudiantes.Numero_de_alumno,
    TempEstudiantes.Cohorte,
    TempEstudiantes.Carrera
FROM
    TempEstudiantes
UNION ALL
SELECT DISTINCT
    TempNotas.RUN,
    TempNotas.DV,
    'Sin Bloqueo' AS Causal_de_bloqueo, -- Valor por defecto
    'X' AS Bloqueo, -- Valor por defecto
    TempNotas.Numero_de_alumno,
    TempNotas.Cohorte,
    TempNotas.Plan AS Nombre_Carrera
FROM
    TempNotas
ON CONFLICT (RUN, DV, Numero_de_estudiante) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Academicos
INSERT INTO Academicos (RUN, DV, Estamento, Grado_academico, Contrato, Jerarquia, Jornada)
SELECT DISTINCT
    COALESCE(TempDocentesPlanificados.RUN, TempNotas.RUN, TempEstudiantes.RUN) AS RUN,
    COALESCE(TempNotas.DV, TempEstudiantes.DV, 'X') AS DV,
    TempDocentesPlanificados.Estamento AS Estamento,
    TempDocentesPlanificados.Grado_academico AS Grado_academico,
    TempDocentesPlanificados.Contrato AS Contrato,
    TempDocentesPlanificados.Jerarquia AS Jerarquia,
    COALESCE(TempDocentesPlanificados.Diurno, TempDocentesPlanificados.Vespertino) AS Jornada
FROM
    TempDocentesPlanificados
LEFT JOIN TempNotas
    ON TempDocentesPlanificados.RUN = TempNotas.RUN
LEFT JOIN TempEstudiantes
    ON TempDocentesPlanificados.RUN = TempEstudiantes.RUN
WHERE TempDocentesPlanificados.Estamento = 'Académico'
AND COALESCE(TempDocentesPlanificados.RUN, TempNotas.RUN, TempEstudiantes.RUN) IS NOT NULL;

-- Inserción en la tabla Administrativos
INSERT INTO Administrativos (RUN, DV, Estamento, Grado_academico, Contrato, Cargo)
SELECT DISTINCT
    COALESCE(TempDocentesPlanificados.RUN, TempNotas.RUN, TempEstudiantes.RUN) AS RUN,
    COALESCE(TempNotas.DV, TempEstudiantes.DV, 'X') AS DV,
    TempDocentesPlanificados.Estamento AS Estamento,
    TempDocentesPlanificados.Grado_academico AS Grado_academico,
    TempDocentesPlanificados.Contrato AS Contrato,
    TempDocentesPlanificados.Cargo AS Cargo
FROM
    TempDocentesPlanificados
LEFT JOIN TempNotas
    ON TempDocentesPlanificados.RUN = TempNotas.RUN
LEFT JOIN TempEstudiantes
    ON TempDocentesPlanificados.RUN = TempEstudiantes.RUN
WHERE TempDocentesPlanificados.Estamento = 'Administrativo'
AND COALESCE(TempDocentesPlanificados.RUN, TempNotas.RUN, TempEstudiantes.RUN) IS NOT NULL
ON CONFLICT (RUN, DV) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Departamento
INSERT INTO Departamento (Nombre, Codigo, Nombre_Facultad)
SELECT DISTINCT
    TempPlaneacion.Departamento AS Nombre,
    TempPlaneacion.Codigo_Depto AS Codigo,
    TempPlaneacion.Facultad AS Nombre_Facultad
FROM TempPlaneacion
ON CONFLICT (Nombre, Codigo) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Planes_Estudio
INSERT INTO Planes_Estudio (Codigo_Plan, Inicio_Vigencia, Jornada, Modalidad, Sede, Plan, Nombre_Facultad, Grado, Nombre_Carrera)
SELECT DISTINCT
    TempPlanes.Codigo_Plan AS Codigo_Plan,
    TempPlanes.Inicio_Vigencia AS Inicio_Vigencia,
    TempPlanes.Jornada AS Jornada,
    TempPlanes.Modalidad AS Modalidad,
    TempPlanes.Sede AS Sede,
    TempPlanes.Plan AS Plan,
    TempPlanes.Facultad AS Nombre_Facultad,
    TempPlanes.Grado AS Grado,
    TempPlanes.Carrera AS Nombre_Carrera
FROM TempPlanes
ON CONFLICT (Codigo_Plan) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Cursos
INSERT INTO Cursos (Sigla_curso, Seccion_curso, Periodo_curso, Nombre, Nivel, Ciclo, Tipo, Oportunidad, Duracion, Nombre_Departamento, Codigo_Departamento, RUN_Academico, DV_Academico, Nombre_Academico, Apellido1_Academico, Apellido2_Academico, Principal, Plan_curso)
SELECT DISTINCT
    TempAsignaturas.Asignatura_id AS Sigla_curso,
    TempPlaneacion.Seccion AS Seccion_curso,
    TempPlaneacion.Periodo AS Periodo_curso,
    TempAsignaturas.Asignatura AS Nombre,
    TempAsignaturas.Nivel AS Nivel,
    TempAsignaturas.Ciclo AS Ciclo,
    'X' AS Tipo, -- Valor desconocido.
    TempNotas.Convocatoria AS Oportunidad,
    TempPlaneacion.Duracion AS Duracion,
    TempPlaneacion.Departamento AS Nombre_Departamento,
    TempPlaneacion.Codigo_Depto AS Codigo_Departamento,
    TempDocentesPlanificados.RUN AS RUN_Academico,
    COALESCE(TempEstudiantes.DV, TempNotas.DV, 'X') AS DV,
    TempDocentesPlanificados.Nombre AS Nombre_Academico,
    COALESCE(TempDocentesPlanificados.Apellido_P, TempPlaneacion.Apellido_Docente_1) AS Apellido1_Academico,
    TempPlaneacion.Apellido_Docente_2 AS Apellido2_Academico,
    TempPlaneacion.Profesor_Principal AS Principal,
    TempPlanes.Codigo_Plan AS Plan_curso
FROM
    TempAsignaturas
JOIN TempPlaneacion ON TempAsignaturas.Asignatura_id = TempPlaneacion.Id_Asignatura
JOIN TempPlanes ON TempAsignaturas.Plan = TempPlanes.Codigo_Plan
LEFT JOIN TempNotas ON TempAsignaturas.Asignatura_id = TempNotas.Codigo_Asignatura
LEFT JOIN TempDocentesPlanificados ON TempPlaneacion.RUN = TempDocentesPlanificados.RUN
LEFT JOIN TempEstudiantes ON TempDocentesPlanificados.RUN = TempEstudiantes.RUN       
ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Cursos_Equivalencias
INSERT INTO Cursos_Equivalencias (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_equivalente, Ciclo)
SELECT DISTINCT
    -- Vamos a llamar 2 veces a la tabla TempAsignaturas, una para el curso y otra para el equivalente
    A1.Asignatura_id AS Sigla_curso,
    TempPlaneacion.Seccion AS Seccion_curso,
    TempPlaneacion.Periodo AS Periodo_curso,
    A2.Asignatura_id AS Sigla_equivalente,
    A1.Ciclo AS Ciclo
FROM
    TempAsignaturas A1
JOIN TempAsignaturas A2 
    -- Extraemos el codigo de asignatura eliminando el plan
    ON SUBSTRING(A1.Asignatura_id, LENGTH(A1.Plan) + 1) = SUBSTRING(A2.Asignatura_id, LENGTH(A2.Plan) + 1)
    -- Nos aseguramos que los planes sean distintos
    AND A1.Plan != A2.Plan
JOIN TempPlaneacion ON A1.Asignatura_id = TempPlaneacion.Id_Asignatura
ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_equivalente) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Cursos_Prerequisitos
INSERT INTO Cursos_Prerequisitos (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_prerequisito, Ciclo)
SELECT DISTINCT
    TempAsignaturas.Asignatura_id AS Sigla_curso,
    TempPlaneacion.Seccion AS Seccion_curso,
    TempPlaneacion.Periodo AS Periodo_curso,
    TempPrerequisitos.Prerequisitos AS Sigla_prerequisito,
    TempAsignaturas.Ciclo AS Ciclo
FROM
    TempAsignaturas
JOIN TempPrerequisitos ON TempAsignaturas.Asignatura_id = TempPrerequisitos.Asignatura_id
JOIN TempPlaneacion ON TempAsignaturas.Asignatura_id = TempPlaneacion.Id_Asignatura
ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_prerequisito) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Cursos_Minimos
INSERT INTO Cursos_Minimos (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_minimo, Ciclo, Nombre, Tipo, Nivel)
SELECT DISTINCT
-- Todos los cursos que son Nivel = '1.0' son cursos mínimos
    TempAsignaturas.Asignatura_id AS Sigla_curso,
    TempPlaneacion.Seccion AS Seccion_curso,
    TempPlaneacion.Periodo AS Periodo_curso,
    TempAsignaturas.Asignatura_id AS Sigla_minimo,
    TempAsignaturas.Ciclo AS Ciclo,
    TempAsignaturas.Asignatura AS Nombre,
    'Mínimo' AS Tipo,
    TempAsignaturas.Nivel AS Nivel
FROM
    TempAsignaturas
JOIN TempPlaneacion ON TempAsignaturas.Asignatura_id = TempPlaneacion.Id_Asignatura
WHERE TempAsignaturas.Nivel = '1.0'
ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso, Sigla_minimo) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Programacion_Academica
INSERT INTO Programacion_Academica (Sigla_curso, Seccion_curso, Periodo_Oferta, Cupos, Sala, Hora_Inicio, Hora_Fin, Fecha_Inicio, Fecha_Fin, Inscritos)
SELECT DISTINCT
    TempAsignaturas.Asignatura_id AS Sigla_curso,
    TempPlaneacion.Seccion AS Seccion_curso,
    TempPlaneacion.Periodo AS Periodo_Oferta,
    TempPlaneacion.Cupo AS Cupos,
    TempPlaneacion.Lugar AS Sala,
    TempPlaneacion.Hora_Inicio AS Hora_Inicio,
    TempPlaneacion.Hora_Fin AS Hora_Fin,
    TempPlaneacion.Fecha_Inicio AS Fecha_Inicio,
    TempPlaneacion.Fecha_Fin AS Fecha_Fin,
    TempPlaneacion.Inscritos AS Inscritos
FROM
    TempPlaneacion
JOIN TempAsignaturas ON TempPlaneacion.Id_Asignatura = TempAsignaturas.Asignatura_id
ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_Oferta) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Nota
INSERT INTO Nota (Sigla_curso, Seccion_curso, Periodo_curso, Numero_de_estudiante, RUN, DV, Nota, Descripcion, Resultado, Calificacion)
SELECT DISTINCT
    TempAsignaturas.Asignatura_id AS Sigla_curso,
    TempPlaneacion.Seccion AS Seccion_curso,
    TempNotas.Periodo_Asignatura AS Periodo_curso,
    TempNotas.Numero_de_alumno AS Numero_de_estudiante,
    TempNotas.RUN AS RUN,
    TempNotas.DV AS DV,
    TempNotas.Nota AS Nota,
    '' AS Descripcion, -- Valor a agregar con la función actualizar_nota
    '' AS Resultado, -- Valor a agregar con la función actualizar_nota
    TempNotas.Calificacion AS Calificacion
FROM
    TempAsignaturas
JOIN TempPlaneacion ON TempAsignaturas.Asignatura_id = TempPlaneacion.Id_Asignatura 
JOIN TempNotas ON TempAsignaturas.Asignatura_id = TempNotas.Codigo_Asignatura
AND TempPlaneacion.Periodo = TempNotas.Periodo_Asignatura
ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante) DO NOTHING; -- Evitar duplicados

-- Inserción en la tabla Avance_Academico
INSERT INTO Avance_Academico (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante, Periodo_Oferta, Nota, Descripcion, Resultado, Calificacion, Ultima_Carga, Ultimo_Logro, Fecha_logro)
SELECT DISTINCT
    TempAsignaturas.Asignatura_id AS Sigla_curso,
    TempPlaneacion.Seccion AS Seccion_curso,
    TempNotas.Periodo_Asignatura AS Periodo_curso,
    TempNotas.RUN AS RUN,
    TempNotas.DV AS DV,
    TempNotas.Numero_de_alumno AS Numero_de_estudiante,
    TempNotas.Periodo_Asignatura AS Periodo_Oferta,
    TempNotas.Nota AS Nota,
    '' AS Descripcion, -- Valor a agregar con la función actualizar_nota
    '' AS Resultado, -- Valor a agregar con la función actualizar_nota
    TempNotas.Calificacion AS Calificacion,
    TempEstudiantes.Ultima_Carga AS Ultima_Carga,
    TempEstudiantes.Logro AS Ultimo_Logro,
    TempEstudiantes.Fecha_Logro AS Fecha_logro
FROM
    TempAsignaturas
JOIN TempPlaneacion ON TempAsignaturas.Asignatura_id = TempPlaneacion.Id_Asignatura
JOIN TempNotas ON TempAsignaturas.Asignatura_id = TempNotas.Codigo_Asignatura
AND TempPlaneacion.Periodo = TempNotas.Periodo_Asignatura
LEFT JOIN TempEstudiantes ON TempNotas.RUN = TempEstudiantes.RUN
ON CONFLICT (Sigla_curso, Seccion_curso, Periodo_curso, RUN, DV, Numero_de_estudiante) DO NOTHING; -- Evitar duplicados