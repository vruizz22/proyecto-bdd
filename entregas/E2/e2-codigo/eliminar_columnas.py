# ELiminar las columnas del archvio Notas csv en Data/
# Elimnar desde la columna 8 a la 15

import pandas as pd
from os import path

# Se lee el archivo Notas.csv
df = pd.read_csv('data/Notas.csv', sep=',')
# Se eliminan las columnas del 8 al 15
df = df.drop(df.columns[8:16], axis=1)
# Se guarda en un archivo csv
df.to_csv('data/Notas.csv', index=False)
