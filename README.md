# Limit Order Exchange Mini Engine

A lightweight limit order matching engine built with Laravel (backend) and Vue 3 (frontend). Users can place buy/sell orders, which are automatically matched and settled with real-time WebSocket notifications.

## Table of Contents

- [Installation & Requirements](#installation--requirements)
- [Environment Configuration](#environment-configuration)
- [API Endpoints](#api-endpoints)
- [Development & Testing](#development--testing)
- [Architecture Overview](#architecture-overview)

---

## Installation & Requirements

### Prerequisites

- **PHP 8.2+**
- **Composer**
- **Node.js 16+** and **npm**
- **SQLite** (default) or **MySQL**

### Backend Setup

1. **Clone and navigate to the project:**
   ```bash
   cd limit-order-exchange-mini-engine/backend
   ```

2. **Install PHP dependencies:**
   ```bash
   composer install
   ```

3. **Create environment file:**
   ```bash
   cp .env.example .env
   ```

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

5. **Run database migrations:**
   ```bash
   php artisan migrate
   ```

6. **Optionally seed sample data:**
   ```bash
   php artisan db:seed
   ```

### Frontend Setup

1. **Navigate to frontend directory:**
   ```bash
   cd ../frontend
   ```

2. **Install dependencies:**
   ```bash
   npm install
   ```

3. **Build for development:**
   ```bash
   npm run dev
   ```

### Quick Start (All-in-One)

From the project root:
```bash
cd backend && composer run-script dev
```

This command runs Laravel development server, queue listener, log viewer, and Vite frontend dev server concurrently.

---

## Environment Configuration

### Database Configuration

```env
DB_CONNECTION=sqlite
# or for MySQL:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=limit_order_exchange
# DB_USERNAME=root
# DB_PASSWORD=
```

### Broadcasting Configuration

For real-time order notifications, configure Pusher:

```env
BROADCAST_DRIVER=pusher
# or use 'log' for development (outputs to logs)

PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1
```

### Queue Configuration

```env
QUEUE_CONNECTION=database
# Alternative: redis, sync (for immediate execution)
```

### Session & Cache

```env
SESSION_DRIVER=database
CACHE_STORE=database
SESSION_LIFETIME=120
```

---

## API Endpoints

All endpoints require the `Content-Type: application/json` header. Protected endpoints require the `Authorization: Bearer {token}` header.

### Authentication Endpoints

#### POST /api/register

Register a new user account.

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response (201):**
```json
{
  "message": "User registered successfully",
  "access_token": "2|abcdef123456...",
  "token_type": "Bearer"
}
```

**Error Response (422):**
```json
{
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

---

#### POST /api/login

Authenticate user and receive API token.

**Request Body:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "message": "Login successful",
  "access_token": "2|abcdef123456...",
  "token_type": "Bearer"
}
```

**Error Response (401):**
```json
{
  "message": "Invalid credentials"
}
```

---

#### POST /api/logout

**Authentication:** Required (Bearer token)

Revoke the current API token.

**Response (200):**
```json
{
  "message": "Logout successful"
}
```

---

### User Profile Endpoints

#### GET /api/profile

**Authentication:** Required (Bearer token)

Retrieve current user information including all asset holdings.

**Response (200):**
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "balance": "10000.00",
  "assets": [
    {
      "id": 1,
      "symbol": "BTC",
      "amount": "1.50",
      "locked_amount": "0.50"
    },
    {
      "id": 2,
      "symbol": "ETH",
      "amount": "10.00",
      "locked_amount": "5.00"
    }
  ]
}
```

---

### Order Management Endpoints

#### GET /api/orders

**Authentication:** Required (Bearer token)

List all open orders in the system. Optionally filter by trading symbol.

**Query Parameters:**
- `symbol` (optional): Filter by symbol (e.g., `?symbol=BTC`)

**Response (200):**
```json
{
  "orders": [
    {
      "id": 1,
      "user_id": 2,
      "symbol": "BTC",
      "side": "buy",
      "price": "45000.00",
      "amount": "0.50",
      "status": "open",
      "created_at": "2025-12-19T10:30:00Z"
    },
    {
      "id": 2,
      "user_id": 3,
      "symbol": "BTC",
      "side": "sell",
      "price": "46000.00",
      "amount": "1.00",
      "status": "open",
      "created_at": "2025-12-19T10:25:00Z"
    }
  ]
}
```

---

#### POST /api/orders

**Authentication:** Required (Bearer token)

Place a new limit order. If matching orders exist, they are automatically matched and settled.

**Request Body:**
```json
{
  "symbol": "BTC",
  "side": "buy",
  "price": "45000.00",
  "amount": "0.50"
}
```

**Validation Rules:**
- `symbol`: Required, string
- `side`: Required, must be "buy" or "sell"
- `price`: Required, numeric, minimum 0.0001
- `amount`: Required, numeric

**Business Logic:**
- **Buy Orders:** User must have sufficient balance: `balance >= (price × amount)`
- **Sell Orders:** User must have sufficient asset amount available: `asset.amount >= amount`
- Upon successful placement, funds are locked immediately
- Automatic matching occurs if compatible orders exist

**Response (201):**
```json
{
  "message": "Order placed successfully",
  "order": {
    "id": 5,
    "user_id": 1,
    "symbol": "BTC",
    "side": "buy",
    "price": "45000.00",
    "amount": "0.50",
    "status": "open",
    "created_at": "2025-12-19T11:00:00Z"
  }
}
```

**Error Response (422):**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "price": ["The price field is required."],
    "amount": ["The amount must be numeric."]
  }
}
```

**Error Response (400):**
```json
{
  "message": "Insufficient balance to place buy order"
}
```

---

#### POST /api/orders/{id}/cancel

**Authentication:** Required (Bearer token)

Cancel an open order. Cancellation unlocks previously locked funds.

**URL Parameters:**
- `id`: Order ID to cancel

**Response (200):**
```json
{
  "message": "Order cancelled successfully"
}
```

**Error Response (404):**
```json
{
  "message": "Order not found"
}
```

**Error Response (400):**
```json
{
  "message": "Cannot cancel a filled order"
}
```

---

### Trade Broadcasting Endpoint

#### POST /api/trade/{id}/broadcast

**Authentication:** Not required

Broadcast a completed trade to involved parties via WebSocket. This is typically called after a trade is settled, to notify both buyer and seller in real-time.

**URL Parameters:**
- `id`: Trade ID to broadcast

**Response (200):**
```json
{
  "message": "Trade broadcasted successfully"
}
```

**WebSocket Event Payload** (sent to buyer and seller on private channels):
```json
{
  "event": "order.matched",
  "data": {
    "trade": {
      "id": 10,
      "price": "45000.00",
      "amount": "0.50"
    },
    "your_balance": "9775.00",
    "your_assets": [
      {
        "symbol": "BTC",
        "amount": "1.50",
        "locked_amount": "0.00"
      }
    ]
  }
}
```

**Error Response (404):**
```json
{
  "message": "Trade not found"
}
```

---

## Development & Testing

### Running the Development Server

Start all services concurrently (Laravel, queue, logs, Vite):

```bash
cd backend
composer run-script dev
```

This launches:
- Laravel development server on `http://localhost:8000`
- Vite frontend on `http://localhost:5173`
- Queue listener for background jobs
- Log viewer for debugging

### Running Tests

Run Pest PHP tests:

```bash
cd backend
composer run-script test
```

Or manually:
```bash
php artisan test
```

**Test Examples:**
- `tests/Feature/AuthTest.php` — Registration, login, logout
- `tests/Feature/OrderTest.php` — Order placement, matching, cancellation
- `tests/Unit/OrderServiceTest.php` — Order matching logic

### Frontend Development

From the `frontend/` directory:

```bash
# Install dependencies
npm install

# Start dev server
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview
```

### Useful Artisan Commands

```bash
# View all routes
php artisan route:list --path=api

# Clear all caches
php artisan config:clear && php artisan cache:clear

# Reset database (⚠️ destructive)
php artisan migrate:refresh --seed

# Check queue jobs
php artisan queue:work

# Restart queue listener
php artisan queue:restart
```

---

## Architecture Overview

### Order Matching Engine (OrderService)

The `OrderService` class handles all order lifecycle operations:

1. **Order Placement:**
   - Validates user has sufficient funds/assets
   - Creates order record with OPEN status
   - Locks funds immediately (balance for buy, asset amount for sell)

2. **Order Matching:**
   - Searches for compatible existing orders:
     - **Buy orders:** Find sell orders with same symbol, amount ≤ new order price, sorted by lowest price
     - **Sell orders:** Find buy orders with same symbol, amount ≤ new order price, sorted by highest price
   - Matches first compatible order found

3. **Trade Settlement:**
   - **Price Determination:** Whichever order was placed first determines the price
   - **Asset Transfer:** Buyer receives asset, seller's locked amount decreases
   - **Commission:** 1.5% deducted from seller's gross proceeds
   - **Payout:** Seller receives `(price × amount) × (1 - 0.015)`
   - **Status:** Both orders marked FILLED
   - **Trade Record:** Created with full details

4. **Concurrency Safety:**
   - Database transactions with row-level locking (`lockForUpdate()`)
   - Prevents race conditions in high-volume trading scenarios

### Real-time Broadcasting (OrderMatched Event)

When a trade settles:

1. `OrderMatched` event is dispatched with trade details
2. Three broadcasts are sent:
   - **Buyer notification:** Private channel `user.{buyer_id}` with buyer-specific data
   - **Seller notification:** Private channel `user.{seller_id}` with seller-specific data
   - **Public broadcast:** Public channel `trades` with trade summary
3. Frontend listeners receive updates and refresh UI

### Authentication (Laravel Sanctum)

- Users authenticate via `/api/login`, receive Bearer token
- Token stored in browser (localStorage or cookie)
- Sent with every request: `Authorization: Bearer {token}`
- Tokens scoped to "*" (all abilities)
- CSRF protection for state-changing requests

### Data Persistence

- **SQLite (default):** File-based database (`database/database.sqlite`)
- **MySQL:** Full-featured relational database (recommended for production)
- **Migrations:** Version-controlled schema in `database/migrations/`

---

## Error Handling

The API returns standard HTTP status codes:

- **200 OK:** Successful GET/POST request
- **201 Created:** Resource successfully created
- **400 Bad Request:** Invalid business logic (e.g., insufficient funds)
- **401 Unauthorized:** Missing or invalid authentication token
- **403 Forbidden:** User lacks permission for action
- **404 Not Found:** Resource does not exist
- **422 Unprocessable Entity:** Validation failed (invalid input)
- **500 Internal Server Error:** Server error (check logs)

All error responses include a `message` field and optional `errors` object for validation failures.

---

## Support & Contributing

For issues, questions, or contributions, please refer to the project repository.

