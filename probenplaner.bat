@echo off
setlocal EnableDelayedExpansion
chcp 65001 >nul

:: -----------------------------------------------------------------------------
:: Probenplaner CLI Tool (Windows Batch)
:: 
:: A professional command-line interface for managing the Probenplaner application
:: across development, test, and production environments.
:: Mirrors functionality of probenplaner.sh
:: -----------------------------------------------------------------------------

:: Enable VT100 escape sequences for colors (Windows 10/11)
for /f "tokens=4-5 delims=. " %%i in ('ver') do set VERSION=%%i.%%j
if "%VERSION%" geq "10.0" (
    reg add HKCU\Console /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1
)

:: Colors
set "ESC="
set "RED=%ESC%[31m"
set "GREEN=%ESC%[32m"
set "YELLOW=%ESC%[33m"
set "BLUE=%ESC%[34m"
set "NC=%ESC%[0m"

:: Project configuration
set "PROJECT_NAME=probenplaner"

goto :main_execution

:: -----------------------------------------------------------------------------
:: Helper Functions
:: -----------------------------------------------------------------------------

:get_git_version
git rev-parse --git-dir >nul 2>&1
if errorlevel 1 (
    set "GIT_VERSION=N/A"
) else (
    for /f %%i in ('git describe --tags --always 2^>nul') do set "GIT_VERSION=%%i"
    if not defined GIT_VERSION set "GIT_VERSION=N/A"
)
exit /b

:set_git_version
call :get_git_version
set "GIT_VERSION=%GIT_VERSION%"
echo %BLUE%📦 Version: %GIT_VERSION%%NC%
exit /b

:get_web_container
for /f "tokens=*" %%i in ('docker compose ps --services --filter "status=running" ^| findstr "web"') do (
    for /f "tokens=*" %%j in ('docker compose ps %%i --format "table {{.Name}}" ^| findstr /v "NAME"') do (
        set "WEB_CONTAINER=%%j"
        exit /b
    )
)
set "WEB_CONTAINER="
exit /b

:check_web_container
call :get_web_container
if "%WEB_CONTAINER%"=="" (
    echo %RED%❌ Error: Web container is not running%NC%
    echo Try starting it with: %GREEN%probenplaner.bat dev%NC%
    exit /b 1
)
exit /b 0

:check_docker
docker info >nul 2>&1
if errorlevel 1 (
    echo %RED%❌ Error: Docker is not running%NC%
    echo Please start Docker Desktop and try again.
    exit /b 1
)
exit /b 0

:print_help
echo %BLUE%╭─────────────────────────────────────────╮%NC%
echo %BLUE%│        Probenplaner CLI Tool            │%NC%
echo %BLUE%╰─────────────────────────────────────────╯%NC%
echo.
echo %GREEN%Usage:%NC% probenplaner.bat [command] [options]
echo.
echo %YELLOW%Environment Management:%NC%
echo   dev             🚀 Start development environment
echo   prod            🏭 Deploy production environment
echo   test            🧪 Start test environment
echo   down            ⬇️  Stop all containers
echo   restart         🔄 Restart current environment
echo   clean           🧹 Remove all containers and volumes
echo.
echo %YELLOW%Development Tools:%NC%
echo   build           🔨 Rebuild containers
echo   status          📊 Show system status
echo   logs [filter]   📋 View logs (filters: error, warn, info, debug, -f)
echo   shell           💻 Open shell in web container
echo   ps              📝 Show container status
echo.
echo %YELLOW%Database:%NC%
echo   migrate:status  📈 Show migration status
echo   migrate:up      ⬆️  Run pending migrations
echo   migrate:create  ➕ Create new migration
echo.
echo %YELLOW%Information:%NC%
echo   version         🏷️  Show current version
echo   help            ❓ Show this help
echo.
echo %GREEN%Examples:%NC%
echo   probenplaner.bat dev
echo   probenplaner.bat prod
echo   probenplaner.bat logs -f
echo   probenplaner.bat migrate:create add_user_preferences
exit /b

:print_status
echo %BLUE%📊 System Status%NC%
echo %BLUE%─────────────────%NC%

call :get_git_version
echo Version: %GREEN%%GIT_VERSION%%NC%

