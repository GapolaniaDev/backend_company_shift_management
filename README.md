# Shift Management System - Backend

<p align="center">
  <img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo">
</p>

## Overview

This is the backend API for the Shift Management System, built with Laravel. The system allows businesses to manage employee work schedules, track clock-in/clock-out with location validation, and automatically generate shifts based on predefined configurations.

## Key Features

- **Role-based Access Control**: Three user roles (admin, supervisor, employee) with different permission levels
- **Employee Management**: Comprehensive employee data including personal and financial information
- **Shift Configuration**: Customizable shift types and employee-specific shift settings
- **Shift Generation**: Automatic shift creation based on templates and configurations
- **Clock In/Out System**: Location-aware time tracking with geofencing
- **API Documentation**: Complete Swagger/OpenAPI documentation
- **Secure Authentication**: JWT-based authentication with Laravel Passport

## System Architecture

### Models

- **User**: Authentication and role management
- **Employee**: Employee data with hierarchical supervisor relationships
- **ShiftType**: Shift templates with weekly schedules
- **ShiftConfiguration**: Employee-specific shift settings
- **Shift**: Individual work shifts with schedule and location data
- **PayPeriod**: Pay period definitions for payroll processing

### API Endpoints

The API is organized into the following categories:

- **Authentication**: Login, logout, token refresh
- **Employees**: Employee CRUD operations
- **ShiftTypes**: Shift template management
- **ShiftConfigurations**: Employee shift settings
- **Shifts**: Shift management including clock in/out functionality
- **ShiftGeneration**: Automated shift creation tools
- **Dashboard**: Data aggregation for reporting

## Technology Stack

- **Framework**: Laravel 10
- **Authentication**: Laravel Passport (OAuth2)
- **Documentation**: L5-Swagger / OpenAPI
- **Database**: MySQL
- **Infrastructure**: Docker, Nginx
- **Security**: SSL/TLS, CSRF protection

## Getting Started

### Prerequisites

- Docker and Docker Compose
- Composer
- PHP 8.2+

### Installation

1. Clone the repository:
   ```
   git clone <repository-url>
   cd shift_management_docker/backend
   ```

2. Install dependencies:
   ```
   composer install
   ```

3. Configure environment:
   ```
   cp env .env
   ```

4. Generate application key:
   ```
   php artisan key:generate
   ```

5. Generate Passport keys:
   ```
   php artisan passport:install
   ```

6. Run migrations and seeders:
   ```
   php artisan migrate
   php artisan db:seed
   ```

7. Start the Docker containers:
   ```
   docker-compose up -d
   ```

### API Documentation

Once the application is running, access the Swagger documentation at:

```
https://localhost/api/documentation
```

## Development

### Generating API Documentation

To regenerate the Swagger/OpenAPI documentation after making changes:

```
php artisan l5-swagger:generate
```

### Running Tests

```
php artisan test
```

## License

This project is licensed under the [MIT license](https://opensource.org/licenses/MIT).