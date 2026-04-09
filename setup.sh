#!/bin/bash

echo "🚀 Iniciando setup do ambiente Docker..."

# Sobe os containers em background
docker compose up -d --build

echo "⏳ Aguardando MySQL inicializar (15s)..."
sleep 15

echo "📦 Instalando dependências do Laravel..."
docker compose exec app composer install --no-interaction --prefer-dist

echo "🔑 Gerando APP_KEY do Laravel..."
docker compose exec app php artisan key:generate

echo "🧹 Limpando caches..."
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

echo "🔒 Ajustando permissões..."
docker compose exec app chown -R www-data:www-data storage bootstrap/cache
docker compose exec app chmod -R 775 storage bootstrap/cache

echo ""
echo "✅ Ambiente pronto!"
echo ""
echo "  🌐 API Laravel:  http://localhost:8000"
echo "  🛢️  phpMyAdmin:   http://localhost:8080"
echo ""
echo "  Credenciais phpMyAdmin:"
echo "    Servidor:  mysql"
echo "    Usuário:   root"
echo "    Senha:     root"
echo ""
echo "  Teste rápido da API:"
echo "  curl -X GET 'http://localhost:8000/api/v1/clientes/consulta?documento=12345678901' \\"
echo "    -H 'Authorization: Basic aW50ZWdyYWNhbzpzZW5oYVNlZ3VyYTEyMw=='"