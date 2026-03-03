# Crovly PHP SDK

Official PHP SDK for Crovly — privacy-first captcha verification.

## Requirements

- PHP 8.1+
- ext-curl
- ext-json

## Installation

### Composer (recommended)

```bash
composer require crovly/crovly-php
```

### Manual

Download the `src/` directory and use PSR-4 autoloading, or require files manually.

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

use Crovly\Crovly;

$crovly = new Crovly('crvl_secret_your_secret_key');

// Verify a token from the widget
$response = $crovly->verify($_POST['crovly-token'], $_SERVER['REMOTE_ADDR']);

if ($response->isHuman()) {
    // Token is valid, score meets threshold — proceed
} else {
    // Verification failed or score too low — block
}
```

## Usage

### Basic Verification

```php
use Crovly\Crovly;

$crovly = new Crovly('crvl_secret_xxx');

$response = $crovly->verify($token);

echo $response->success; // true/false
echo $response->score;   // 0.0 — 1.0
echo $response->ip;      // Client IP that solved the challenge
```

### IP Binding

Pass the client's IP to enforce that the token was solved from the same IP:

```php
$response = $crovly->verify($token, $_SERVER['REMOTE_ADDR']);
```

### Custom Threshold

The default threshold is `0.5`. You can adjust it:

```php
// Stricter — require score >= 0.7
if ($response->isHuman(0.7)) {
    // High confidence human
}

// Lenient — accept score >= 0.3
if ($response->isHuman(0.3)) {
    // Low friction, some risk
}
```

### Response Object

| Property   | Type     | Description                                |
|------------|----------|--------------------------------------------|
| `success`  | `bool`   | Whether the token is valid                 |
| `score`    | `float`  | Risk score (0.0 = bot, 1.0 = human)       |
| `ip`       | `string` | IP address that solved the challenge       |
| `solvedAt` | `int`    | Unix timestamp in milliseconds             |

## Laravel Integration

### Middleware

Create `app/Http/Middleware/VerifyCrovly.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Crovly\Crovly;
use Crovly\Exceptions\CrovlyException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyCrovly
{
    private Crovly $crovly;

    public function __construct()
    {
        $this->crovly = new Crovly(config('services.crovly.secret_key'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->input('crovly-token');

        if (!$token) {
            abort(422, 'Captcha token is required');
        }

        try {
            $response = $this->crovly->verify($token, $request->ip());

            if (!$response->isHuman()) {
                abort(403, 'Captcha verification failed');
            }
        } catch (CrovlyException $e) {
            abort(500, 'Captcha service error');
        }

        return $next($request);
    }
}
```

Register in `bootstrap/app.php` (Laravel 11+):

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'crovly' => \App\Http\Middleware\VerifyCrovly::class,
    ]);
})
```

Use on routes:

```php
Route::post('/contact', [ContactController::class, 'store'])->middleware('crovly');
```

### Config

Add to `config/services.php`:

```php
'crovly' => [
    'secret_key' => env('CROVLY_SECRET_KEY'),
],
```

Add to `.env`:

```
CROVLY_SECRET_KEY=crvl_secret_xxx
```

## Plain PHP (No Framework)

```php
<?php

require_once 'vendor/autoload.php';

use Crovly\Crovly;
use Crovly\Exceptions\CrovlyException;

$crovly = new Crovly('crvl_secret_xxx');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['crovly-token'] ?? '';

    try {
        $response = $crovly->verify($token, $_SERVER['REMOTE_ADDR']);

        if ($response->isHuman()) {
            // Process form
            echo 'Form submitted successfully';
        } else {
            http_response_code(403);
            echo 'Bot detected (score: ' . $response->score . ')';
        }
    } catch (CrovlyException $e) {
        http_response_code(500);
        echo 'Verification error: ' . $e->getMessage();
    }
}
```

## Configuration

```php
$crovly = new Crovly('crvl_secret_xxx', [
    'apiUrl'  => 'https://api.crovly.com', // API base URL (default)
    'timeout' => 10,                        // Request timeout in seconds (default)
]);
```

## Error Handling

```php
use Crovly\Exceptions\CrovlyException;
use Crovly\Exceptions\ValidationException;
use Crovly\Exceptions\ApiException;

try {
    $response = $crovly->verify($token, $ip);
} catch (ValidationException $e) {
    // 400 — Invalid token or missing parameters
    echo $e->getMessage();
} catch (ApiException $e) {
    // 401 — Invalid secret key
    // 403 — Forbidden
    // 429 — Rate limited
    // 5xx — Server error
    echo $e->getStatusCode() . ': ' . $e->getMessage();
} catch (CrovlyException $e) {
    // Network errors, JSON parse errors
    echo $e->getErrorCode() . ': ' . $e->getMessage();
}
```

## Frontend Setup

Add the widget to your HTML form:

```html
<script src="https://get.crovly.com/widget.js" data-site-key="crvl_site_xxx"></script>
<form method="POST" action="/submit">
    <div id="crovly-captcha"></div>
    <button type="submit">Submit</button>
</form>
```

The widget adds a hidden `crovly-token` field to the form on successful verification.

## Documentation

Full documentation at [docs.crovly.com](https://docs.crovly.com).

## License

MIT
