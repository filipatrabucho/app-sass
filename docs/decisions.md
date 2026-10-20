# Registo de decisões

Formato: contexto → decisão → consequências. Mais recente no fim.

## D1 — Laravel 13 e PHP 8.4 no Docker (Fase 1)
- **Contexto:** a especificação pede "última versão estável" e PHP 8.4+.
- **Decisão:** Laravel 13; imagem Docker `php:8.4-fpm`. O `composer.json` exige `php ^8.3` (o mínimo do
  Laravel 13) para permitir correr os testes em CI/máquinas com 8.3; produção e Docker usam 8.4.

## D2 — Estrutura `Modules/` própria em vez de `nwidart/laravel-modules` (Fase 1)
- **Contexto:** a spec admite as duas opções. O nwidart (v11+) impõe a sua própria estrutura (`app/` dentro do
  módulo, `module.json` próprio, ativação por ficheiro global) que não coincide com a da spec nem com o
  licenciamento por tenant.
- **Decisão:** estrutura própria, mínima:
  - `App\Core\Modules\ModuleRegistry` lê `Modules/*/module.json` (`key`, `name`, `version`, `requires`, `provider`, `seeder`).
  - `App\Core\Modules\ModuleServiceProvider` (base dos providers de módulo) regista automaticamente
    `routes/api.php` com prefixo `/api`, stack de tenant, `auth:sanctum` e **`module:{key}`** — é impossível um
    módulo expor uma rota sem o middleware de licença. Também regista `Database/Migrations` nas migrations de
    tenant e `lang/` com o namespace do módulo.
  - Autoload PSR-4 `Modules\\` → `Modules/`.
- **Consequências:** zero "magia" extra; adicionar um módulo = criar a pasta com `module.json` + provider e
  correr `php artisan modules:sync` para o catálogo central.

## D3 — Licenças: cache sem tags de tenant (Fase 1)
- **Contexto:** o `CacheTenancyBootstrapper` etiqueta as chamadas `Cache::get()` com o tenant atual. O painel
  central (contexto central) tem de invalidar a mesma chave que o tenant lê.
- **Decisão:** o `LicenseService` usa `Cache::store()` (repositório sem tags) com a chave
  `licenses:tenant:{id}`. Guarda as linhas de `tenant_modules` e calcula a vigência (datas) em cada leitura, para
  uma licença expirar à meia-noite certa sem depender do TTL.

## D4 — Dependências entre módulos: recusar em vez de ativar automaticamente (Fase 1)
- **Contexto:** "validar dependências e ativá-las ou recusar com mensagem clara".
- **Decisão:** recusar (`422`, `code: MODULE_DEPENDENCY`, com a lista de dependências em falta por módulo). O
  mesmo vale para desativar um módulo de que outro ativo depende. É explícito e evita ativar (e faturar) módulos
  sem o administrador perceber.

## D5 — Permissões de todos os módulos criadas em todos os tenants (Fase 1)
- **Decisão:** o `TenantDatabaseSeeder` corre os seeders de permissões de todos os módulos instalados,
  licenciados ou não. O acesso é decidido pela licença; assim ativar/reativar um módulo não exige seeds. Novos
  módulos em tenants existentes: `php artisan tenants:seed` (os seeders são idempotentes).
- O role `admin` recebe todas as permissões existentes.

## D6 — Sessões: cookies host-only + verificação do tenant na sessão (Fase 1)
- **Contexto:** sessões em Redis partilhado; cada subdomínio é um tenant.
- **Decisão:** `SESSION_DOMAIN=null` (cookie host-only, nunca partilhado entre subdomínios) e, como defesa em
  profundidade, `EnsureSessionBelongsToTenant` guarda o `tenant_id` na sessão e invalida-a se for apresentada
  noutro tenant (corre antes da autenticação — prioridade configurada em `bootstrap/app.php`).
- `sanctum.stateful` = o próprio host do pedido (o SPA é servido no mesmo host que a API via nginx).

## D7 — Painel central: mesma app Laravel, rotas só nos domínios centrais (Fase 1)
- **Decisão:** `routes/central.php` registado com `Route::domain()` para cada `CENTRAL_DOMAINS`, prefixo
  `/api/central`, guard de sessão `central` (tabela `central_admins` na BD central). Num domínio de tenant estas
  rotas não existem (404). Horizon fica no domínio central e exige sessão `central`.
- Coluna `2fa_secret` da spec chama-se `two_factor_secret` (nomes de coluna a começar por dígito obrigam a
  quoting em todo o lado). 2FA fica por implementar (ver pendentes da Fase 1).
