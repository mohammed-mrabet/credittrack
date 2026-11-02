#!/bin/sh
set -e

# Migrations de la base, puis données de démonstration si la base est vide.
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
php bin/console app:seed --if-empty
php bin/console cache:warmup

# Serveur PHP intégré : suffisant pour une démo. En production réelle : Nginx + PHP-FPM.
exec php -S 0.0.0.0:8000 -t public
