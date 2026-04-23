#!/bin/bash
set -e

echo "==> Atualizando código..."
git config --global --add safe.directory "$(pwd)"
git pull origin main

echo "==> Subindo containers de produção..."
docker compose -f docker-compose.prod.yml up -d --build

echo "==> Instalando dependências PHP..."
docker compose -f docker-compose.prod.yml exec app bash -c "git config --global --add safe.directory /var/www/html && composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader"

echo "==> Otimizando configurações..."
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache

echo "==> Ajustando permissões..."
docker compose -f docker-compose.prod.yml exec app chown -R www-data:www-data storage bootstrap/cache
docker compose -f docker-compose.prod.yml exec app chmod -R 775 storage bootstrap/cache

echo ""
echo "Deploy concluído! API disponível em http://$(curl -s ifconfig.me)"
