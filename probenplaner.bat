@echo off
setlocal EnableDelayedExpansion

:: Colors for Windows console
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

:: Container name
set "CONTAINER=probenplaner-web-1"

:: Helper functions
goto :main

:print_help
echo %BLUE%Probenplaner Development Helper%NC%
echo.
echo Usage: probenplaner.bat [command]
echo.
echo Docker Commands:
echo   up              Start all containers
echo   down            Stop all containers
echo   restart         Restart all containers
echo   logs [filter]   View container logs (filter: error, warn, info, debug)
echo   ps              Show container status
echo   shell           Open shell in web container
echo.
echo Migration Commands:
echo   migrate:status  Show migration status
echo   migrate:up      Run all pending migrations
echo   migrate:create [name]  Create a new migration
echo.
echo Examples:
echo   probenplaner.bat up
echo   probenplaner.bat logs error
echo   probenplaner.bat migrate:create add_user_field
exit /b

:: Check if Docker is running
:check_docker
docker info > nul 2>&1
if errorlevel 1 (
    echo %RED%Error: Docker is not running%NC%
    exit /b 1
)
exit /b 0

:: Check if container is running
:check_container
docker ps | findstr %CONTAINER% > nul
if errorlevel 1 (
    echo %RED%Error: Container %CONTAINER% is not running%NC%
    echo Try starting it with: %GREEN%probenplaner.bat up%NC%
    exit /b 1
)
exit /b 0

:main
if "%1"=="" (
    call :print_help
    exit /b
)

if "%1"=="up" (
    call :check_docker || exit /b 1
    echo %GREEN%Starting containers...%NC%
    docker compose up -d
    echo %GREEN%Containers are starting. View logs with: probenplaner.bat logs%NC%
    exit /b
)

if "%1"=="down" (
    call :check_docker || exit /b 1
    echo %YELLOW%Stopping containers...%NC%
    docker compose down
    exit /b
)

if "%1"=="restart" (
    call :check_docker || exit /b 1
    echo %YELLOW%Restarting containers...%NC%
    docker compose down
    docker compose up -d
    echo %GREEN%Containers restarted%NC%
    exit /b
)

if "%1"=="logs" (
    call :check_docker || exit /b 1
    call :check_container || exit /b 1
    if "%2"=="error" (
        docker logs %CONTAINER% 2>&1 | findstr /i "error"
    ) else if "%2"=="warn" (
        docker logs %CONTAINER% 2>&1 | findstr /i "warn warning"
    ) else if "%2"=="info" (
        docker logs %CONTAINER% 2>&1 | findstr /i "info"
    ) else if "%2"=="debug" (
        docker logs %CONTAINER% 2>&1 | findstr /i "debug"
    ) else (
        docker logs %CONTAINER%
    )
    exit /b
)

if "%1"=="ps" (
    call :check_docker || exit /b 1
    docker compose ps
    exit /b
)

if "%1"=="shell" (
    call :check_docker || exit /b 1
    call :check_container || exit /b 1
    echo %GREEN%Opening shell in %CONTAINER%...%NC%
    docker exec -it %CONTAINER% /bin/bash
    exit /b
)

if "%1"=="migrate:status" (
    call :check_docker || exit /b 1
    call :check_container || exit /b 1
    echo %BLUE%Migration Status:%NC%
    docker exec %CONTAINER% php /var/www/html/database/cli-migrate.php status
    exit /b
)

if "%1"=="migrate:up" (
    call :check_docker || exit /b 1
    call :check_container || exit /b 1
    echo %YELLOW%Running migrations...%NC%
    docker exec %CONTAINER% php /var/www/html/database/cli-migrate.php up
    exit /b
)

if "%1"=="migrate:create" (
    if "%2"=="" (
        echo %RED%Error: Migration name required%NC%
        echo Usage: probenplaner.bat migrate:create ^<name^>
        exit /b 1
    )
    call :check_docker || exit /b 1
    call :check_container || exit /b 1
    echo %GREEN%Creating migration: %2%NC%
    docker exec %CONTAINER% php /var/www/html/database/cli-migrate.php create "%2"
    exit /b
)

call :print_help 