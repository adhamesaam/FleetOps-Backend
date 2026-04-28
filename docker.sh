#!/usr/bin/env bash
# FleetOps Backend Docker Management Script

COMMAND=${1:-help}

show_help() {
    echo -e "\033[1;36mFleetOps Backend - Docker Commands\033[0m"
    echo ""
    echo -e "\033[1;33mUsage: ./docker.sh [command]\033[0m"
    echo ""
    echo -e "\033[1;32mCommands:\033[0m"
    echo "  install     - Build and start containers (first time setup)"
    echo "  up          - Start all containers"
    echo "  down        - Stop all containers"
    echo "  restart     - Restart all containers"
    echo "  logs        - Show logs from all containers"
    echo "  logs-app    - Show logs from app container"
    echo "  logs-db     - Show logs from database"
    echo "  shell       - Access app container shell"
    echo "  db          - Access SQL Server CLI"
    echo "  migrate     - Run database migrations"
    echo "  fresh       - Fresh database with migrations"
    echo "  optimize    - Optimize Laravel caches"
    echo "  clear       - Clear all Laravel caches"
    echo "  test        - Run tests"
    echo "  status      - Show container status"
    echo "  clean       - Remove all containers and volumes"
    echo ""
}

install() {
    echo -e "\033[1;36mBuilding and starting FleetOps Backend...\033[0m"
    docker compose up -d --build
    
    echo -e "\033[1;33mWaiting for SQL Server to be ready...\033[0m"
    sleep 30
    
    echo -e "\033[1;33mCreating database...\033[0m"
    docker compose exec -T sqlserver /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "Fleetops12345678!" -C -Q "IF NOT EXISTS (SELECT * FROM sys.databases WHERE name = 'fleetops') CREATE DATABASE fleetops"
    
    echo -e "\033[1;33mCreating .env file...\033[0m"
    docker compose exec app sh -c 'if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || cat > .env << "EOF"
APP_NAME=FleetOps
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=info

DB_CONNECTION=sqlsrv
DB_HOST=sqlserver
DB_PORT=1433
DB_DATABASE=fleetops
DB_USERNAME=sa
DB_PASSWORD=Fleetops12345678!
DB_ENCRYPT=optional
DB_TRUST_SERVER_CERTIFICATE=true

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=database
QUEUE_CONNECTION=database

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

MAIL_MAILER=log
MAIL_FROM_ADDRESS="noreply@fleetops.com"
MAIL_FROM_NAME="FleetOps"
EOF
fi'
    
    echo -e "\033[1;33mGenerating application key...\033[0m"
    docker compose exec app php artisan key:generate --force
    
    echo -e "\033[1;33mRunning migrations...\033[0m"
    docker compose exec app php artisan migrate --force
    
    echo -e "\033[1;33mOptimizing application...\033[0m"
    docker compose exec app php artisan config:cache
    docker compose exec app php artisan route:cache
    
    echo ""
    echo -e "\033[1;32mSetup complete!\033[0m"
    echo -e "\033[1;36mAPI available at: http://localhost:8000\033[0m"
    echo -e "\033[1;36mHealth check: http://localhost:8000/api/health\033[0m"
}

start_containers() {
    echo -e "\033[1;36mStarting containers...\033[0m"
    docker compose up -d
    echo -e "\033[1;32mContainers started!\033[0m"
}

stop_containers() {
    echo -e "\033[1;36mStopping containers...\033[0m"
    docker compose down
    echo -e "\033[1;32mContainers stopped!\033[0m"
}

restart_containers() {
    echo -e "\033[1;36mRestarting containers...\033[0m"
    docker compose restart
    echo -e "\033[1;32mContainers restarted!\033[0m"
}

show_logs() {
    docker compose logs -f
}

show_applogs() {
    docker compose logs -f app
}

show_dblogs() {
    docker compose logs -f sqlserver
}

enter_shell() {
    docker compose exec app sh
}

enter_database() {
    docker compose exec sqlserver /opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P "Fleetops12345678!" -C
}

run_migrations() {
    echo -e "\033[1;36mRunning migrations...\033[0m"
    docker compose exec app php artisan migrate --force
    echo -e "\033[1;32mMigrations complete!\033[0m"
}

fresh_database() {
    echo -e "\033[1;36mRefreshing database...\033[0m"
    docker compose exec app php artisan migrate:fresh --force
    echo -e "\033[1;32mDatabase refreshed!\033[0m"
}

optimize_app() {
    echo -e "\033[1;36mOptimizing application...\033[0m"
    docker compose exec app php artisan config:cache
    docker compose exec app php artisan route:cache
    docker compose exec app php artisan view:cache
    echo -e "\033[1;32mOptimization complete!\033[0m"
}

clear_caches() {
    echo -e "\033[1;36mClearing caches...\033[0m"
    docker compose exec app php artisan cache:clear
    docker compose exec app php artisan config:clear
    docker compose exec app php artisan route:clear
    docker compose exec app php artisan view:clear
    echo -e "\033[1;32mCaches cleared!\033[0m"
}

run_tests() {
    echo -e "\033[1;36mRunning tests...\033[0m"
    docker compose exec app php artisan test
}

show_status() {
    docker compose ps
}

clean_all() {
    echo -e "\033[1;31mWARNING: This will remove all containers and volumes!\033[0m"
    read -p "Are you sure? (yes/no) " confirm
    if [ "$confirm" = "yes" ]; then
        docker compose down -v --rmi all
        echo -e "\033[1;32mCleanup complete!\033[0m"
    else
        echo -e "\033[1;33mCancelled.\033[0m"
    fi
}

# Command routing
case "$(echo "$COMMAND" | tr '[:upper:]' '[:lower:]')" in
    "install") install ;;
    "up") start_containers ;;
    "down") stop_containers ;;
    "restart") restart_containers ;;
    "logs") show_logs ;;
    "logs-app") show_applogs ;;
    "logs-db") show_dblogs ;;
    "shell") enter_shell ;;
    "db") enter_database ;;
    "migrate") run_migrations ;;
    "fresh") fresh_database ;;
    "optimize") optimize_app ;;
    "clear") clear_caches ;;
    "test") run_tests ;;
    "status") show_status ;;
    "clean") clean_all ;;
    *) show_help ;;
esac
