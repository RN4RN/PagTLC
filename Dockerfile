FROM php:8.2-apache

# Copia todos los archivos al contenedor
COPY ./indexl

# Habilita extensiones necesarias (como mysqli)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

EXPOSE 80
