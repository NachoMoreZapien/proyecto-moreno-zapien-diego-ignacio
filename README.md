# Sistema de Control de Incidencias

Aplicación web distribuida en cinco contenedores mediante Docker Compose para el registro y gestión de incidencias.

## Arquitectura
- **Frontend**: Nginx sirviendo HTML/JS/TailwindCSS (Puerto 8081).
- **Backend**: API REST con PHP 8.3 Apache (Red interna).
- **Base de Datos**: MariaDB 16-alpine con volumen persistente (Red interna).
- **Caché**: Redis 7-alpine para contador y almacenamiento temporal (Red interna).
- **Notificaciones**: Mailpit para captura de correos de prueba (Puerto 8025).

## Requisitos previos
- Docker Desktop o Docker Engine instalado.
- Docker Compose v2.

## Instalación y ejecución
1. Clonar el repositorio.
2. Copiar `.env.example` y renombrarlo a `.env`, llenando los valores.
3. Ejecutar el comando para levantar la infraestructura:
   `docker compose up -d --build`

## Accesos
- Interfaz del sistema: [http://localhost:8081](http://localhost:8081)
- Bandeja de correos Mailpit: [http://localhost:8025](http://localhost:8025)