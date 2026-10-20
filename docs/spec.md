# MVP — Plataforma Modular de RH e Gestão (SaaS multi-tenant)

> Documento de especificação para desenvolvimento. Lê tudo antes de começar.
> Trabalha **por fases**, pela ordem indicada. No fim de cada fase pára, resume o que foi feito e espera validação antes de avançar.

---

## 1. Contexto do produto

Aplicação web vendida a empresas (B2B), **sempre alojada por nós** (SaaS). Cada empresa cliente é um *tenant*. A aplicação é composta por **módulos vendáveis individualmente**, todos no mesmo código. Os módulos são ativados ou desativados por cliente num **painel central** só nosso.

A aplicação pode integrar com a Web API do **Cegid** (ERP/salários) de cada cliente, mas tem de funcionar também **sem integração**, com a sua própria base de dados.

### Âmbito do MVP
1. **Core**: auth, multi-tenancy, painel central, licenciamento de módulos, colaboradores, departamentos, permissões, motor de aprovações, notificações, ficheiros, audit log, feriados.
2. **Módulo Férias**: marcação, aprovação, cancelamento, saldos, calendário.
3. **Camada de integração** (`PayrollProvider`) com implementação `Null` funcional e esqueleto `Cegid`.
4. **Portal do colaborador** e **backoffice RH** (apenas para o que existir no MVP).

Fora do MVP (mas a arquitetura tem de os prever): Documentos RH/Recibos, Onboarding, Helpdesk, Despesas, Clientes, Projetos, Timesheets.

---

## 2. Stack

**Backend**
- PHP 8.4+, Laravel (última versão estável)
- MariaDB
- Redis (cache, filas, sessões)
- Laravel Sanctum (auth SPA via cookies + tokens para futura app mobile)
- `stancl/tenancy` (uma base de dados por tenant)
- `spatie/laravel-permission`
- `nwidart/laravel-modules` (ou estrutura `Modules/` própria equivalente)
- Pest para testes
- Laravel Horizon para monitorizar filas

**Frontend**
- React + Vite
- Ant Design (versão atual)
- Redux + Redux-Saga para estado e data fetching
- **Proibido usar axios.** Todos os pedidos HTTP passam por um *api gateway* próprio baseado em `fetch`, chamado apenas a partir das sagas (fluxo: action → saga → api gateway → reducer).
- React Router com lazy loading por módulo
- Reutilizar componentes e classes partilhadas; evitar estilos inline e classes avulsas.

**Infra local**
- `docker-compose` com: app (PHP-FPM), nginx, mariadb, redis, node (vite), mailpit.

---

## 3. Arquitetura

### 3.1 Monólito modular
```
backend/
  app/
    Core/                    # código partilhado (não vendável)
      Auth/ Tenancy/ Licensing/ Employees/ Departments/
      Approvals/ Notifications/ Files/ Audit/ Holidays/
      Integrations/          # contratos + providers (PayrollProvider)
    Central/                 # painel de administração (só nós)
  Modules/
    Vacations/
      Http/Controllers  Http/Requests  Http/Resources
      Models  Services  Policies  Events  Listeners
      Database/Migrations  Database/Seeders  Database/Factories
      routes/api.php
      module.json            # key, nome, versão, dependências
      VacationsServiceProvider.php
      Tests/
frontend/
  src/
    core/                    # layout, auth, router, api gateway, store root
    shared/                  # componentes reutilizáveis (tabelas, forms, modal de aprovação, upload)
    modules/
      vacations/
        pages/ components/ store/ (actions, sagas, reducer) api/ routes.js menu.js index.js
    central/                 # UI do painel central
```

### 3.2 Regras de isolamento (obrigatórias)
- Um módulo **nunca** importa models ou classes internas de outro módulo.
- Comunicação entre módulos é feita **apenas** por:
  - **Eventos** Laravel (ex.: `VacationApproved`);
  - **Contratos/interfaces** expostos no Core (ex.: `WorkCodeProvider`, `PayrollProvider`).
- Os módulos podem depender do Core. O Core nunca depende de um módulo.
- Cada módulo declara as dependências no `module.json`:
  ```json
  { "key": "vacations", "name": "Férias", "version": "1.0.0", "requires": [] }
  ```

