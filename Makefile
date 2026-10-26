.PHONY: help build up down restart logs shell composer artisan test migrate seed fresh install

help: ## Mostrar ayuda
	@echo "Comandos disponibles:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

build: ## Construir contenedores
	docker-compose build

up: ## Iniciar contenedores
	docker-compose up -d

down: ## Detener contenedores
	docker-compose down

restart: ## Reiniciar contenedores
	docker-compose restart

logs: ## Ver logs
	docker-compose logs -f

shell: ## Acceder al contenedor app
	docker-compose exec app sh

composer: ## Ejecutar composer install
	docker-compose exec app composer install

artisan: ## Ejecutar comando artisan (use: make artisan CMD="migrate")
	docker-compose exec app php artisan $(CMD)

test: ## Ejecutar tests
	docker-compose exec app php artisan test

migrate: ## Ejecutar migraciones
	docker-compose exec app php artisan migrate

seed: ## Ejecutar seeders
	docker-compose exec app php artisan db:seed

fresh: ## Migración fresca con seeders
	docker-compose exec app php artisan migrate:fresh --seed

install: ## Instalación completa
	@echo "Construyendo contenedores..."
	docker-compose build
	@echo "Iniciando servicios..."
	docker-compose up -d
	@echo "Esperando a que los servicios estén listos..."
	sleep 10
	@echo "Instalando dependencias..."
	docker-compose exec app composer install
	@echo "Copiando archivo .env..."
	docker-compose exec app cp .env.example .env
	@echo "Generando APP_KEY..."
	docker-compose exec app php artisan key:generate
	@echo "Ejecutando migraciones..."
	docker-compose exec app php artisan migrate --seed
	@echo "¡Instalación completa! El sistema está disponible en http://localhost:8000"

clean: ## Limpiar contenedores y volúmenes
	docker-compose down -v
	rm -rf backend/vendor backend/node_modules

ps: ## Listar contenedores
	docker-compose ps

nginx-reload: ## Recargar configuración de Nginx
	docker-compose exec nginx nginx -s reload

## ─── Frontend ──────────────────────────────────────────────
fe-install: ## Instalar dependencias del frontend
	cd frontend && npm install

fe-dev: ## Iniciar servidor de desarrollo frontend
	cd frontend && npm run dev

fe-build: ## Compilar frontend para producción
	cd frontend && npm run build

fe-up: ## Levantar frontend con Docker (perfil dev)
	docker-compose --profile dev up -d frontend

fe-logs: ## Ver logs del frontend
	docker-compose --profile dev logs -f frontend
