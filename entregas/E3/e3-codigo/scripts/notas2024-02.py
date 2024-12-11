"""
Un script de python que transforma 
losel archivo notas.csv en uno que contiene
solo las notas de los cursos del periodo
2024-02
"""

# Importar librerias
import pandas
from os import path

# Cargar el archivo ../data/Notas.csv
archivo = path.join("..", "data", "Notas.csv")
notas = pandas.read_csv(archivo)

# Filtrar las notas del periodo 2024-02
notas_2024_02 = notas[notas["Periodo Asignatura"] == "2024-02"]

# Guardar las notas en un archivo, con la misma estructura de columnas
archivo_2024_02 = path.join("..", "data", "Notas_2024_02.csv")
notas_2024_02.to_csv(archivo_2024_02, index=False, header=True)
