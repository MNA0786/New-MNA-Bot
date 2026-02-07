#!/bin/bash
# setup.sh - Initial setup script

echo "🎬 Setting up Entertainment Tadka Bot..."

# Create directories
mkdir -p cache backups logs

# Create files with proper permissions
touch movies.csv users.json bot_stats.json error.log
chmod 666 movies.csv users.json bot_stats.json error.log
chmod 777 cache backups logs

# Create CSV header if file is empty
if [ ! -s movies.csv ]; then
    echo "movie_name,message_id,channel_id" > movies.csv
    echo "✅ Created movies.csv with header"
fi

# Copy environment example
if [ ! -f .env ]; then
    cp .env.example .env
    echo "⚠️  Please edit .env file with your configuration"
fi

# Set permissions
chmod +x setup.sh

echo "✅ Setup complete!"
echo "📝 Next steps:"
echo "1. Edit .env file with your bot token"
echo "2. Run: docker-compose up -d  (for Docker)"
echo "3. Or deploy to Render.com"
echo "4. Set webhook: https://your-domain.com/?setwebhook=1"