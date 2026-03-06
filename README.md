# 🎬 Entertainment Tadka Bot

[![Version](https://img.shields.io/badge/version-4.0-blue.svg)](https://github.com/entertainment-tadka/bot)
[![PHP](https://img.shields.io/badge/PHP-8.1+-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

A powerful Telegram bot for movie searches across multiple channels with a complete movie request system, admin panel, and Hinglish support.

## 📋 Table of Contents
- [Features](#-features)
- [Quick Start](#-quick-start)
- [Installation](#-installation)
- [Configuration](#-configuration)
- [Commands](#-commands)
- [Admin Panel](#-admin-panel)
- [Deployment](#-deployment)
- [API Reference](#-api-reference)
- [Contributing](#-contributing)
- [License](#-license)

## ✨ Features

### 🎬 Core Features
- **Smart Movie Search** - Partial matching, relevance scoring
- **Multi-Channel Support** - 4 Public + 2 Private channels
- **CSV Database** - Lightweight, no SQL required
- **Caching System** - Fast response times

### 📋 Request System
- `/request MovieName` command
- Natural language ("pls add MovieName")
- Duplicate blocking (24h)
- Flood control with reputation bonus
- Auto-approve when movie added

### 👤 User Features
- `/myrequests` - Track request status
- User reputation system
- Daily login rewards
- Language selection (English/Hindi/Hinglish)
- Points and achievements

### 🧑‍💼 Admin Panel
- **Dashboard** - Complete bot statistics
- **Request Management** - Approve/Reject with reasons
- **User Management** - View all users
- **Movie Management** - Browse database
- **Broadcast** - Send messages to all users
- **Backup** - Manual/auto backups
- **Performance Metrics** - Response times, cache stats

### 🆕 New in v4.0
- ✅ **Send All Results Button** - Search results mein ek saath bhejein
- ✅ **Send All Copies Button** - Movie ki saari copies ek saath
- ✅ **Database Optimization** - Index-based searching
- ✅ **Caching without Redis** - File-based cache
- ✅ **Smart Auto-approve** - Keyword matching
- ✅ **Batch Operations** - Multiple movies ek saath
- ✅ **User Reputation** - Bonus for active users
- ✅ **Performance Monitoring** - Auto-cache clear
- ✅ **Commands with Buttons** - Har command ke saath inline buttons

## 🚀 Quick Start

```bash
# Clone repository
git clone https://github.com/entertainment-tadka/bot.git
cd bot

# Copy environment file
cp .env.example .env
# Edit .env with your bot token

# Start with Docker
docker-compose up -d

# Set webhook
curl "https://your-domain.com/?setup=1"