docker info >nul 2>&1
if errorlevel 1 (
    echo Docker: %RED%❌ Not running%NC%
    exit /b 1
) else (
    echo Docker: %GREEN%✅ Running%NC%
)

call :get_web_container
if not "%WEB_CONTAINER%"=="" (
    echo Web Container: %GREEN%✅ Running (%WEB_CONTAINER%)%NC%
    
    docker exec %WEB_CONTAINER% mysqladmin ping -h db -u root --password="%MYSQL_ROOT_PASSWORD%" >nul 2>&1
    if errorlevel 1 (
        echo Database: %YELLOW%⚠️  Connection issue%NC%
    ) else (
        echo Database: %GREEN%✅ Connected%NC%
    )
) else (
    echo Web Container: %RED%❌ Not running%NC%
)
echo.
exit /b

:: -----------------------------------------------------------------------------
:: Main Execution
:: -----------------------------------------------------------------------------

:main_execution

if "%1"=="" goto :help_cmd
if "%1"=="help" goto :help_cmd
if "%1"=="--help" goto :help_cmd
if "%1"=="-h" goto :help_cmd

if "%1"=="dev" goto :dev_cmd
if "%1"=="up" goto :dev_cmd
if "%1"=="prod" goto :prod_cmd
if "%1"=="test" goto :test_cmd
if "%1"=="build" goto :build_cmd
if "%1"=="down" goto :down_cmd
if "%1"=="clean" goto :clean_cmd
if "%1"=="restart" goto :restart_cmd
if "%1"=="status" goto :status_cmd
if "%1"=="version" goto :version_cmd
if "%1"=="logs" goto :logs_cmd
if "%1"=="ps" goto :ps_cmd
if "%1"=="shell" goto :shell_cmd
if "%1"=="migrate:status" goto :migrate_status_cmd
if "%1"=="migrate:up" goto :migrate_up_cmd
if "%1"=="migrate:create" goto :migrate_create_cmd

echo %RED%❌ Error: Unknown command '%1'%NC%
echo Use %GREEN%probenplaner.bat help%NC% to see available commands
exit /b 1

:help_cmd
call :print_help
exit /b

:dev_cmd
call :check_docker
call :set_git_version
echo %GREEN%🚀 Starting development environment...%NC%
docker compose up -d
echo %GREEN%✅ Development environment is ready!%NC%
echo    🌐 Web: %BLUE%http://localhost:8080%NC%
echo    📊 Status: %BLUE%probenplaner.bat status%NC%
echo    📋 Logs: %BLUE%probenplaner.bat logs%NC%
exit /b

:prod_cmd
call :check_docker
call :set_git_version
echo %GREEN%🏭 Building and deploying production environment...%NC%
echo %YELLOW%   Stopping existing production containers...%NC%
docker compose -f docker-compose.prod.yml down 2>nul
echo %YELLOW%   Building production containers...%NC%
docker compose -f docker-compose.prod.yml build
if errorlevel 1 (
    echo %RED%❌ Production build failed%NC%
    exit /b 1
)
echo %YELLOW%   Starting production environment...%NC%
docker compose -f docker-compose.prod.yml up -d
echo %GREEN%✅ Production environment deployed successfully!%NC%
echo    Version: %BLUE%%GIT_VERSION%%NC%
exit /b

:test_cmd
call :check_docker
call :set_git_version
echo %GREEN%🧪 Building and starting test environment...%NC%
echo %YELLOW%   Stopping existing test containers...%NC%
docker compose -f docker-compose.test.yml down 2>nul
echo %YELLOW%   Building test containers...%NC%
docker compose -f docker-compose.test.yml build
if errorlevel 1 (
    echo %RED%❌ Test build failed%NC%
    exit /b 1
)
echo %YELLOW%   Starting test environment...%NC%
docker compose -f docker-compose.test.yml up -d
echo %GREEN%✅ Test environment is ready!%NC%
echo    Version: %BLUE%%GIT_VERSION%%NC%
exit /b

:build_cmd
call :check_docker
call :set_git_version
echo %YELLOW%🔨 Rebuilding development containers...%NC%
docker compose build
if errorlevel 1 (
    echo %RED%❌ Build failed%NC%
    exit /b 1
)
echo %GREEN%✅ Build completed successfully!%NC%
echo    Version: %BLUE%%GIT_VERSION%%NC%
exit /b

