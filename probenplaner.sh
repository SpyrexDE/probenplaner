#!/bin/bash
#
# Probenplaner CLI Tool
# 
# A professional command-line interface for managing the Probenplaner application
# across development, test, and production environments.
#
# Author: Generated for Probenplaner project
# Usage: ./probenplaner.sh [command] [options]
#

set -e  # Exit on any error

# Trap to handle script interruption
trap 'echo -e "\n${YELLOW}⚠️  Script interrupted${NC}"; exit 130' INT

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color
BLUE='\033[0;34m'

# Project name for container detection
PROJECT_NAME="probenplaner"

# Check if we're in a git repository
check_git() {
    if ! git rev-parse --git-dir > /dev/null 2>&1; then
        echo -e "${YELLOW}Warning: Not in a git repository. Version will be 'N/A'${NC}"
        return 1
    fi
    return 0
}

# Get git version for builds
get_git_version() {
    local git_version
    if check_git; then
        git_version=$(git describe --tags --always 2>/dev/null || echo "N/A")
    else
        git_version="N/A"
    fi
    echo "$git_version"
}

# Set git version environment variable
set_git_version() {
    export GIT_VERSION=$(get_git_version)
    echo -e "${BLUE}📦 Version: $GIT_VERSION${NC}"
}

# Get web container name dynamically
get_web_container() {
    docker compose ps --services --filter "status=running" | grep web | head -1 | xargs -I {} docker compose ps {} --format "table {{.Name}}" | tail -n +2 | head -1
}

# Check if web container is running
check_web_container() {
    local container
    container=$(get_web_container)
    if [ -z "$container" ]; then
        echo -e "${RED}❌ Error: Web container is not running${NC}"
        echo -e "Try starting it with: ${GREEN}./probenplaner.sh dev${NC}"
        return 1
    fi
    echo "$container"
}

# Helper functions
print_help() {
    echo -e "${BLUE}╭─────────────────────────────────────────╮${NC}"
    echo -e "${BLUE}│        Probenplaner CLI Tool            │${NC}" 
    echo -e "${BLUE}╰─────────────────────────────────────────╯${NC}"
    echo
    echo -e "${GREEN}Usage:${NC} ./probenplaner.sh [command] [options]"
    echo
    echo -e "${YELLOW}Environment Management:${NC}"
    echo "  dev             🚀 Start development environment"
    echo "  prod            🏭 Deploy production environment"
    echo "  test            🧪 Start test environment"
    echo "  down            ⬇️  Stop all containers"
    echo "  restart         🔄 Restart current environment"
    echo "  clean           🧹 Remove all containers and volumes"
    echo
    echo -e "${YELLOW}Development Tools:${NC}"
    echo "  build           🔨 Rebuild containers"
    echo "  status          📊 Show system status"
    echo "  logs [filter]   📋 View logs (filters: error, warn, info, debug, -f)"
    echo "  shell           💻 Open shell in web container"
    echo "  ps              📝 Show container status"
    echo
    echo -e "${YELLOW}Database:${NC}"
    echo "  migrate:status  📈 Show migration status"
    echo "  migrate:up      ⬆️  Run pending migrations"
    echo "  migrate:create  ➕ Create new migration"
    echo
    echo -e "${YELLOW}Information:${NC}"
    echo "  version         🏷️  Show current version"
    echo "  help            ❓ Show this help"
    echo
    echo -e "${GREEN}Examples:${NC}"
    echo "  ./probenplaner.sh dev"
    echo "  ./probenplaner.sh prod"
    echo "  ./probenplaner.sh logs -f"
    echo "  ./probenplaner.sh migrate:create add_user_preferences"
}

# Check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo -e "${RED}❌ Error: Docker is not running${NC}"
        echo -e "Please start Docker Desktop and try again."
        exit 1
    fi
}

