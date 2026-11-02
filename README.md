# Juicebox Laravel API

A RESTful API built with Laravel 13, featuring Sanctum authentication, post management, weather integration, queued jobs, and comprehensive testing.

> 📖 **Interactive API Documentation** available at [`/docs/api`](http://localhost:8000/docs/api) when running locally — powered by [Scramble](https://scramble.dedoc.co/).

## Requirements

- PHP 8.3+
- MySQL 8.0+
- Composer
- A free API key from [WeatherAPI.com](https://www.weatherapi.com/)

## Setup Instructions

### 1. Clone & Install Dependencies

```bash
git clone <repository-url>
cd juicebox-laravel-test
composer install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` and configure:

```dotenv
# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=juicebox_laravel
DB_USERNAME=root
DB_PASSWORD=

# Queue (database driver is preconfigured)
QUEUE_CONNECTION=database

# Weather API (WeatherAPI.com)
WEATHER_API_KEY=your_weatherapi_key_here
```

### 3. Database Setup

Create the MySQL database, then run migrations and seeders:

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS juicebox_laravel;"
php artisan migrate --seed
```

The seeder creates:
- 1 test user (`test@example.com` / `password`) with 5 posts
- 9 additional users, each with 5 posts

### 4. Start the Application

```bash
php artisan serve
```

The API will be available at `http://localhost:8000/api`.

---

## 📚 Documentation Index

We provide comprehensive documentation in three formats to suit different needs:

- **Interactive Explorer**: Available at [`/docs/api`](http://localhost:8000/docs/api) for a live, "try-it-out" experience (powered by Scramble). For more details on how this works, [see the API Documentation](#api-documentation) section below.
- **Technical Reference**: See the [Detailed API Reference](docs/API.md) for a full mapping of endpoints, request bodies, and error codes.
- **Postman Collection**: Import the [Postman Collection JSON](docs/Postman_Collection.json) to quickly test the API with pre-configured requests.

---

## Weather API Setup (WeatherAPI.com)

This application connects to [WeatherAPI.com](https://www.weatherapi.com/) to fetch real-time weather data for **Perth, Australia** (1,000,000 free calls/month, instant activation).

### Getting an API Key

1. Sign up for free at [https://www.weatherapi.com/signup.aspx](https://www.weatherapi.com/signup.aspx) (no credit card required).
2. Copy your API key from the dashboard.
3. Add it to your `.env` file:

```dotenv
WEATHER_API_KEY=your_weatherapi_key_here
```

### How It Works

- The `/api/weather` endpoint returns normalized weather data for Perth, AU
- Responses are **cached for 15 minutes** to minimise external API calls
- A **background job** (`FetchWeatherData`) runs hourly via the scheduler to keep the cache warm
- If the external API fails or the key is invalid, a `503` error is returned gracefully

---

## Queue Worker Setup

The application uses a database queue driver for background jobs.

### Start the Queue Worker

```bash
php artisan queue:work
```

For production, use a process manager like Supervisor. For development:

```bash
php artisan queue:work --tries=3
```

### Queued Jobs

| Job | Trigger | Description |
|-----|---------|-------------|
| `SendWelcomeEmail` | User registration (`POST /api/register`) | Sends a welcome email to the new user |
| `FetchWeatherData` | Hourly schedule | Refreshes cached weather data from WeatherAPI.com |

### Manually Dispatch Welcome Email

To test the welcome email job manually:

```bash
php artisan app:send-welcome-email {user_id}
```

Example:

```bash
php artisan app:send-welcome-email 1
```

> **Note:** The mail driver defaults to `log` in development. Check `storage/logs/laravel.log` to see sent emails.

### Running the Scheduler

For the hourly weather fetch job to run automatically:

```bash
php artisan schedule:work
```

Or add a cron entry for production:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

---

## API Documentation

This project uses [Scramble](https://scramble.dedoc.co/) to **auto-generate interactive OpenAPI documentation** directly from the codebase — no manual annotations required.

### Viewing the Docs

Start the application and visit:

- **Interactive UI:** [`http://localhost:8000/docs/api`](http://localhost:8000/docs/api)
- **OpenAPI JSON spec:** [`http://localhost:8000/docs/api.json`](http://localhost:8000/docs/api.json)

The docs are auto-generated from your controllers, Form Requests, and API Resources. Any code changes are reflected immediately.

### Authentication

The API uses **Laravel Sanctum** token-based authentication. To access protected endpoints:

1. Register or login to receive a Bearer token
2. Include the token in the `Authorization` header:

```
Authorization: Bearer your-token-here
```

All requests should include:

```
Accept: application/json
Content-Type: application/json
```

---

### Endpoints Overview

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| `POST` | `/api/register` | Register a new user | No |
| `POST` | `/api/login` | Login and get token | No |
| `POST` | `/api/logout` | Revoke current token | 🔒 Yes |
| `GET` | `/api/users` | List users (paginated: `?page=1&per_page=15`) | 🔒 Yes |
| `GET` | `/api/users/{id}` | Get user profile | 🔒 Yes |
| `GET` | `/api/posts` | List posts (paginated: `?page=1&per_page=15`) | No |
| `GET` | `/api/posts/{id}` | Get a specific post | No |
| `POST` | `/api/posts` | Create a post | 🔒 Yes |
| `PATCH` | `/api/posts/{id}` | Update own post | 🔒 Yes |
| `DELETE` | `/api/posts/{id}` | Delete own post | 🔒 Yes |
| `GET` | `/api/weather` | Current Perth weather | No |

> For full request/response schemas, validation rules, and a "Try it" playground, see the [interactive docs](http://localhost:8000/docs/api).

---

## Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/PostApiTest.php
php artisan test tests/Feature/AuthApiTest.php
php artisan test tests/Feature/WeatherApiTest.php

# Run with filter
php artisan test --filter="can list paginated posts"
```

---

## Project Structure

```
app/
├── Console/Commands/
│   └── SendWelcomeEmailCommand.php    # Manual welcome email dispatch
├── Http/
│   ├── Controllers/Api/
│   │   ├── AuthController.php         # Register, login, logout
│   │   ├── PostController.php         # CRUD for posts
│   │   ├── UserController.php         # User profile
│   │   └── WeatherController.php      # Weather data
│   ├── Requests/
│   │   ├── LoginRequest.php
│   │   ├── PaginationRequest.php
│   │   ├── RegisterRequest.php
│   │   ├── StorePostRequest.php
│   │   └── UpdatePostRequest.php
│   └── Resources/
│       ├── PostResource.php
│       └── UserResource.php
├── Jobs/
│   ├── FetchWeatherData.php           # Hourly weather cache refresh
│   └── SendWelcomeEmail.php           # Queued welcome email
├── Mail/
│   └── WelcomeEmail.php
├── Models/
│   ├── Post.php
│   └── User.php
├── Policies/
│   └── PostPolicy.php                 # Ownership authorization (update/delete)
└── Services/
    └── WeatherService.php             # WeatherAPI.com integration + caching
```

---

## Security Highlights

- **Stateless Sanctum Tokens**: Hashed with SHA-256 in the database; tokens revoked upon logout with configurable expiration (`SANCTUM_TOKEN_EXPIRATION`).
- **Policy-Based Authorization**: `PostPolicy` prevents Insecure Direct Object References (IDOR) by restricting `update` and `delete` actions strictly to post owners.
- **Rate Limiting**: Globally configured `RateLimiter::for('api', ...)` applying 60 requests/minute per authenticated user (or per IP for guests), returning standard `X-RateLimit-*` headers.
- **Mass Assignment Protection**: Models use strict `#[Fillable]` attributes, and foreign keys (`user_id`) are always derived from the authenticated session (`$request->user()->posts()->create(...)`).
- **Data Exposure Prevention**: Sensitive fields (`password`, `remember_token`) are excluded at both the model level (`#[Hidden]`) and through Eloquent API Resources (`UserResource`, `PostResource`).
- **External API & SSRF Resilience**: WeatherAPI.com endpoint URLs are hardcoded in `WeatherService`, with strict 10s timeouts and 3x retries. Upstream failures are caught, logged, and isolated behind user-friendly HTTP 503 responses.

---

## Key Design Decisions

- **Sanctum Tokens**: Stateless API token authentication for simplicity and security
- **Eloquent API Resources**: Consistent JSON response formatting with relationship loading
- **Form Requests**: Validation and authorization separated from controllers
- **Database Queue**: Jobs persisted in MySQL for reliability without Redis dependency
- **15-minute Cache**: Weather data cached to minimise external API calls while staying current
- **Hourly Background Job**: Keeps cache warm so users get instant responses
- **Ownership Authorization**: Post updates/deletes restricted to the post owner via Form Request authorization and controller checks
