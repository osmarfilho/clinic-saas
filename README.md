# Clinic SaaS

Aplicacao SaaS para clinicas com backend Laravel, frontend Vue, PostgreSQL, Redis e Docker Compose.

## Requisitos

- Docker e Docker Compose
- PHP 8.3 com Composer, para rodar backend fora do Docker
- Node.js 20.19+ ou 22.12+ e npm, para rodar frontend fora do Docker
- PostgreSQL 16 e Redis, se optar por rodar sem Docker

## Estrutura

- `backend/`: API Laravel, migrations, seeders, controllers, tests e Dockerfiles.
- `frontend/`: SPA Vue/Vite, rotas, stores, services e Dockerfiles.
- `docker-compose.yml`: ambiente local com backend, frontend, PostgreSQL e Redis.

## Modulos Entregues

- Autenticacao com Laravel Sanctum.
- Dashboard com dados reais calculados no banco.
- Pacientes com CRUD completo.
- Agenda com CRUD de consultas, retornos, exames e teleconsultas.
- Financeiro com receitas, despesas, pendencias e pagamentos.
- Configuracoes da clinica com dados institucionais, horarios e indicadores.
- Notificacoes com leitura individual e leitura em lote.
- Seeders com dados demonstrativos reais para usar no primeiro acesso.

## Ambiente Local com Docker

```bash
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
docker compose up -d --build
docker compose exec -T backend php artisan db:seed --force
```

Acesse:

- Frontend: `http://localhost:5173`
- Backend/API: `http://localhost:8000/api`
- PostgreSQL local: `localhost:5433`

O container backend executa `composer install`, gera `APP_KEY` quando necessario, roda migrations e cria o `storage:link`.

Acesso inicial criado pelo seeder:

```text
E-mail: admin@clinic.test
Senha: 123456
```

## Configuracao do .env

Backend: use `backend/.env.example` como base.

Variaveis importantes:

- `APP_ENV=local` em desenvolvimento e `APP_ENV=production` em producao.
- `APP_DEBUG=true` em desenvolvimento e `APP_DEBUG=false` em producao.
- `APP_URL=https://api.seu-dominio.com` em producao.
- `FRONTEND_URL=https://seu-dominio.com`.
- `CORS_ALLOWED_ORIGINS=https://seu-dominio.com`.
- `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD`.

Frontend: use `frontend/.env.example` ou `frontend/.env.production.example` como base.

```bash
VITE_API_URL=http://localhost:8000/api
```

Em producao, ajuste para a URL publica da API.

## Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan test
```

Se rodar fora do Docker, ajuste `DB_HOST` para o host correto. Com o Compose, o host interno e `postgres`; pelo host da maquina, a porta publicada e `5433`.

Comandos de producao:

```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Frontend

```bash
cd frontend
npm install
cp .env.example .env
npm run build
```

O frontend usa Vue Router; nao use redirecionamentos com `window.location.href` para navegacao interna da SPA.

## Testes

Com Docker:

```bash
docker compose exec -T backend php artisan test
```

Sem Docker:

```bash
cd backend
php artisan test
```

O PHP local precisa ter `pdo_sqlite`, porque a suite usa SQLite em memoria.

## Deploy em VPS

1. Instale Docker e Docker Compose na VPS.
2. Clone o repositorio e copie os arquivos de ambiente.
3. Configure `backend/.env` com `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, banco, Redis, `FRONTEND_URL` e `CORS_ALLOWED_ORIGINS`.
4. Configure `frontend/.env.production` com `VITE_API_URL`.
5. Gere as imagens de producao:

```bash
docker build -f backend/Dockerfile.production -t clinic-backend:prod backend
docker build -f frontend/Dockerfile.production -t clinic-frontend:prod frontend
```

6. Suba os containers com Nginx/Proxy reverso na frente do frontend e do PHP-FPM/backend.
7. Rode no backend:

```bash
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

8. Garanta permissao de escrita em `backend/storage` e `backend/bootstrap/cache` para o usuario do PHP.

## Deploy em cPanel

O caminho mais simples e publicar backend e frontend separadamente:

- Backend: usar hospedagem com PHP 8.3+, Composer, extensoes `pdo_pgsql` ou `pdo_mysql`, `openssl`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `fileinfo` e `redis` se usar Redis.
- Configure o document root para `backend/public`.
- Rode `composer install --no-dev --optimize-autoloader`.
- Configure `.env` com `APP_ENV=production`, `APP_DEBUG=false`, banco e URL publica.
- Rode migrations pelo terminal/SSH do cPanel, quando disponivel.
- Frontend: rode `npm run build` localmente ou em CI e publique o conteudo de `frontend/dist` no dominio/subdominio da SPA.
- Configure rewrite/fallback para `index.html` no frontend.

Se o cPanel nao permitir SSH, Composer ou migrations, prefira VPS ou um provedor com suporte nativo a Laravel.

## Seguranca

- `.env` nao deve ser commitado. Use apenas `.env.example`.
- Em producao: `APP_ENV=production` e `APP_DEBUG=false`.
- Nao deixe senhas reais em arquivos versionados.
- Configure `CORS_ALLOWED_ORIGINS` apenas com dominios confiaveis.
- Garanta escrita em `storage` e `bootstrap/cache`.
- Gere uma `APP_KEY` unica por ambiente.

## Comandos Rapidos

```bash
docker compose up -d --build
docker compose exec -T backend php artisan migrate --force
docker compose exec -T backend php artisan db:seed --force
docker compose exec -T backend php artisan test
docker compose exec -T backend php artisan config:cache
docker compose exec -T backend php artisan route:cache
docker compose exec -T backend php artisan view:cache
cd frontend && npm run build
```
