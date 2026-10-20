DC = docker compose
PHP = $(DC) exec app

.PHONY: up down install migrate seed fresh test lint tenant shell

up: ## Arranca o ambiente
	$(DC) up -d --build

down:
	$(DC) down

install: ## Primeira instalação (dependências, chave, migrations e seed demo)
	$(PHP) composer install
	$(PHP) sh -c "test -f .env || cp .env.example .env"
	$(PHP) php artisan key:generate
	$(PHP) php artisan migrate --force
	$(PHP) php artisan db:seed --force

migrate: ## Migrations central + todos os tenants
	$(PHP) php artisan migrate --force
	$(PHP) php artisan tenants:migrate

seed:
	$(PHP) php artisan db:seed --force

fresh: ## Apaga BDs de tenant e recria tudo (dev)
	$(PHP) php artisan tinker --execute 'App\Core\Tenancy\Models\Tenant::all()->each->delete();' || true
	$(PHP) php artisan migrate:fresh --force --seed

test:
	$(PHP) ./vendor/bin/pest

lint:
	$(PHP) ./vendor/bin/pint --test
	$(DC) exec node npm run lint

tenant: ## make tenant NAME="Empresa X" SUB=empresax EMAIL=rh@empresax.pt
	$(PHP) php artisan tenants:create "$(NAME)" $(SUB) --admin-email=$(EMAIL) --module=vacations

shell:
	$(PHP) bash
