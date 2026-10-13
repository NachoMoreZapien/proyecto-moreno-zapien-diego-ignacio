FROM nginx:alpine

# Copiar el archivo de configuración del proxy reverso
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Copiar los archivos estáticos del frontend
COPY src/ /usr/share/nginx/html/