### 3.3 Multi-tenancy
- **Base de dados central** (`central`): tenants, domínios, administradores, catálogo de módulos e licenças.
- **Uma base de dados por tenant**: todos os dados da empresa cliente.
- Identificação do tenant por **subdomínio** (`empresa.nossaapp.pt`).
- Painel central em domínio próprio (`admin.nossaapp.pt`) com guard e utilizadores separados.
- Migrations do tenant correm em todas as bases de dados de tenant (`tenants:migrate`).
- Ficheiros guardados por tenant (`storage/tenants/{tenant_id}/...` ou bucket com prefixo).

### 3.4 Licenciamento de módulos
- Tabela central `tenant_modules`.
- Middleware `module:{key}` aplicado a **todas** as rotas de cada módulo. Devolve `403` com código `MODULE_NOT_LICENSED` se não estiver ativo.
- Jobs, listeners e comandos agendados de um módulo verificam também a licença antes de executar.
- Leitura das licenças com cache (Redis) e invalidação quando o painel central altera algo.
- Endpoint `GET /api/me` devolve utilizador, permissões e **módulos ativos**. O frontend usa isto para montar menu e rotas, mas **a decisão de acesso é sempre do backend**.
- Desativar um módulo **nunca apaga dados**. O módulo fica inacessível e, ao reativar, tudo volta.
- Ao ativar um módulo, validar dependências (`requires`) e ativá-las ou recusar com mensagem clara.

---

## 4. Modelo de dados

### 4.1 Base de dados central
| Tabela | Campos principais |
|---|---|
| `central_admins` | id, name, email, password, 2fa_secret, timestamps |
| `tenants` | id (uuid), name, nif, contact_email, status (active/suspended), data (json), timestamps |
| `domains` | id, tenant_id, domain |
| `modules` | key (pk), name, description, version, requires (json) |
| `tenant_modules` | id, tenant_id, module_key, active, starts_at, ends_at, max_users (nullable), timestamps |
| `tenant_integrations` | id, tenant_id, provider (null/cegid), base_url, credentials (encrypted json), active, last_sync_at |

### 4.2 Base de dados de tenant — Core
| Tabela | Campos principais |
|---|---|
| `users` | id, name, email, password, locale, active, last_login_at, timestamps |
| `departments` | id, name, parent_id, manager_employee_id, timestamps |
| `positions` | id, name |
| `employees` | id, user_id, employee_number, external_id (Cegid), first_name, last_name, nif, birth_date, phone, address (json), iban (encrypted), department_id, position_id, manager_id (employee), hire_date, avatar_path, status, timestamps, soft deletes |
| `roles` / `permissions` | spatie |
| `approval_requests` | id, type (ex.: `vacation`), approvable_type, approvable_id, requester_id, status (pending/approved/rejected/cancelled), current_step, timestamps |
| `approval_steps` | id, approval_request_id, order, approver_type (manager/role/user), approver_id, status, decided_by, decided_at, comment |
| `attachments` | id, attachable_type, attachable_id, path, original_name, mime, size, uploaded_by |
| `notifications` | tabela standard Laravel |
| `audit_logs` | id, user_id, action, auditable_type, auditable_id, old_values (json), new_values (json), ip, created_at |
| `holidays` | id, date, name, type (national/municipal/company), region (nullable), recurring |
| `integration_logs` | id, provider, direction (pull/push), entity, entity_id, status, request (json), response (json), error, created_at |
| `settings` | key, value (json) |

### 4.3 Módulo Férias
| Tabela | Campos principais |
|---|---|
| `vacation_policies` | id, name, days_per_year, allow_carry_over, max_carry_over_days, carry_over_expires_month, allow_half_days |
| `vacation_balances` | id, employee_id, year, entitled_days, carried_over_days, used_days, pending_days |
| `vacation_requests` | id, employee_id, start_date, end_date, half_day_start, half_day_end, business_days, notes, status (pending/approved/rejected/cancelled/cancel_requested), synced_at, external_id, timestamps |

Regras:
- `business_days` calculado no backend excluindo fins de semana e `holidays`.
- Não permitir sobreposição com pedidos pendentes ou aprovados do mesmo colaborador.
- Não permitir exceder o saldo disponível (`entitled + carried_over - used - pending`).
- Pedido criado → cria `approval_request` (passo 1: chefia direta; passo 2 opcional: RH, configurável em `settings`).
- Cancelar férias **pendentes**: o próprio colaborador.
- Cancelar férias **aprovadas**: cria pedido de cancelamento (`cancel_requested`) que o RH aprova.
- Atualizar `vacation_balances` de forma transacional em cada mudança de estado.