# Print status information
print_status() {
    echo -e "${BLUE}📊 System Status${NC}"
    echo -e "${BLUE}─────────────────${NC}"
    
    # Git version
    local git_ver=$(get_git_version)
    echo -e "Version: ${GREEN}$git_ver${NC}"
    
    # Docker status
    if docker info > /dev/null 2>&1; then
        echo -e "Docker: ${GREEN}✅ Running${NC}"
    else
        echo -e "Docker: ${RED}❌ Not running${NC}"
        return 1
    fi
    
    # Container status
    local web_container=$(get_web_container)
    if [ -n "$web_container" ]; then
        echo -e "Web Container: ${GREEN}✅ Running ($web_container)${NC}"
        
        # Database connectivity test
        if docker exec "$web_container" mysqladmin ping -h db -u root --password="$MYSQL_ROOT_PASSWORD" > /dev/null 2>&1; then
            echo -e "Database: ${GREEN}✅ Connected${NC}"
        else
            echo -e "Database: ${YELLOW}⚠️  Connection issue${NC}"
        fi
    else
        echo -e "Web Container: ${RED}❌ Not running${NC}"
    fi
    echo
}

# Main script
case "$1" in
    "dev"|"up")
        check_docker
        set_git_version
        echo -e "${GREEN}🚀 Starting development environment...${NC}"
        docker compose up -d
        echo -e "${GREEN}✅ Development environment is ready!${NC}"
        echo -e "   🌐 Web: ${BLUE}http://localhost:8080${NC}"
        echo -e "   📊 Status: ${BLUE}./probenplaner.sh status${NC}"
        echo -e "   📋 Logs: ${BLUE}./probenplaner.sh logs${NC}"
        ;;
        
    "prod")
        check_docker
        set_git_version
        echo -e "${GREEN}🏭 Building and deploying production environment...${NC}"
        echo -e "${YELLOW}   Stopping existing production containers...${NC}"
        docker compose -f docker-compose.prod.yml down 2>/dev/null || true
        echo -e "${YELLOW}   Building production containers...${NC}"
        if docker compose -f docker-compose.prod.yml build; then
            echo -e "${YELLOW}   Starting production environment...${NC}"
            docker compose -f docker-compose.prod.yml up -d
            echo -e "${GREEN}✅ Production environment deployed successfully!${NC}"
            echo -e "   Version: ${BLUE}$GIT_VERSION${NC}"
        else
            echo -e "${RED}❌ Production build failed${NC}"
            exit 1
        fi
        ;;
        
    "test")
        check_docker
        set_git_version
        echo -e "${GREEN}🧪 Building and starting test environment...${NC}"
        echo -e "${YELLOW}   Stopping existing test containers...${NC}"
        docker compose -f docker-compose.test.yml down 2>/dev/null || true
        echo -e "${YELLOW}   Building test containers...${NC}"
        if docker compose -f docker-compose.test.yml build; then
            echo -e "${YELLOW}   Starting test environment...${NC}"
            docker compose -f docker-compose.test.yml up -d
            echo -e "${GREEN}✅ Test environment is ready!${NC}"
            echo -e "   Version: ${BLUE}$GIT_VERSION${NC}"
        else
            echo -e "${RED}❌ Test build failed${NC}"
            exit 1
        fi
        ;;
        
    "build")
        check_docker
        set_git_version
        echo -e "${YELLOW}🔨 Rebuilding development containers...${NC}"
        if docker compose build; then
            echo -e "${GREEN}✅ Build completed successfully!${NC}"
            echo -e "   Version: ${BLUE}$GIT_VERSION${NC}"
        else
            echo -e "${RED}❌ Build failed${NC}"
            exit 1
        fi
        ;;
        
    "down")
        check_docker
        echo -e "${YELLOW}⬇️  Stopping containers...${NC}"
        docker compose down
        docker compose -f docker-compose.prod.yml down 2>/dev/null || true
        docker compose -f docker-compose.test.yml down 2>/dev/null || true
        echo -e "${GREEN}✅ All containers stopped${NC}"
        ;;
        
    "clean")
        check_docker
        echo -e "${YELLOW}🧹 Cleaning up containers and volumes...${NC}"
        docker compose down -v --remove-orphans 2>/dev/null || true
        docker compose -f docker-compose.prod.yml down -v --remove-orphans 2>/dev/null || true
        docker compose -f docker-compose.test.yml down -v --remove-orphans 2>/dev/null || true
        docker system prune -f > /dev/null
        echo -e "${GREEN}✅ Cleanup completed${NC}"
        ;;
        
    "restart")
        check_docker
        set_git_version
        echo -e "${YELLOW}🔄 Restarting development environment...${NC}"
        docker compose down
        docker compose up -d
        echo -e "${GREEN}✅ Environment restarted with version: $GIT_VERSION${NC}"
        ;;
        
    "status")
        print_status
        ;;
        
    "version")
        git_ver=$(get_git_version)
        echo -e "${BLUE}🏷️  Current Version: ${GREEN}$git_ver${NC}"
        ;;
        
    "logs")
        check_docker
        container=$(check_web_container) || exit 1
        
        echo -e "${BLUE}📋 Container Logs${NC}"
        if [ "$2" = "-f" ] || [ "$2" = "--follow" ]; then
            echo -e "${YELLOW}Following logs (Ctrl+C to stop)...${NC}"
            docker logs -f "$container"
        elif [ "$2" = "error" ]; then
            echo -e "${YELLOW}Filtering for errors...${NC}"
            docker logs "$container" 2>&1 | grep -i "error" | tail -50
        elif [ "$2" = "warn" ]; then
            echo -e "${YELLOW}Filtering for warnings...${NC}"
            docker logs "$container" 2>&1 | grep -i -E "warn|warning" | tail -50
        elif [ "$2" = "info" ]; then
            echo -e "${YELLOW}Filtering for info...${NC}"
            docker logs "$container" 2>&1 | grep -i "info" | tail -50
        elif [ "$2" = "debug" ]; then
            echo -e "${YELLOW}Filtering for debug...${NC}"
            docker logs "$container" 2>&1 | grep -i "debug" | tail -50
        else
            echo -e "${YELLOW}Showing recent logs...${NC}"
            docker logs --tail 100 "$container"
        fi
        ;;
        
    "ps")
        check_docker
        echo -e "${BLUE}📝 Container Status${NC}"
        docker compose ps
        ;;
        
    "shell")
        check_docker
        container=$(check_web_container) || exit 1
        echo -e "${GREEN}💻 Opening shell in $container...${NC}"
        docker exec -it "$container" /bin/bash
        ;;
        
    "migrate:status")
        check_docker
        container=$(check_web_container) || exit 1
        echo -e "${BLUE}📈 Migration Status${NC}"
        docker exec "$container" php /var/www/html/database/cli-migrate.php status
        ;;
        
    "migrate:up")
        check_docker
        container=$(check_web_container) || exit 1
        echo -e "${YELLOW}⬆️  Running pending migrations...${NC}"
        if docker exec "$container" php /var/www/html/database/cli-migrate.php up; then
            echo -e "${GREEN}✅ Migrations completed successfully${NC}"
        else
            echo -e "${RED}❌ Migration failed${NC}"
            exit 1
        fi
        ;;
        
    "migrate:create")
        if [ -z "$2" ]; then
            echo -e "${RED}❌ Error: Migration name required${NC}"
            echo -e "Usage: ${GREEN}./probenplaner.sh migrate:create <name>${NC}"
            echo -e "Example: ${BLUE}./probenplaner.sh migrate:create add_user_preferences${NC}"
            exit 1
        fi
        check_docker
        container=$(check_web_container) || exit 1
        echo -e "${GREEN}➕ Creating migration: ${BLUE}$2${NC}"
        if docker exec "$container" php /var/www/html/database/cli-migrate.php create "$2"; then
            echo -e "${GREEN}✅ Migration created successfully${NC}"
        else
            echo -e "${RED}❌ Failed to create migration${NC}"
            exit 1
        fi
        ;;
        
    "help"|"--help"|"-h")
        print_help
        ;;
        
    "")
        echo -e "${RED}❌ Error: No command provided${NC}"
        echo -e "Use ${GREEN}./probenplaner.sh help${NC} to see available commands"
        exit 1
        ;;
        
    *)
        echo -e "${RED}❌ Error: Unknown command '$1'${NC}"
        echo -e "Use ${GREEN}./probenplaner.sh help${NC} to see available commands"
        exit 1
        ;;
esac 