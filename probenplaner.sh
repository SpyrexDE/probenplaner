#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color
BLUE='\033[0;34m'

# Container name
CONTAINER="probenplaner-web-1"

# Helper functions
print_help() {
    echo -e "${BLUE}Probenplaner Development Helper${NC}"
    echo
    echo "Usage: ./probenplaner.sh [command]"
    echo
    echo "Docker Commands:"
    echo "  up              Start all containers"
    echo "  down            Stop all containers"
    echo "  restart         Restart all containers"
    echo "  logs [filter]   View container logs (filter: error, warn, info, debug)"
    echo "  ps              Show container status"
    echo "  shell           Open shell in web container"
    echo
    echo "Migration Commands:"
    echo "  migrate:status  Show migration status"
    echo "  migrate:up      Run all pending migrations"
    echo "  migrate:create [name]  Create a new migration"
    echo
    echo "Examples:"
    echo "  ./probenplaner.sh up"
    echo "  ./probenplaner.sh logs error"
    echo "  ./probenplaner.sh migrate:create add_user_field"
}

# Check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        echo -e "${RED}Error: Docker is not running${NC}"
        exit 1
    fi
}

# Check if container is running
check_container() {
    if ! docker ps | grep -q $CONTAINER; then
        echo -e "${RED}Error: Container $CONTAINER is not running${NC}"
        echo -e "Try starting it with: ${GREEN}./probenplaner.sh up${NC}"
        exit 1
    fi
}

# Main script
case "$1" in
    "up")
        check_docker
        echo -e "${GREEN}Starting containers...${NC}"
        docker compose up -d
        echo -e "${GREEN}Containers are starting. View logs with: ./probenplaner.sh logs${NC}"
        ;;
        
    "down")
        check_docker
        echo -e "${YELLOW}Stopping containers...${NC}"
        docker compose down
        ;;
        
    "restart")
        check_docker
        echo -e "${YELLOW}Restarting containers...${NC}"
        docker compose down
        docker compose up -d
        echo -e "${GREEN}Containers restarted${NC}"
        ;;
        
    "logs")
        check_docker
        check_container
        if [ "$2" = "error" ]; then
            docker logs $CONTAINER 2>&1 | grep -i "error"
        elif [ "$2" = "warn" ]; then
            docker logs $CONTAINER 2>&1 | grep -i -E "warn|warning"
        elif [ "$2" = "info" ]; then
            docker logs $CONTAINER 2>&1 | grep -i "info"
        elif [ "$2" = "debug" ]; then
            docker logs $CONTAINER 2>&1 | grep -i "debug"
        else
            docker logs $CONTAINER
        fi
        ;;
        
    "ps")
        check_docker
        docker compose ps
        ;;
        
    "shell")
        check_docker
        check_container
        echo -e "${GREEN}Opening shell in $CONTAINER...${NC}"
        docker exec -it $CONTAINER /bin/bash
        ;;
        
    "migrate:status")
        check_docker
        check_container
        echo -e "${BLUE}Migration Status:${NC}"
        docker exec $CONTAINER php /var/www/html/database/cli-migrate.php status
        ;;
        
    "migrate:up")
        check_docker
        check_container
        echo -e "${YELLOW}Running migrations...${NC}"
        docker exec $CONTAINER php /var/www/html/database/cli-migrate.php up
        ;;
        
    "migrate:create")
        if [ -z "$2" ]; then
            echo -e "${RED}Error: Migration name required${NC}"
            echo "Usage: ./probenplaner.sh migrate:create <name>"
            exit 1
        fi
        check_docker
        check_container
        echo -e "${GREEN}Creating migration: $2${NC}"
        docker exec $CONTAINER php /var/www/html/database/cli-migrate.php create "$2"
        ;;
        
    *)
        print_help
        ;;
esac 