# Probenplaner

## Development Cheat Sheet

Quick reference for development commands (use `probenplaner.sh` on Unix/Mac or `probenplaner.bat` on Windows):

```bash
# Docker
./probenplaner.sh up        # Start containers
./probenplaner.sh down      # Stop containers
./probenplaner.sh restart   # Restart containers
./probenplaner.sh ps        # Container status
./probenplaner.sh shell     # Open container shell

# Logs
./probenplaner.sh logs          # All logs
./probenplaner.sh logs error    # Only errors
./probenplaner.sh logs warn     # Only warnings
./probenplaner.sh logs info     # Only info
./probenplaner.sh logs debug    # Only debug

# Database Migrations
./probenplaner.sh migrate:status         # Check migration status
./probenplaner.sh migrate:up             # Run pending migrations
./probenplaner.sh migrate:create <name>  # Create new migration
```


