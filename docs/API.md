# API Documentation

This document provides a technical reference for the Juicebox RESTful API.

## Base URL
`http://localhost:8000/api`

## Authentication
Most endpoints require a Bearer token. Obtain this token via the `/api/login` endpoint.
**Header:** `Authorization: Bearer {your_token}`

---

## 🚀 Quick Start Examples

### 1. Register a new account
```bash
curl -X POST http://localhost:8000/api/register \
     -H "Content-Type: application/json" \
     -d '{"name": "John Doe", "email": "john@example.com", "password": "password123", "password_confirmation": "password123"}'
```

### 2. Login to get your Token
```bash
curl -X POST http://localhost:8000/api/login \
     -H "Content-Type: application/json" \
     -d '{"email": "john@example.com", "password": "password123"}'
```
*Copy the `token` from the response for the next steps.*

### 3. Create a Post (Authenticated)
```bash
curl -X POST http://localhost:8000/api/posts \
     -H "Authorization: Bearer YOUR_TOKEN_HERE" \
     -H "Content-Type: application/json" \
     -d '{"title": "My First Post", "content": "Hello world, this is my first post!"}'
```

### 4. Get Weather Data (Public)
```bash
curl -X GET http://localhost:8000/api/weather
```

---

## Endpoints Summary

### 🔐 Authentication & Users
| Method | Endpoint | Description | Auth |
| :--- | :--- | :--- | :---: |
| `POST` | `/api/register` | Register a new user account | No |
| `POST` | `/api/login` | Authenticate and receive a token | No |
| `POST` | `/api/logout` | Revoke current session token | Yes |
| `GET` | `/api/users` | List all users (Paginated) | Yes |
| `GET` | `/api/users/{id}` | Get profile details for a specific user | Yes |

### 📝 Posts Management
| Method | Endpoint | Description | Auth |
| :--- | :--- | :--- | :---: |
| `GET` | `/api/posts` | List all posts (Paginated) | No |
| `GET` | `/api/posts/{id}` | Get details of a specific post | No |
| `POST` | `/api/posts` | Create a new post | Yes |
| `PATCH` | `/api/posts/{id}` | Update an existing post (Owner only) | Yes |
| `DELETE` | `/api/posts/{id}` | Delete a post (Owner only) | Yes |

### ☀️ Weather Service
| Method | Endpoint | Description | Auth |
| :--- | :--- | :--- | :---: |
| `GET` | `/api/weather` | Current weather data for Perth, AU | No |

---

## Detailed Endpoint Reference

### 1. User Registration
`POST /api/register`

**Request Body:**
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```
**Success Response:** `201 Created`

### 2. User Login
`POST /api/login`

**Request Body:**
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```
**Success Response:** `200 OK`
```json
{
    "token": "1|abc123def456...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    }
}
```

### 3. User Logout
`POST /api/logout`

**Success Response:** `204 No Content`

### 4. List Users
`GET /api/users?page=1&per_page=15`

**Success Response:** `200 OK`
```json
{
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "created_at": "2026-11-02T10:00:00Z"
        }
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "per_page": 15, "total": 10 }
}
```

### 5. Get User Profile
`GET /api/users/{id}`

**Success Response:** `200 OK`
```json
{
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "created_at": "2026-11-02T10:00:00Z"
    }
}
```

### 6. List Posts
`GET /api/posts?page=1&per_page=15`

**Success Response:** `200 OK`
```json
{
    "data": [
        {
            "id": 1,
            "title": "Hello World",
            "content": "My first post content",
            "user": { "name": "John Doe" },
            "created_at": "2026-11-02T10:00:00Z"
        }
    ],
    "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
    "meta": { "current_page": 1, "per_page": 15, "total": 50 }
}
```

### 7. Get Specific Post
`GET /api/posts/{id}`

**Success Response:** `200 OK`
```json
{
    "data": {
        "id": 1,
        "title": "Hello World",
        "content": "My first post content",
        "user": { "name": "John Doe" },
        "created_at": "2026-11-02T10:00:00Z"
    }
}
```

### 8. Create Post
`POST /api/posts`

**Request Body:**
```json
{
    "title": "New Post Title",
    "content": "Detailed content of the post..."
}
```
**Success Response:** `201 Created`

### 9. Update Post
`PATCH /api/posts/{id}`

**Request Body:**
```json
{
    "title": "Updated Title",
    "content": "Updated content..."
}
```
**Success Response:** `200 OK`

### 10. Delete Post
`DELETE /api/posts/{id}`

**Success Response:** `204 No Content`

### 11. Weather Data
`GET /api/weather`

**Success Response:** `200 OK`
```json
{
    "data": {
        "city": "Perth",
        "region": "Western Australia",
        "country": "Australia",
        "latitude": -31.9333,
        "longitude": 115.8333,
        "localtime": "2026-11-02 18:00",
        "temperature": 18.4,
        "temperature_unit": "°C",
        "feels_like": 15.5,
        "humidity": 65,
        "description": "Sunny",
        "cloud_cover": 6,
        "wind_speed": 17.6,
        "wind_speed_unit": "km/h",
        "wind_direction": "W",
        "wind_degree": 264,
        "pressure_mb": 1018,
        "precipitation_mm": 0,
        "visibility_km": 10,
        "uv_index": 0.3,
        "icon": "https://cdn.weatherapi.com/weather/64x64/day/113.png",
        "fetched_at": "2026-11-02T10:00:00Z"
    }
}
```

---

## Error Handling

The API uses standard HTTP response codes to indicate the success or failure of an API request.

| Code | Meaning | Description |
| :--- | :--- | :--- |
| `200` | OK | Request succeeded. |
| `201` | Created | Resource created successfully. |
| `204` | No Content | Resource deleted successfully. |
| `401` | Unauthorized | Invalid or missing authentication token. |
| `403` | Forbidden | Authenticated, but you do not own this resource. |
| `422` | Unprocessable Entity | Validation failed. See `errors` object in response. |
| `503` | Service Unavailable | External Weather API is down or key is invalid. |

**Validation Error Example (`422`):**
```json
{
    "message": "The given data was invalid.",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```