- Alterações feitas no painel (criação de tenant, dados, estado, licenças) ficam em `central_audit_logs` na BD
  central (o `audit_logs` da spec vive na BD de cada tenant).

## D8 — Frontend: Redux Toolkit como "Redux", sem RTK Query (Fase 1)
- **Decisão:** usar `@reduxjs/toolkit` (`configureStore`, `createAction`, `createReducer`) por ser a forma
  oficial de usar Redux. **Não** se usa RTK Query nem thunks (desativados): todo o data fetching é
  action → saga → `core/api/gateway.js` (fetch) → reducer. ESLint proíbe `axios`.
- Cada módulo segue `store/actions.js`, `store/reducer.js`, `store/sagas.js`; a store suporta
  `injectReducer`/`injectSaga` para módulos carregados em lazy.
- O tratamento global de 401 / 403 `MODULE_NOT_LICENSED` é feito pela saga `callApi` (que envolve o gateway) e
  não no gateway, para o gateway continuar uma função pura de I/O.

## D9 — Uma build Vite com duas entradas (Fase 1)
- **Decisão:** `index.html` (app do tenant) e `central.html` (painel central). Em dev, um plugin do Vite serve
  `central.html` nos hosts de `VITE_CENTRAL_HOSTS`. Em produção, o nginx do host admin faz fallback para
  `/central.html` e o dos tenants para `/index.html`. Partilham `core/api`, `shared/` e i18n.
- Cada módulo do frontend é registado em `src/modules/index.js` com `load` (import dinâmico) e `basePath`; um
  URL de um módulo inativo mostra "módulo não disponível" em vez de 404.

## D10 — Testes com SQLite (Fase 1)
- **Decisão:** os testes Pest correm com a BD central em memória e BDs de tenant em ficheiros SQLite
  (`testing_tenant_*.sqlite`, apagados no fim de cada teste). Não exigem MariaDB/Redis. O Docker usa MariaDB.

## D11 — Auditoria por trait nos models + morph map (Fase 2)
- **Decisão:** `App\Core\Audit\Concerns\Auditable` regista `created/updated/deleted/restored` em `audit_logs`
  (diferenças, utilizador, IP). Atributos `$hidden` e `$auditMasked` (ex.: IBAN, password) ficam como `***`.
  Os tipos polimórficos usam nomes estáveis (`employee`, `user`, `department`…) via `Relation::morphMap`;
  cada módulo regista os seus no seu provider.

## D12 — Role admin via `Gate::before`; visibilidade por hierarquia (Fase 2)
- **Decisão:** além de receber todas as permissões no seeder, o role `admin` passa em qualquer `Gate`/policy
  (`Gate::before`), para não depender de re-seeds quando surgem permissões novas.
- RH (`core.employees.manage`) vê todos; chefias (`core.employees.view`) só a equipa direta e indireta
  (via `manager_id`) e **sem** dados sensíveis (NIF, IBAN, morada); todos veem a própria ficha.

## D13 — Ficheiros privados, sempre servidos por controller (Fase 2)
- **Decisão:** disco `local` isolado por tenant (`storage/tenants/{id}/app`), sem URLs públicas. Fotografias e
  anexos são servidos por rotas autenticadas. O serviço `FileStorage` é o ponto único (fácil de trocar para S3
  com prefixo por tenant).

## D14 — Contas de acesso criadas sem password (Fase 2)
- **Decisão:** ao criar um colaborador com conta, o utilizador recebe uma password aleatória e um email de
  boas-vindas com link para definir a password (token do broker de reposição). Apagar um colaborador é soft
  delete e desativa a conta; o middleware `EnsureUserIsActive` termina sessões de contas desativadas.

## D15 — Frontend: fábrica `createResource` e `ItemList` (Fase 2)
- **Decisão:** `core/store/createResource.js` gera actions/reducer/saga CRUD standard (lista paginada, detalhe,
  gravar, apagar, refrescar lista) — usado por colaboradores, departamentos, cargos, feriados e auditoria.
  Ao terminar sessão, todos os recursos são limpos.
- O `List` do AntD 6 está obsoleto e o substituto (`Listy`) é uma lista virtual com outra API; criámos
  `shared/components/ItemList` (simples, responsivo).
- Uploads: o `File` segue na action até à saga (configurado no `serializableCheck`), nunca no estado.

## D16 — Dados de demonstração (Fase 2)
- **Decisão:** `DemoTenantSeeder` (dados falsos: 3 departamentos, 15 colaboradores com hierarquia e contas,
  feriados, notificações) corre automaticamente no tenant `demo` do seeder central. Pode ser aplicado a outro
  tenant vazio com `php artisan tenants:seed --class="Database\Seeders\DemoTenantSeeder" --tenants=<id>`.
