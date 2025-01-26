#!/bin/bash

# Check if the "queue_worker" argument is passed, and start the queue worker if so
if [ "$1" = "queue_worker" ]; then
    echo "Starting the Laravel Queue Worker..."
    php artisan queue:work --tries=3
    exit 0
fi

# Otherwise, just run the default command (PHP-FPM)
echo "Starting PHP-FPM..."
exec "$@"
