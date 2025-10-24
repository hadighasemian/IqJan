# IqJan Bot Setup Guide

## Overview
This is a web service that handles Bale messenger bot with OpenRouter AI service integration.

## Architecture
- **Adapter Pattern**: Clean separation between messenger and AI service implementations
- **Service Layer**: Business logic separated into dedicated services
- **Repository Pattern**: Database operations through Eloquent models
- **Dependency Injection**: Proper IoC container usage

## Setup Instructions

### 1. Database Setup
```bash
# Run migrations
php artisan migrate

# Seed the database with AI services and models
php artisan db:seed
```

### 2. Configuration
The bot is configured with:
- **Bale Bot**: `@iq_jan_bot`
- **Token**: `1893304116:ARV2LdEbYxHJtWAYVLfZPwCMNfr-1PyTQyo`
- **OpenRouter API Key**: `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`
- **Default AI Model**: `openai/gpt-oss-20b:free`

### 3. Set Webhook URL
```bash
# Set webhook URL for your domain
php artisan webhook:setup https://iq-jan.salam-raya.ir/webhook/bale
```

### 4. Test the Bot
1. Send a message to `@iq_jan_bot` in Bale
2. The bot should respond with "الان جواب می دم" and then provide AI response

## API Endpoints

### Webhook Endpoints
- `POST /webhook/bale` - Handle incoming messages
- `POST /webhook/bale/set` - Set webhook URL
- `GET /webhook/bale/info` - Get bot information

### Health Check
- `GET /health` - Service health check

## Features

### Message Processing Flow
1. User sends message to bot
2. Bot shows "الان جواب می دم" (waiting message)
3. Message is saved to database with user/group info
4. Message is sent to OpenRouter AI service
5. AI response is received and saved
6. Waiting message is edited with AI response

### Database Schema
- **Users**: Store user information from messengers
- **Groups**: Store group information
- **Messages**: Store all messages and AI responses
- **AI Services**: Configure AI service providers
- **AI Models**: Configure available AI models
- **AI API Keys**: Manage API keys and usage tracking

### Usage Tracking
- Track API key usage statistics
- Monitor daily usage limits
- Log all AI interactions

## Error Handling
- Comprehensive logging for debugging
- Graceful error handling with user-friendly messages
- Database transaction rollback on failures

## Security
- Webhook signature verification (configurable)
- API key management
- Input validation and sanitization

## Monitoring
- Health check endpoint
- Usage statistics tracking
- Error logging with context
