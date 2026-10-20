# Fase 2 — Core de RH

**Estado:** concluída, à espera de validação.

## Plano
- BD de tenant: `departments` (árvore), `positions`, `employees` (soft delete, IBAN encriptado), `attachments`,
  `notifications`, `audit_logs`, `holidays`, `settings`.
- Permissões `core.*` + policies: RH gere tudo, chefias veem a equipa, o colaborador vê/edita o seu perfil.
- Endpoints da spec (§7 Core) + anexos de colaborador, pesquisa para selects, importação de feriados nacionais
  e auditoria.
- Frontend: portal (início, perfil, notificações, feriados) e backoffice (colaboradores, departamentos, cargos,
  auditoria); componentes partilhados.
- **Dados falsos** de demonstração no tenant `demo` para validar.

## O que foi feito

### Backend
- **Colaboradores** (`/api/employees`): CRUD com filtros (pesquisa, departamento, chefia, estado), ordenação e
  paginação; criação opcional de conta de acesso com role (email de boas-vindas para definir password);
  validação de ciclos na hierarquia; soft delete desativa a conta. `GET /employees/lookup` para selects.
  Anexos: `GET/POST /employees/{id}/attachments`, download e remoção.
- **Perfil**: `/api/me` inclui a ficha do colaborador; `PUT /me/profile` (telefone, morada, IBAN, idioma) e
  `POST /me/avatar` (JPG/PNG/WEBP ≤ 2 MB). Fotografia em `GET /employees/{id}/avatar`.
- **Departamentos** (árvore, responsável, sem ciclos, não apaga se tiver pessoas/subdepartamentos) e **cargos**.
- **Feriados**: CRUD (nacional/municipal/empresa, concelho, recorrente), `GET /holidays?year=` com recorrentes
  projetados, `POST /holidays/import-national` (13 feriados obrigatórios PT, Páscoa calculada).
  `HolidayService::between()` fica como contrato para o cálculo de dias úteis (Fase 4).
- **Notificações**: lista com contagem de não lidas, marcar uma/todas como lidas.
- **Auditoria**: trait `Auditable` em utilizadores, colaboradores, departamentos, cargos e feriados;
  `GET /api/audit-logs` (só admin).
- **Definições**: `SettingsService` (chave/valor) pronto para as Fases 3/4.
- **Segurança**: sessões de utilizadores desativados terminam (também nas rotas dos módulos).
- **Testes**: 49 Pest (20 novos nesta fase).

### Frontend
- Menu agrupado em **Portal** (Início, O meu perfil, Notificações, Feriados, + módulos) e **Gestão**
  (Colaboradores, Departamentos, Cargos, Auditoria), filtrado por permissões.
- Início com a minha ficha, notificações por ler, equipa, próximos feriados e atalhos de módulos.
- Cabeçalho com sino de notificações (atualizado a cada 60 s) e fotografia do utilizador.
- Páginas: perfil (com fotografia), notificações, colaboradores (lista + ficha com separadores Ficha /
  Documentos / Equipa + formulário lateral), departamentos (árvore), cargos, feriados (por ano, importar
  nacionais), auditoria (com diferenças antes/depois).
- Partilhados: `createResource`, `EmployeeSelect`, `FileUpload`, `UserAvatar`, `ConfirmDelete`, `ItemList`,
  `useSaveFeedback`, formatação de datas/tamanhos, `toTree`.

## Dados falsos (tenant demo) — password de todos: `password`
| Email | Perfil | Notas |
|---|---|---|
| `admin@demo.pt` | admin | Sofia Almeida, CEO (topo da hierarquia) |
| `rh@demo.pt` | hr | Marta Ribeiro, Diretora de RH |
| `chefe.ti@demo.pt` | manager | Ricardo Sousa, chefia de 5 pessoas (Tecnologia) |
| `chefe.comercial@demo.pt` | manager | Inês Carvalho, chefia de 5 pessoas (Comercial) |
| `colaborador@demo.pt` | employee | João Pereira (Tecnologia) |

3 departamentos, 15 colaboradores, feriados nacionais do ano atual e do seguinte, Santo António (Lisboa) e
24/12 (empresa), 2 notificações por utilizador. Para recriar: `make fresh` (ou `migrate:fresh --seed`).

## Como testar
1. `make fresh` (sem Docker: apagar `database/*.sqlite` e correr `migrate` + `db:seed`).
2. http://demo.nossaapp.localhost:8080 com `rh@demo.pt` → Colaboradores → abrir alguém → Editar / Documentos;
   Novo colaborador com conta (o email aparece no Mailpit); Departamentos; Feriados → importar.
3. `chefe.ti@demo.pt` → Colaboradores mostra só a equipa, sem NIF/IBAN.
4. `colaborador@demo.pt` (de preferência no telemóvel) → O meu perfil → fotografia, morada → Guardar.
5. `admin@demo.pt` → Auditoria.
6. `cd backend && ./vendor/bin/pest`.

Verificado com Chromium (Playwright) contra `artisan serve` + Vite (SQLite + Redis), incluindo vista de
telemóvel. O Docker continua por arrancar neste ambiente (sem daemon).

## Pendente
- `max_users` das licenças ainda não é aplicado (falta decidir o que conta: utilizadores ativos?).
- Ecrã de definições do tenant (o serviço existe; a UI entra com a definição "aprovação RH" nas Fases 3/4).
- Notificações em tempo real (hoje: polling de 60 s); miniaturas das fotografias.
- Tenants criados antes desta fase precisam de `php artisan tenants:migrate && php artisan tenants:seed`.
