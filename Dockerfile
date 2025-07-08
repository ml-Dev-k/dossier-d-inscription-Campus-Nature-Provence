# Utilise une image officielle PHP 8.2 avec Apache
FROM php:8.2-apache

# Active le module rewrite si tu utilises .htaccess (utile pour routing ou sécurité)
RUN a2enmod rewrite

# Copie tous les fichiers dans le dossier de déploiement Apache
COPY . /var/www/html/

# Donne les bons droits
RUN chown -R www-data:www-data /var/www/html

# Expose le port standard HTTP (facultatif sur Render)
EXPOSE 80
