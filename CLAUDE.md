# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application with Livewire for building a tourism accommodation management system. The application integrates with the Apidae API to fetch and manage accommodation data from French tourism databases.

## Key Technologies

- **Backend**: Laravel 12 with PHP 8.2+
- **Frontend**: Livewire + Flux UI components
- **Styling**: Tailwind CSS 4.0 with DaisyUI
- **Database**: SQLite (development), configurable for production
- **Testing**: Pest PHP testing framework
- **Build Tool**: Vite
- **External API**: Apidae Tourism API integration

## Common Commands

### Development
```bash
# Start development server with queue worker and asset compilation
composer run dev

# Alternative: Start individual services
php artisan serve                    # Start Laravel server
php artisan queue:listen --tries=1  # Start queue worker
npm run dev                         # Start Vite for asset compilation
```

### Database
```bash
php artisan migrate                 # Run migrations
php artisan db:seed                 # Run seeders
php artisan migrate:fresh --seed    # Fresh migration with seeding
```

### Testing
```bash
composer run test                   # Run full test suite
php artisan test                    # Alternative test command
vendor/bin/pest                     # Run Pest tests directly
vendor/bin/pest --filter=TestName   # Run specific test
```

### Code Quality
```bash
vendor/bin/pint                     # Format code using Laravel Pint
```

### Asset Management
```bash
npm run build                       # Build for production
npm run dev                         # Development build with watching
```

### Apidae API Integration
```bash
php artisan apidae:fetch            # Fetch accommodations from API (150 default)
php artisan apidae:fetch --test     # Use test data instead of API
php artisan apidae:fetch --limit=50 # Limit number of accommodations
php artisan apidae:fetch --simple   # Simple query without criteria
```

## Application Architecture

### Core Models
- **Accommodation**: Main model representing tourism accommodations with Apidae integration
  - Fields: apidae_id, name, city, email, phone, website, description, type, status
  - Scopes: active(), pending()
  - Location: `app/Models/Accommodation.php`

### Livewire Components
- **AccommodationsList**: Main component for displaying and filtering accommodations
  - Filters: search, status, city, type, contact information
  - Pagination: 100 items per page
  - Real-time statistics and city rankings
  - Location: `app/Livewire/AccommodationsList.php`

### Key Features
- **Apidae API Integration**: Fetches accommodation data from French tourism API
- **Advanced Filtering**: Multiple filter options for accommodations
- **User Authentication**: Laravel Breeze-style authentication with Livewire
- **Dashboard**: Statistics and management interface
- **Settings**: User profile, password, appearance management

### API Integration
The application integrates with the Apidae API for French tourism data:
- **Command**: `FetchApidaeData` in `app/Console/Commands/`
- **Configuration**: Requires APIDAE_API_KEY, APIDAE_PROJECT_ID, APIDAE_SELECTION_ID in .env
- **Data Processing**: Handles accommodation data parsing and contact information extraction

### Database Schema
- **Users**: Standard Laravel authentication
- **Accommodations**: Tourism accommodations with Apidae integration
- **Cache/Queue**: Standard Laravel infrastructure tables

### Routes Structure
- **Authentication**: Standard Laravel auth routes
- **Dashboard**: Main application interface
- **Accommodations**: List and management interface
- **Settings**: User preferences and profile management

## Environment Configuration

Copy `.env.example` to `.env` and configure:
- Database connection (SQLite by default)
- Apidae API credentials for tourism data integration
- Mail settings for user notifications
- Application settings (name, URL, etc.)

## UI Framework

The application uses Livewire Flux for UI components with Tailwind CSS styling:
- Components located in `resources/views/components/`
- Flux components in `resources/views/flux/`
- Livewire views in `resources/views/livewire/`

## Testing Strategy

Tests are organized using Pest PHP:
- **Feature Tests**: Authentication, dashboard, settings functionality
- **Unit Tests**: Model logic and business rules
- **Database**: In-memory SQLite for testing
- **Configuration**: `phpunit.xml` with proper test environment setup