:down_cmd
call :check_docker
echo %YELLOW%⬇️  Stopping containers...%NC%
docker compose down
docker compose -f docker-compose.prod.yml down 2>nul
docker compose -f docker-compose.test.yml down 2>nul
echo %GREEN%✅ All containers stopped%NC%
exit /b

:clean_cmd
call :check_docker
echo %YELLOW%🧹 Cleaning up containers and volumes...%NC%
docker compose down -v --remove-orphans 2>nul
docker compose -f docker-compose.prod.yml down -v --remove-orphans 2>nul
docker compose -f docker-compose.test.yml down -v --remove-orphans 2>nul
docker system prune -f >nul
echo %GREEN%✅ Cleanup completed%NC%
exit /b

:restart_cmd
call :check_docker
call :set_git_version
echo %YELLOW%🔄 Restarting development environment...%NC%
docker compose down
docker compose up -d
echo %GREEN%✅ Environment restarted with version: %GIT_VERSION%%NC%
exit /b

:status_cmd
call :print_status
exit /b

:version_cmd
call :get_git_version
echo %BLUE%🏷️  Current Version: %GREEN%%GIT_VERSION%%NC%
exit /b

:logs_cmd
call :check_docker
call :check_web_container || exit /b 1
echo %BLUE%📋 Container Logs%NC%

if "%2"=="-f" goto :logs_follow
if "%2"=="--follow" goto :logs_follow
if "%2"=="error" goto :logs_error
if "%2"=="warn" goto :logs_warn
if "%2"=="info" goto :logs_info
if "%2"=="debug" goto :logs_debug

echo %YELLOW%Showing recent logs...%NC%
docker logs --tail 100 %WEB_CONTAINER%
exit /b

:logs_follow
echo %YELLOW%Following logs (Ctrl+C to stop)...%NC%
docker logs -f %WEB_CONTAINER%
exit /b

:logs_error
echo %YELLOW%Filtering for errors...%NC%
docker logs %WEB_CONTAINER% 2>&1 | findstr /i "error"
exit /b

:logs_warn
echo %YELLOW%Filtering for warnings...%NC%
docker logs %WEB_CONTAINER% 2>&1 | findstr /i "warn warning"
exit /b

:logs_info
echo %YELLOW%Filtering for info...%NC%
docker logs %WEB_CONTAINER% 2>&1 | findstr /i "info"
exit /b

:logs_debug
echo %YELLOW%Filtering for debug...%NC%
docker logs %WEB_CONTAINER% 2>&1 | findstr /i "debug"
exit /b

:ps_cmd
call :check_docker
echo %BLUE%📝 Container Status%NC%
docker compose ps
exit /b

:shell_cmd
call :check_docker
call :check_web_container || exit /b 1
echo %GREEN%💻 Opening shell in %WEB_CONTAINER%...%NC%
docker exec -it %WEB_CONTAINER% /bin/bash
exit /b

:migrate_status_cmd
call :check_docker
call :check_web_container || exit /b 1
echo %BLUE%📈 Migration Status%NC%
docker exec %WEB_CONTAINER% php /var/www/html/database/cli-migrate.php status
exit /b

:migrate_up_cmd
call :check_docker
call :check_web_container || exit /b 1
echo %YELLOW%⬆️  Running pending migrations...%NC%
docker exec %WEB_CONTAINER% php /var/www/html/database/cli-migrate.php up
if errorlevel 1 (
    echo %RED%❌ Migration failed%NC%
    exit /b 1
)
echo %GREEN%✅ Migrations completed successfully%NC%
exit /b

:migrate_create_cmd
if "%2"=="" (
    echo %RED%❌ Error: Migration name required%NC%
    echo Usage: %GREEN%probenplaner.bat migrate:create ^<name^>%NC%
    echo Example: %BLUE%probenplaner.bat migrate:create add_user_preferences%NC%
    exit /b 1
)
call :check_docker
call :check_web_container || exit /b 1
echo %GREEN%➕ Creating migration: %BLUE%%2%NC%
docker exec %WEB_CONTAINER% php /var/www/html/database/cli-migrate.php create "%2"
if errorlevel 1 (
    echo %RED%❌ Failed to create migration%NC%
    exit /b 1
)
echo %GREEN%✅ Migration created successfully%NC%
exit /b