# Fase 1 — Fundação

**Estado:** concluída, à espera de validação.

## Plano (apresentado antes de começar)
- Laravel 13 em `backend/` com `stancl/tenancy` (BD por tenant, identificação por domínio), Sanctum (SPA por
  cookie), spatie/permission, Horizon e Pest. React 19 + Vite + AntD 6 + Redux/Redux-Saga em `frontend/`.
- BD central: `central_admins`, `tenants`, `domains`, `modules`, `tenant_modules`, `central_audit_logs`.
  BD de tenant: `users`, `password_reset_tokens`, `personal_access_tokens`, tabelas spatie.
- Estrutura `Modules/` própria (D2) com um módulo `Vacations` esqueleto para provar o licenciamento.
- Painel central mínimo (login, CRUD tenants, suspender/reativar, ativar módulos com datas e limite de utilizadores).
- Docker Compose: app, queue (Horizon), scheduler, nginx, mariadb, redis, node, mailpit.

## O que foi feito

### Backend
- **Tenancy**: `App\Core\Tenancy\Models\Tenant` (uuid, name, nif, contact_email, status). Criar um tenant cria a
  BD, corre as migrations de tenant e o `TenantDatabaseSeeder` (roles `admin`, `hr`, `manager`, `employee` +
  permissões do core e dos módulos). Ficheiros em `storage/tenants/{id}`; cache, filas e permissões separadas
  por tenant.
- **Provisionamento**: `TenantProvisioner` (usado pela API, CLI e seeder): domínio `{sub}.TENANT_BASE_DOMAIN`,
  admin do cliente com role `admin`, licenças iniciais; reverte (apaga a BD) se algo falhar.
- **Licenciamento**: `LicenseService` (cache, vigência por datas, validação de `requires`), middleware
  `module:{key}` → `403 MODULE_NOT_LICENSED`, job middleware `RequiresModuleLicense` para jobs/listeners.
- **Módulos**: `ModuleRegistry`, `ModuleServiceProvider`, `modules:sync`; módulo `Vacations` com `module.json`,
  provider, permissões no seeder e rota placeholder `GET /api/vacations/my`.
- **API tenant**: `POST /api/auth/login|logout|forgot-password|reset-password`, `GET /api/me`
  (user, employee=null até à Fase 2, roles, permissions, active_modules, tenant). Utilizadores inativos não
  entram; tenant suspenso → `403 TENANT_SUSPENDED`; domínio desconhecido → `404 TENANT_NOT_FOUND`.
- **API central** (`/api/central`, guard `central`): auth (login/me/logout), `GET modules`,
  `GET/POST tenants`, `GET/PUT tenants/{id}`, `POST tenants/{id}/suspend|activate`,
  `GET/PUT tenants/{id}/modules`. Alterações registadas em `central_audit_logs`.
- **CLI**: `tenants:create`, `tenants:modules`, `modules:sync`.
- **Erros** uniformes `{ message, code, errors? }` (VALIDATION_ERROR, UNAUTHENTICATED, FORBIDDEN, NOT_FOUND,
  MODULE_NOT_LICENSED, …). Traduções PT-PT (e EN).
- **Testes Pest (29):** login central, gestão de tenants, ativação de módulos e vigência, licenciamento
  (módulo inativo → 403, desativar/reativar), jobs sem licença, `/me`, login/logout/reset de password no tenant,
  tenant suspenso, isolamento de dados entre tenants, sessão de um tenant recusada noutro, dependências entre
  módulos.

### Frontend
- `core/api/gateway.js` (fetch + cookies Sanctum + CSRF + retry em 419 + `ApiError`), `callApi` (401 → logout,
  403 MODULE_NOT_LICENSED → página "módulo não disponível"), `toFormFields` para erros 422.
- Store com injeção dinâmica; bootstrap `GET /me` → carrega os manifestos dos módulos ativos (lazy) → menu e rotas.
- Páginas: login, recuperar/definir password, início (atalhos dos módulos), 404, módulo não disponível.
  Layout responsivo (menu lateral recolhe em telemóvel).
- Módulo `vacations` esqueleto (menu "As minhas férias" + página lazy).
- Painel central (`central.html`): login, lista de empresas (pesquisa, filtro, paginação), criar empresa
  (com admin e módulos), detalhe (editar dados, suspender/reativar, abrir subdomínio) e gestão de módulos
  (ativo, período, máx. utilizadores, em vigor).
- Componentes partilhados: `PageHeader`, `StatusTag`, `DataTable`, `FullPageLoader`; estilos em
  `shared/styles/global.css`; i18n com namespaces (`core`, `central`, um por módulo).

## Como testar
1. `make up && make install` (ver README) — ou o arranque sem Docker.
2. Painel http://admin.nossaapp.localhost:8080 (`admin@nossaapp.pt` / `password`) → **Nova empresa**
   (subdomínio `acme`, admin, marcar Férias).
3. http://acme.nossaapp.localhost:8080 → login com o admin criado → o menu mostra **As minhas férias**.
4. No painel, abrir a empresa → desligar Férias → **Guardar módulos**.
5. No tenant, recarregar: o menu deixa de ter Férias; `/ferias` mostra "Módulo não disponível" e
   `GET /api/vacations/my` devolve `403 MODULE_NOT_LICENSED`. Reativar repõe tudo.
6. `cd backend && ./vendor/bin/pest`.

Os passos 2–5 foram verificados com Chromium (Playwright) contra `artisan serve` + Vite com SQLite e Redis.
O `docker-compose.yml` foi validado com `docker compose config`, mas **não foi arrancado** neste ambiente
(não há daemon Docker disponível) — é o primeiro ponto a confirmar.

## Pendente / para fases seguintes
- 2FA dos administradores centrais (coluna existe; falta fluxo) e gestão de administradores centrais.
- `max_users` é guardado mas ainda não é aplicado (aplicar quando existirem utilizadores/colaboradores — Fase 2).
- Bloquear sessões já abertas de utilizadores desativados (hoje só o login é recusado) — Fase 2.
- Criação de tenant é síncrona; em produção passar o pipeline para fila (`shouldBeQueued(true)`) com estado
  "a provisionar" no painel.
- Endpoints de integração do painel (`/central/tenants/{id}/integration`) — Fase 5.
- Configuração nginx de produção (build estática + fallback por host).
