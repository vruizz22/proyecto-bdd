# Iniciar un proyecto en Laravel


## Tener instalado unzip y 7z

Para esto, usamos los siguientes comandos, en terminal con permisos de administrador:

```bash
choco install unzip
choco install 7zip
```

## Instalar Laravel globalmente

Para instalar Laravel globalmente en tu sistema, necesitas tener Composer instalado. Luego, ejecuta el siguiente comando:

```bash
composer global require laravel/installer
```
Esto instalará el comando laravel en tu sistema, que facilita la creación de nuevos proyectos.

## Crear una nueva aplicación

Para crear una nueva aplicación de Laravel, ejecuta el siguiente comando:

```bash
laravel new [nombre_proyecto]
```

O, si prefieres crear el proyecto sin instalar el instalador de Laravel, puedes usar Composer directamente:

```bash
composer create-project --prefer-dist laravel/laravel [nombre_proyecto]
```

## Iniciar el servidor de desarrollo

Para iniciar el servidor de desarrollo de Laravel, ejecuta el siguiente comando:

```bash
php artisan serve
```

Esto iniciará el servidor en http://localhost:8000 por defecto.

## Solución de problemas comunes

Si experimentas algún problema con tu proyecto, es posible que necesites actualizar los paquetes de Composer:

```bash
composer update
```

Y si es necesario, puedes borrar el caché y los archivos temporales del proyecto ejecutando:

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

Estos comandos limpian los cachés de configuración, rutas, vistas y datos generales de la aplicación.

## Configurar la base de datos creada en PostgreSQL

Para configurar la base de datos creada en PostgreSQL, debes modificar el archivo .env que se encuentra en la raíz del proyecto. Debes modificar las siguientes líneas:

```bash
DB_CONNECTION=pgsql
DB_HOST=[host]
DB_PORT=[port]
DB_DATABASE=[database_name]
DB_USERNAME=[username]
DB_PASSWORD=[password]
```

