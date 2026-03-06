#!/bin/bash
# Health check script for Docker

# Check if Apache is running
if ! pgrep apache2 > /dev/null; then
    echo "Apache is not running"
    exit 1
fi

# Check if index.php exists
if [ ! -f /var/www/html/index.php ]; then
    echo "index.php not found"
    exit 1
fi

# Check if we can connect to localhost
if ! curl -s -f http://localhost/ > /dev/null; then
    echo "Cannot connect to localhost"
    exit 1
fi

echo "Health check passed"
exit 0