#!/bin/bash
# Health check script for Docker

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Running health check...${NC}"

# Check if Apache is running
if ! pgrep apache2 > /dev/null; then
    echo -e "${RED}Apache is not running${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Apache is running${NC}"

# Check if index.php exists
if [ ! -f /var/www/html/index.php ]; then
    echo -e "${RED}index.php not found${NC}"
    exit 1
fi
echo -e "${GREEN}✓ index.php exists${NC}"

# Check if we can connect to localhost
if ! curl -s -f http://localhost/ > /dev/null; then
    echo -e "${RED}Cannot connect to localhost${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Localhost connection OK${NC}"

# Check critical files permissions
FILES=("error.log" "movies.csv" "users.json" "bot_stats.json" "requests.json")
for file in "${FILES[@]}"; do
    if [ -f "/var/www/html/$file" ]; then
        if [ ! -w "/var/www/html/$file" ]; then
            echo -e "${RED}$file is not writable${NC}"
            exit 1
        fi
    fi
done
echo -e "${GREEN}✓ File permissions OK${NC}"

# Check directories permissions
DIRS=("backups" "cache" "logs" "tmp" "sessions" "data")
for dir in "${DIRS[@]}"; do
    if [ ! -d "/var/www/html/$dir" ] || [ ! -w "/var/www/html/$dir" ]; then
        echo -e "${RED}$dir directory is not writable${NC}"
        exit 1
    fi
done
echo -e "${GREEN}✓ Directory permissions OK${NC}"

# Check cron is running
if ! pgrep cron > /dev/null; then
    echo -e "${YELLOW}⚠ Cron is not running (optional)${NC}"
else
    echo -e "${GREEN}✓ Cron is running${NC}"
fi

# Check supervisor is running
if ! pgrep supervisord > /dev/null; then
    echo -e "${RED}Supervisor is not running${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Supervisor is running${NC}"

echo -e "${GREEN}✅ All health checks passed!${NC}"
exit 0
