# Deploy de Producao (aaPanel + Docker + IP)

Este guia sobe o projeto Laravel em producao com:
- Nginx + PHP-FPM
- MySQL
- Redis
- Worker de fila
- Scheduler

## 1) Ajustar .env

Use estes pontos no `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://77.237.240.202
APP_PORT=8080

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=seu_banco
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

## 2) Subir stack de producao

```bash
cd /www/wwwroot/api

docker compose -f docker-compose.prod.yml down

docker compose -f docker-compose.prod.yml up -d --build
```

## 3) Preparar Laravel

```bash
docker compose -f docker-compose.prod.yml exec app composer install --no-dev --optimize-autoloader --no-interaction

docker compose -f docker-compose.prod.yml exec app php artisan key:generate --force

docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

docker compose -f docker-compose.prod.yml exec app php artisan db:seed --class=UserSeeder --force

docker compose -f docker-compose.prod.yml exec app php artisan config:cache

docker compose -f docker-compose.prod.yml exec app php artisan route:cache

docker compose -f docker-compose.prod.yml exec app php artisan view:cache
```

## 4) Build frontend (dentro do container app)

```bash
docker compose -f docker-compose.prod.yml exec app npm ci

docker compose -f docker-compose.prod.yml exec app npm run build
```

## 5) aaPanel (site com IP)

No site do aaPanel:
1. Criar site para `77.237.240.202`
2. Habilitar reverse proxy para `127.0.0.1:8080`
3. Nao forcar HTTPS enquanto estiver sem dominio

## 6) Validacao rapida

```bash
docker compose -f docker-compose.prod.yml ps

docker compose -f docker-compose.prod.yml logs --tail=80 app

docker compose -f docker-compose.prod.yml logs --tail=80 nginx

docker compose -f docker-compose.prod.yml logs --tail=80 queue
```

Abrir no navegador:
- http://77.237.240.202

## 7) Atualizacao de versao (deploy futuro)

```bash
cd /www/wwwroot/api
git pull

docker compose -f docker-compose.prod.yml up -d --build

docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

docker compose -f docker-compose.prod.yml exec app php artisan optimize
```
