# Funcion que lee un CSV llamado Planeacion.csv con pandas
# y obtiene los cursos vigentes ( es decir todos los cursos en ID_asignatura y Asignatura (codigo y nombre))
# unicos, es decir que no se repitan.
# las columnas de pleanacion son: Periodo,Sede,Facultad,Código Depto,Departamento,Id Asignatura,Asignatura,Sección,Duración,Jornada,Cupo,Inscritos,Día,Hora Inicio,Hora Fin,Fecha Inicio,Fecha Fin,Lugar,Edificio,Profesor Principal,RUN,Nombre Docente,1er Apellido Docente,2so Apellido Docente,Jerarquización
# Se crea un datafram que se pasa a un archivo csv llamado cursos_vigentes.csv que se guarda en data/
import pandas as pd
from os import path

# Se lee el archivo Planeacion.csv
ruta = path.join('data', 'Planeacion.csv')
df = pd.read_csv(ruta, sep=',')
# Se obtienen los cursos vigentes
cursos_vigentes = df[['Id Asignatura', 'Asignatura']].drop_duplicates()
# Se guarda en un archivo csv
cursos_vigentes.to_csv('data/cursos_vigentes.csv', index=False)