---

## 5. Motor de aprovações (Core)

Serviço genérico reutilizável por todos os módulos futuros (despesas, documentos, etc.):

```php
interface Approvable {
    public function approvalType(): string;
    public function approvalSteps(): array;     // define os passos
    public function onApproved(): void;
    public function onRejected(?string $reason): void;
    public function onCancelled(): void;
}

ApprovalService::submit(Approvable $item, Employee $requester): ApprovalRequest
ApprovalService::approve(ApprovalRequest $r, User $by, ?string $comment)
ApprovalService::reject(ApprovalRequest $r, User $by, string $reason)
ApprovalService::cancel(ApprovalRequest $r, User $by)
```

- Dispara eventos `ApprovalSubmitted`, `ApprovalStepApproved`, `ApprovalCompleted`, `ApprovalRejected`.
- Notifica o aprovador seguinte (email + in-app) e o requerente no fim.
- Endpoint transversal `GET /api/approvals/pending` lista tudo o que o utilizador tem para aprovar, de todos os módulos ativos.

---

## 6. Integrações (Cegid)

```php
interface PayrollProvider {
    public function testConnection(): bool;
    public function getEmployees(): Collection;                 // EmployeeData DTOs
    public function getEmployee(string $externalId): EmployeeData;
    public function getPayslips(Employee $e, int $year): Collection; // para módulo futuro
    public function pushVacation(VacationRequest $r): string;   // devolve external_id
    public function cancelVacation(VacationRequest $r): void;
}
```

- `NullPayrollProvider`: implementação por defeito. Não faz nada externo; a app funciona só com a BD local.
- `CegidPayrollProvider`: esqueleto com cliente HTTP (Laravel HTTP client), autenticação configurável e métodos com `TODO`. Os endpoints concretos da Web API do Cegid serão definidos depois.
- O provider é resolvido por tenant a partir de `tenant_integrations`.
- Todas as chamadas externas correm em **jobs em fila** com retries e backoff, e registam em `integration_logs`.
- Fonte de verdade:
  - Dados contratuais e recibos → Cegid (sync para cache local).
  - Pedidos de férias → BD local; depois de aprovados, `pushVacation` em job.
- Comando agendado `integrations:sync-employees` (diário) por tenant com integração ativa.

---

## 7. API (REST, JSON, prefixo `/api`)

Convenções: API Resources, Form Requests para validação, Policies para autorização, paginação standard, erros com `{ "message": "...", "code": "...", "errors": {...} }`.

### Core
```
POST   /auth/login            POST /auth/logout
POST   /auth/forgot-password  POST /auth/reset-password
GET    /me                    (user + employee + permissions + active_modules)
PUT    /me/profile            POST /me/avatar
GET    /employees             POST /employees     GET/PUT/DELETE /employees/{id}
GET    /departments           (CRUD)
GET    /holidays              (CRUD, RH)
GET    /approvals/pending     POST /approvals/{id}/approve   POST /approvals/{id}/reject
GET    /notifications         POST /notifications/{id}/read
```

### Férias (middleware `module:vacations`)
```
GET    /vacations/my                  # pedidos do próprio
GET    /vacations/my/balance
POST   /vacations                     # criar pedido
POST   /vacations/{id}/cancel         # cancelar (pendente) ou pedir cancelamento (aprovado)
GET    /vacations/team                # equipa do chefe
GET    /vacations/calendar?from&to&department_id
GET    /vacations                     # RH: todos, filtros
POST   /vacations/{id}/approve-cancel # RH
GET/PUT /vacations/balances           # RH: gerir saldos
GET/POST/PUT /vacations/policies      # RH
POST   /vacations/business-days       # pré-cálculo para o form
```

### Central (domínio admin, guard `central`)
```
POST /central/auth/login
GET/POST/PUT /central/tenants          # criar tenant cria BD, corre migrations e seeds, cria admin do cliente
POST /central/tenants/{id}/suspend
GET  /central/modules
PUT  /central/tenants/{id}/modules     # ativar/desativar com datas
GET/PUT /central/tenants/{id}/integration
POST /central/tenants/{id}/integration/test
```

---

## 8. Permissões (roles iniciais por tenant)

- `admin` — tudo dentro do tenant, incluindo configurações.
- `hr` — gestão de colaboradores, saldos, políticas, feriados, aprovação final e cancelamentos.
- `manager` — vê e aprova pedidos da sua equipa (relação `manager_id`).
- `employee` — portal do colaborador.

