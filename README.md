# Plataforma Modular de RH e Gestão (SaaS multi-tenant)

Monólito modular **Laravel 13 + React (Vite) + Ant Design**, multi-tenant com **uma base de dados por empresa**
(`stancl/tenancy`). Cada empresa cliente acede pelo seu subdomínio (`empresa.nossaapp.pt`); o painel central
(só nosso) fica em `admin.nossaapp.pt` e ativa/desativa módulos por cliente.

- Especificação completa: [`docs/spec.md`](docs/spec.md)
- Decisões de arquitetura: [`docs/decisions.md`](docs/decisions.md)
- Estado por fase: [`docs/fases/`](docs/fases)

## Estrutura

```
backend/                     Laravel (API JSON)
  app/Core/                  Código partilhado: Auth, Tenancy, Licensing, Modules, Http
  app/Central/               Painel central (guard "central", BD central)
  Modules/Vacations/         Módulo Férias (module.json, provider, rotas, migrations, seeders, testes)
  database/migrations/       Migrations da BD central
  database/migrations/tenant Migrations de cada BD de tenant (core)
  routes/tenant.php          API do tenant (core)  ·  routes/central.php  API do painel
frontend/                    React + Vite (duas entradas: index.html e central.html)
  src/core/                  layout, auth, router, api gateway (fetch), store, i18n
  src/shared/                componentes reutilizáveis e estilos partilhados
  src/modules/vacations/     módulo Férias (carregado só se estiver ativo)
  src/central/               UI do painel central
docker/                      PHP-FPM 8.4, nginx, init do MariaDB
```

## Arranque com Docker (recomendado)

Requisitos: Docker + Docker Compose. Os domínios `*.localhost` resolvem para 127.0.0.1 nos browsers
modernos (Chrome, Firefox, Edge), por isso não é preciso mexer no `/etc/hosts`.

```bash
cp backend/.env.example backend/.env
make up          # docker compose up -d --build
make install     # composer install, key:generate, migrate, seed (admin central + tenant demo)
```

O tenant `demo` vem com **dados falsos** (3 departamentos, 15 colaboradores com hierarquia, feriados,
notificações) — ver contas em [`docs/fases/fase-2.md`](docs/fases/fase-2.md).

| O quê | URL | Credenciais |
|---|---|---|
| Painel central | http://admin.nossaapp.localhost:8080 | `admin@nossaapp.pt` / `password` |
| Tenant demo | http://demo.nossaapp.localhost:8080 | `admin@demo.pt`, `rh@demo.pt`, `chefe.ti@demo.pt`, `colaborador@demo.pt` / `password` |
| Horizon (filas) | http://admin.nossaapp.localhost:8080/horizon | sessão do painel central |
| Mailpit | http://localhost:8025 | — |

> Se o browser não resolver `*.localhost`, adicionar ao `/etc/hosts`:
> `127.0.0.1 admin.nossaapp.localhost demo.nossaapp.localhost` (e um por cada tenant novo).

## Arranque sem Docker (só PHP + Node, com SQLite)

Requisitos: PHP 8.3+ (extensões `pdo_sqlite`, `intl`, `mbstring`, `gd`), Composer e Node 22. Não precisa de
MariaDB nem de Redis.

```bash
# Backend
cd backend
composer install
cp .env.example .env
php artisan key:generate
# Ajustar o .env para SQLite e sem Redis:
sed -i.bak \
  -e "s|^DB_DRIVER=.*|DB_DRIVER=sqlite|" \
  -e "s|^DB_DATABASE=.*|DB_DATABASE=$PWD/database/central.sqlite|" \
  -e "s|^TENANCY_DB_SUFFIX=.*|TENANCY_DB_SUFFIX=.sqlite|" \
  -e "s|^SESSION_DRIVER=.*|SESSION_DRIVER=file|" \
  -e "s|^CACHE_STORE=.*|CACHE_STORE=array|" \
  -e "s|^QUEUE_CONNECTION=.*|QUEUE_CONNECTION=sync|" \
  -e "s|^MAIL_MAILER=.*|MAIL_MAILER=log|" \
  -e "s|^FRONTEND_PORT=.*|FRONTEND_PORT=5173|" .env
touch database/central.sqlite
php artisan migrate
php artisan db:seed          # admin central + tenant demo com dados falsos
php artisan serve            # http://127.0.0.1:8000 (deixar a correr)

# Frontend (noutro terminal)
cd frontend
npm install
VITE_BACKEND_URL=http://127.0.0.1:8000 npm run dev
```

| O quê | URL | Credenciais |
|---|---|---|
| Painel central | http://admin.nossaapp.localhost:5173 | `admin@nossaapp.pt` / `password` |
| Tenant demo | http://demo.nossaapp.localhost:5173 | `rh@demo.pt`, `chefe.ti@demo.pt`, `colaborador@demo.pt`, `admin@demo.pt` / `password` |

- Usar **Chrome, Edge ou Firefox** (resolvem `*.localhost` sozinhos). No Safari, ou se não abrir, acrescentar ao
  `/etc/hosts`: `127.0.0.1 admin.nossaapp.localhost demo.nossaapp.localhost`.
- Os emails (ex.: boas-vindas, recuperar password) ficam em `backend/storage/logs/laravel.log`.
- Recomeçar do zero: `rm database/*.sqlite && rm -rf storage/tenants && touch database/central.sqlite &&
  php artisan migrate && php artisan db:seed`.
- No Windows, editar o `.env` à mão (os mesmos valores) em vez do `sed`, e usar `set VITE_BACKEND_URL=...`
  antes de `npm run dev` (ou o WSL).

## Comandos úteis

```bash
# Criar um tenant (BD + migrations + seeds + admin + licenças)
php artisan tenants:create "Empresa X, Lda." empresax --admin-email=rh@empresax.pt --module=vacations
#   (ou: make tenant NAME="Empresa X" SUB=empresax EMAIL=rh@empresax.pt)

# Ver / ativar / desativar módulos de um tenant (aceita id, subdomínio ou domínio)
php artisan tenants:modules empresax
php artisan tenants:modules empresax --enable=vacations
php artisan tenants:modules empresax --disable=vacations

# Sincronizar o catálogo de módulos (tabela central "modules") com os module.json
php artisan modules:sync

# Migrations: central e todas as BDs de tenant
php artisan migrate && php artisan tenants:migrate
# Re-correr seeds base (roles/permissões) em todos os tenants
php artisan tenants:seed
```

Tudo o que o painel central faz também está disponível pela API `/api/central/*` (ver `routes/central.php`).

## Qualidade

```bash
cd backend && ./vendor/bin/pest          # testes (SQLite, sem serviços externos)
cd backend && ./vendor/bin/pint --test   # estilo PHP
cd frontend && npm run lint              # ESLint (+ Prettier: npm run format:check)
cd frontend && npm run build
```

## Regras de arquitetura (resumo)

- Um módulo **nunca** importa classes de outro módulo; comunica por **eventos** ou **contratos do Core**.
- O Core nunca depende de módulos. Cada módulo declara `requires` no `module.json`.
- Todas as rotas de um módulo levam `module:{key}` automaticamente (`App\Core\Modules\ModuleServiceProvider`);
  módulo inativo → `403 { code: "MODULE_NOT_LICENSED" }`. Jobs usam `RequiresModuleLicense`.
- Desativar um módulo nunca apaga dados.
- Frontend: **sem axios**; HTTP só via `core/api/gateway.js`, chamado a partir de sagas
  (action → saga → gateway → reducer). O menu/rotas vêm de `active_modules` (`GET /api/me`), mas a decisão
  de acesso é sempre do backend.
