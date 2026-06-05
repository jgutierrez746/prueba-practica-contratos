# Sistema de Gestión de Contratos con Inyección de Marca de Agua

Este proyecto consiste en una solución modular para la gestión de documentos en formato PDF. Integra un backend desarrollado en Laravel 11 utilizando una base de datos PostgreSQL, junto con un microservicio especializado en Python con FastAPI. La arquitectura está completamente contenedorizada mediante Docker, permitiendo registrar documentos, estampar una marca de agua de forma dinámica directamente en el almacenamiento físico, descargarlos de manera segura y eliminarlos del sistema de forma definitiva.

## Arquitectura del Sistema

El ecosistema se compone de tres contenedores principales administrados por Docker Compose:

* laravel-api: Servidor backend en Laravel 11 operando en el puerto 8080. Gestiona la autenticación, las reglas de negocio, la persistencia de datos y actúa como cliente multipart para transferir los archivos hacia el microservicio de Python.
* python-service: Microservicio en FastAPI operando internamente en el puerto 8000. Se encarga exclusivamente del procesamiento digital de imágenes sobre los archivos PDF utilizando la librería PyMuPDF (fitz).
* postgres-db: Motor de base de datos PostgreSQL dedicado a la persistencia de la información del sistema.

---

## Requisitos Previos

Para desplegar el entorno es necesario contar con las siguientes herramientas instaladas:
* Docker versión 20.10 o superior
* Docker Compose versión 2.0 o superior

---

## Instalación y Despliegue

Siga estos pasos para clonar, configurar y levantar todo el entorno de desarrollo:

### 1. Clonar el repositorio
git clone https://github.com/jgutierrez746/prueba-practica-contratos.git
cd prueba-practica-contratos

### 2. Configurar variables de entorno
Cree el archivo .env dentro de la carpeta del backend (laravel-api/.env) utilizando como plantilla el archivo .env.example. Configure la conexión a la base de datos apuntando al contenedor correspondiente:

DB_CONNECTION=pgsql
DB_HOST=postgres-db
DB_PORT=5432
DB_DATABASE=nombre_de_tu_bd
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password

### 3. Construir y levantar los contenedores
Ejecute el siguiente comando en la raíz del proyecto para compilar las imágenes y levantar los servicios en segundo plano:

sudo docker compose up -d --build

### 4. Ejecutar las migraciones
Una vez que los servicios estén activos, levante la estructura de la base de datos ejecutando las migraciones dentro del contenedor de Laravel:

sudo docker compose exec laravel-api php artisan migrate

---

## Endpoints de la API

### 1. Registro e Inyección de Marca de Agua
* URL: POST http://localhost:8080/api/documents
* Tipo de contenido: multipart/form-data
* Parámetros requeridos:
  * pdf_file (Archivo PDF)
  * watermark_image (Archivo de imagen)
* Parámetros opcionales:
  * title (Cadena de texto)

### 2. Descarga Segura de Documentos
* URL: GET http://localhost:8080/api/documents/{id}/download
* Descripción: Transfiere el archivo binario almacenado en el directorio protegido del framework, inyectando cabeceras nativas de Symfony para forzar la descarga en el cliente.

### 3. Eliminación de Registros y Archivos
* URL: DELETE http://localhost:8080/api/documents/{id}
* Descripción: Remueve el registro de la base de datos PostgreSQL y destruye físicamente el archivo del almacenamiento del servidor mediante comandos de bajo nivel (unlink), evitando la acumulación de archivos huérfanos.

---

## Detalles Técnicos de Implementación

* Estructura de Almacenamiento en Laravel 11: El sistema se adaptó a la arquitectura del framework, la cual aísla de forma automática los archivos del disco local dentro del directorio seguro storage/app/private/ para garantizar la privacidad de los documentos.
* Compatibilidad en el Procesamiento de PDFs: El microservicio de Python procesa los archivos escribiendo temporales en el disco del contenedor e interactúa con PyMuPDF mediante el método tradicional de paso de rutas físicas (filename=temp_img_path). Esto asegura un funcionamiento correcto e independiente de la versión exacta de la librería instalada.