Permissões no formato `{modulo}.{recurso}.{acao}` (ex.: `vacations.requests.approve`). Cada módulo regista as suas no seeder do módulo.

---

## 9. Frontend

- **Bootstrap**: depois do login, `GET /me` → guarda utilizador, permissões e `active_modules` na store.
- **Registo de módulos**: cada módulo exporta `{ key, routes, menu, reducer, saga }`. O `core` só regista os que estão em `active_modules` e carrega-os com `React.lazy`.
- **Api gateway** (`core/api/gateway.js`): wrapper de `fetch` com base URL, credenciais (cookies Sanctum + CSRF), tratamento de 401 (logout), 403 `MODULE_NOT_LICENSED` (redireciona para página "módulo não disponível") e 422 (erros de formulário).
- **Layouts**:
  - Portal do colaborador: perfil, os meus pedidos, saldo de férias, calendário, notificações.
  - Backoffice (manager/hr/admin): aprovações pendentes, colaboradores, departamentos, feriados, gestão de férias.
  - Painel central: app separada (ou entrada separada no Vite) para `admin.nossaapp.pt`.
- **Componentes partilhados** (`shared/`): `DataTable` (filtros, paginação server-side), `FormField`s, `ApprovalActions` (aprovar/rejeitar com comentário), `StatusTag`, `FileUpload`, `EmployeeSelect`, `DateRangeWithBusinessDays`, `PageHeader`.
- Interface em **PT-PT** com i18n preparado (chaves, não strings no código).
- Responsivo (o portal do colaborador tem de funcionar bem em telemóvel).

---

## 10. Qualidade

- Testes Pest para: licenciamento (rota de módulo inativo → 403), isolamento entre tenants, cálculo de dias úteis, regras de saldo e sobreposição, fluxo completo de aprovação e cancelamento.
- Factories e seeders de demo: 1 tenant demo com 3 departamentos, 15 colaboradores, hierarquia, feriados nacionais PT do ano corrente, módulo Férias ativo.
- Laravel Pint e ESLint + Prettier configurados.
- `README.md` com setup local, comandos para criar tenant e ativar módulos.
- Segredos só em `.env`; credenciais de integração encriptadas (`encrypted` cast).
- Audit log em todas as alterações a colaboradores, pedidos e licenças.

---

## 11. Fases de desenvolvimento

**Fase 1 — Fundação**
Docker, Laravel, React/Vite, tenancy (central + tenant), domínios, painel central mínimo (login, CRUD tenants, ativar módulos), middleware `module:`, `GET /me`, auth Sanctum no tenant.
*Critério:* criar um tenant no painel, entrar no subdomínio, fazer login e ver o menu vazio/condicionado pelos módulos.

**Fase 2 — Core de RH**
Colaboradores, departamentos, cargos, roles/permissões, perfil + avatar, feriados, ficheiros, notificações, audit log, layout portal/backoffice e componentes partilhados.
*Critério:* RH cria colaboradores com hierarquia; colaborador edita o perfil.

**Fase 3 — Motor de aprovações**
`ApprovalService`, eventos, notificações, endpoint e ecrã de aprovações pendentes.
*Critério:* testes do fluxo genérico com um `Approvable` de teste.

**Fase 4 — Módulo Férias**
Políticas, saldos, pedidos, cálculo de dias úteis, calendário de equipa, cancelamentos, ecrãs no portal e no backoffice.
*Critério:* fluxo completo colaborador → chefia → RH, com saldos corretos; desativar o módulo no painel bloqueia API e menu.

**Fase 5 — Integrações**
`PayrollProvider`, `NullPayrollProvider`, esqueleto `CegidPayrollProvider`, configuração por tenant no painel, jobs, `integration_logs`, teste de ligação, push de férias aprovadas.
*Critério:* com provider `null` tudo funciona; com `cegid` os jobs são lançados e registados (mesmo que os endpoints ainda sejam `TODO`).

---

## 12. Instruções para quem desenvolve

- Segue as regras de isolamento da secção 3.2 sem exceções.
- Antes de cada fase, apresenta um plano curto (ficheiros a criar e decisões). No fim, lista o que foi feito, como testar e o que ficou pendente.
- Na dúvida entre duas abordagens, escolhe a mais simples que respeite a arquitetura e regista a decisão em `docs/decisions.md`.
- Não inventes endpoints da API Cegid; deixa `TODO` com a assinatura esperada.
