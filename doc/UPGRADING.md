# Upgrading to Horde_Token 3.0 (PSR-4)

## Overview

Horde_Token 3.0 introduces a modern PSR-4 architecture with clean separation of concerns, improved testability, and a more intuitive API. This guide helps you migrate from the PSR-0 (2.x) version to PSR-4 (3.0).

## What Changed

### Architecture Changes

**PSR-0 (2.x):** Tight coupling between token logic and storage
```
Horde_Token_Base (abstract)
  ├── Token generation + validation logic
  └── Storage interface (exists, add, purge)
       ├── Horde_Token_File
       ├── Horde_Token_Sql
       ├── Horde_Token_Mongo
       └── Horde_Token_Null
```

**PSR-4 (3.0):** Clean separation with dependency injection
```
Horde\Token\Token (facade)
  ├── TokenGenerator (pure logic)
  ├── TokenValidator (pure logic)
  └── TokenStorageInterface
       ├── FileStorage
       ├── SqlStorage
       ├── MongoStorage
       ├── NullStorage
       └── InMemoryStorage (testing)
```

### Key Benefits

1. **Testable** - Mock storage for unit tests
2. **Type-safe** - PHP 8.1+ with strict types and readonly properties
3. **Immutable** - Value objects (TokenConfig, GeneratedToken)
4. **Flexible** - Dependency injection, easy to extend
5. **Modern** - PSR-4 autoloading, PER-1 coding style

## Migration Guide

### 1. Installation

Update your `composer.json`:

```json
{
    "require": {
        "horde/token": "^3.0"
    }
}
```

Run `composer update horde/token`.

### 2. Update Namespaces

**Before (PSR-0):**
```php
$token = new Horde_Token_Sql([
    'secret' => 'my-secret',
    'db' => $db
]);
```

**After (PSR-4):**
```php
use Horde\Token\Token;

$token = Token::sql('my-secret', $db);
```

### 3. File Storage Migration

**Before (PSR-0):**
```php
$token = new Horde_Token_File([
    'secret' => 'my-secret',
    'token_dir' => '/tmp/tokens'
]);

$tokenString = $token->get('checkout_form');
$token->validate($tokenString, 'checkout_form');
```

**After (PSR-4):**
```php
use Horde\Token\Token;

$token = Token::file('my-secret', '/tmp/tokens');

$generated = $token->generate('checkout_form');
$token->validateUnique($generated->token, 'checkout_form');

// OR use the value object directly
echo $generated->token;
echo $generated->expiresAt;
```

### 4. SQL Storage Migration

**Before (PSR-0):**
```php
$token = new Horde_Token_Sql([
    'secret' => 'my-secret',
    'db' => $db,
    'table' => 'custom_tokens' // optional
]);

$tokenString = $token->get('delete_user');
if (!$token->isValid($tokenString, 'delete_user')) {
    throw new Exception('Invalid token');
}
```

**After (PSR-4):**
```php
use Horde\Token\Token;
use Horde\Token\TokenConfig;

// Simple usage
$token = Token::sql('my-secret', $db);

// With custom configuration
$config = TokenConfig::default('my-secret')
    ->withLifetime(3600);  // 1 hour expiry

$token = Token::sql('my-secret', $db, $config);

$generated = $token->generate('delete_user');
if (!$token->isValid($generated->token, 'delete_user')) {
    throw new Exception('Invalid token');
}
```

**Custom table name:**
```php
use Horde\Token\Storage\SqlStorage;
use Horde\Token\TokenGenerator;
use Horde\Token\TokenValidator;
use Horde\Token\Token;

$config = TokenConfig::default('my-secret');
$storage = new SqlStorage($db, $config->timeout, 'custom_tokens');

$token = new Token(
    new TokenGenerator($config),
    new TokenValidator($config, $storage),
    $storage,
    $config
);
```

### 5. MongoDB Storage Migration

**Before (PSR-0):**
```php
$token = new Horde_Token_Mongo([
    'secret' => 'my-secret',
    'mongo_db' => $mongoDb
]);
```

**After (PSR-4):**
```php
use Horde\Token\Token;
use Horde\Token\Storage\MongoStorage;
use Horde\Token\TokenGenerator;
use Horde\Token\TokenValidator;

$config = TokenConfig::default('my-secret');
$storage = new MongoStorage($mongoDb, $config->timeout);

$token = new Token(
    new TokenGenerator($config),
    new TokenValidator($config, $storage),
    $storage,
    $config
);
```

### 6. Null Storage (No Validation)

**Before (PSR-0):**
```php
$token = new Horde_Token_Null([
    'secret' => 'my-secret'
]);
```

**After (PSR-4):**
```php
use Horde\Token\Token;

$token = Token::null('my-secret');
```

### 7. Token Configuration

**Before (PSR-0):**
```php
$token = new Horde_Token_File([
    'secret' => 'my-secret',
    'token_dir' => '/tmp/tokens',
    'token_lifetime' => 1800,  // 30 minutes
    'timeout' => 86400          // 24 hours cleanup
]);
```

**After (PSR-4):**
```php
use Horde\Token\Token;
use Horde\Token\TokenConfig;

$config = TokenConfig::default('my-secret')
    ->withLifetime(1800)   // 30 minutes
    ->withTimeout(86400);  // 24 hours cleanup

$token = Token::file('my-secret', '/tmp/tokens', $config);
```

### 8. Token Validation Methods

**Before (PSR-0):**
```php
// Check if valid (doesn't mark as used)
$isValid = $token->isValid($tokenString, $seed);

// Validate and throw exception
try {
    $token->validate($tokenString, $seed);
} catch (Horde_Token_Exception_Invalid $e) {
    // Invalid signature
} catch (Horde_Token_Exception_Expired $e) {
    // Token expired
}

// Validate and mark as used (one-time use)
try {
    $token->validateUnique($tokenString, $seed);
} catch (Horde_Token_Exception_Used $e) {
    // Token already used
}
```

**After (PSR-4):**
```php
use Horde\Token\Exception\InvalidTokenException;
use Horde\Token\Exception\ExpiredTokenException;
use Horde\Token\Exception\UsedTokenException;

// Check if valid (doesn't mark as used)
$isValid = $token->isValid($tokenString, $seed);

// Validate and throw exception (PSR-4 doesn't have validate() method - use isValid or validateUnique)
if (!$token->isValid($tokenString, $seed)) {
    throw new InvalidTokenException('Token validation failed');
}

// Validate and mark as used (one-time use)
try {
    $token->validateUnique($tokenString, $seed);
} catch (UsedTokenException $e) {
    // Token already used
} catch (ExpiredTokenException $e) {
    // Token expired
} catch (InvalidTokenException $e) {
    // Invalid signature
}
```

### 9. Working with Generated Tokens

**PSR-4 introduces a GeneratedToken value object:**

```php
use Horde\Token\Token;

$token = Token::file('my-secret', '/tmp/tokens');
$generated = $token->generate('form_id');

// Access token string
echo $generated->token;             // "base64url_encoded_token"
echo (string) $generated;           // same as above

// Check expiration
if ($generated->isExpired()) {
    echo "Token expired!";
}

// Get seconds until expiration
$seconds = $generated->getSecondsUntilExpiration();
if ($seconds !== null && $seconds > 0) {
    echo "Expires in {$seconds} seconds";
}

// Use in HTML forms
echo '<input type="hidden" name="token" value="' . htmlspecialchars($generated->token) . '">';
```

### 10. Testing Your Code

**PSR-4 makes testing much easier:**

```php
use Horde\Token\TokenGenerator;
use Horde\Token\TokenValidator;
use Horde\Token\TokenConfig;
use Horde\Token\Storage\InMemoryStorage;

// Unit test example
public function testTokenValidation(): void
{
    $config = TokenConfig::default('test-secret')->withLifetime(3600);
    $storage = new InMemoryStorage();
    $generator = new TokenGenerator($config);
    $validator = new TokenValidator($config, $storage);

    $generated = $generator->generate('test-seed');

    // Should be valid
    $this->assertTrue($validator->isValid($generated->token, 'test-seed'));

    // Should fail with wrong seed
    $this->assertFalse($validator->isValid($generated->token, 'wrong-seed'));
}
```

## Common Patterns

### Pattern 1: Form CSRF Protection

**Before (PSR-0):**
```php
// Generate token for form
$csrf = new Horde_Token_File(['secret' => SECRET, 'token_dir' => '/tmp']);
$tokenString = $csrf->get('delete_account');

// In HTML
<input type="hidden" name="token" value="<?php echo htmlspecialchars($tokenString); ?>">

// Validate on submission
if (!$csrf->isValid($_POST['token'], 'delete_account', null, true)) {
    die('CSRF token invalid or already used');
}
```

**After (PSR-4):**
```php
use Horde\Token\Token;

// Generate token for form
$csrf = Token::file(SECRET, '/tmp');
$token = $csrf->generate('delete_account');

// In HTML
<input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

// Validate on submission
try {
    $csrf->validateUnique($_POST['token'], 'delete_account');
    // Token valid, process form
} catch (\Horde\Token\Exception\TokenException $e) {
    die('CSRF token error: ' . $e->getMessage());
}
```

### Pattern 2: API Request Signing

```php
use Horde\Token\Token;
use Horde\Token\TokenConfig;

// Short-lived tokens for API requests
$config = TokenConfig::default(API_SECRET)->withLifetime(300); // 5 minutes
$token = Token::null(API_SECRET, $config); // No storage needed

$generated = $token->generate('api_request_' . $userId);

// Client sends token with request
// Server validates (within 5 minutes)
if (!$token->isValid($receivedToken, 'api_request_' . $userId)) {
    http_response_code(401);
    die('Invalid or expired token');
}
```

### Pattern 3: One-Time Action Links

```php
use Horde\Token\Token;
use Horde\Token\TokenConfig;

// Generate one-time link for password reset
$config = TokenConfig::default(SECRET)->withLifetime(3600); // 1 hour
$token = Token::sql(SECRET, $db, $config);

$resetToken = $token->generate('reset_password_' . $userId);
$link = "https://example.com/reset?token={$resetToken->token}&user={$userId}";

// Send link via email...

// When user clicks link:
try {
    $token->validateUnique($_GET['token'], 'reset_password_' . $_GET['user']);
    // Token valid and marked as used - show password reset form
} catch (\Horde\Token\Exception\ExpiredTokenException $e) {
    die('This link has expired. Please request a new password reset.');
} catch (\Horde\Token\Exception\UsedTokenException $e) {
    die('This link has already been used.');
} catch (\Horde\Token\Exception\TokenException $e) {
    die('Invalid reset link.');
}
```

## Backward Compatibility

### PSR-0 Compatibility Layer

The PSR-0 classes (`Horde_Token_*`) are still available but **deprecated**. They delegate to the new PSR-4 implementation:

```php
// Still works but deprecated
$token = new Horde_Token_File(['secret' => 'abc', 'token_dir' => '/tmp']);
$tokenString = $token->get('form');
$token->validate($tokenString, 'form');

// Internally delegates to:
// $psr4 = Token::file('abc', '/tmp');
// $generated = $psr4->generate('form');
// $psr4->validateUnique($generated->token, 'form');
```

**Migration path:**
1. Update to Token 3.0 (PSR-0 compatibility enabled)
2. Test your application
3. Gradually migrate to PSR-4 API
4. Remove PSR-0 usage before Token 4.0 (compatibility will be removed)

## Breaking Changes

### API Changes

1. **No `validate()` method** - Use `isValid()` or `validateUnique()`
2. **Generate returns object** - `generate()` returns `GeneratedToken`, not string
3. **Exception hierarchy** - New exception classes under `Horde\Token\Exception\`
4. **Constructor signature** - No array-based config, use factory methods or DI
5. **Seed parameter** - Now type-hinted as `string` (was optional default)

### Removed Features

1. **`Horde_Token::generateId()`** - Static utility removed (use `TokenGenerator`)
2. **Array-based configuration** - Use `TokenConfig` object instead
3. **`getNonce()` public method** - Now internal only

### Database Schema

**No changes required** - The database schema is compatible with PSR-0:

```sql
CREATE TABLE horde_tokens (
    token_address    VARCHAR(100) NOT NULL,
    token_id         VARCHAR(32) NOT NULL,
    token_timestamp  BIGINT NOT NULL,
    PRIMARY KEY (token_address, token_id)
);
```

## Troubleshooting

### Issue: "Class 'Horde_Token_File' not found"

**Solution:** Update autoloader or use PSR-4 namespace:
```php
// Old (PSR-0)
$token = new Horde_Token_File(...);

// New (PSR-4)
use Horde\Token\Token;
$token = Token::file(...);
```

### Issue: "Call to undefined method validate()"

**Solution:** PSR-4 uses `isValid()` or `validateUnique()`:
```php
// Old (PSR-0)
$token->validate($tokenString, $seed);

// New (PSR-4)
$token->validateUnique($tokenString, $seed);
```

### Issue: "generate() returns object, not string"

**Solution:** Access the token property:
```php
// Old (PSR-0)
$tokenString = $token->get('seed');

// New (PSR-4)
$generated = $token->generate('seed');
$tokenString = $generated->token;  // or: (string) $generated
```

### Issue: "Secret must be non-empty"

**Solution:** Ensure secret is provided:
```php
// Check your configuration
$token = Token::file(SECRET, '/tmp');  // SECRET constant must be defined and non-empty
```

## PHP Requirements

- **Minimum PHP:** 8.1
- **Required extensions:** `ext-hash`
- **Optional extensions:** `ext-pdo` (for SqlStorage), `ext-mongodb` (for MongoStorage)

## Getting Help

- **Documentation:** https://www.horde.org/libraries/Horde_Token
- **API Reference:** See inline PHPDoc comments
- **Issues:** https://github.com/horde/Token/issues
- **Mailing list:** dev@lists.horde.org

## Summary

PSR-4 Token 3.0 provides:
- ✅ Clean separation of concerns (logic vs storage)
- ✅ Type-safe API with PHP 8.1+
- ✅ Easy to test (dependency injection)
- ✅ Immutable value objects
- ✅ Modern PSR-4 autoloading
- ✅ Backward compatible (via PSR-0 shims)

The migration is straightforward and can be done gradually. Start by updating to 3.0, test with PSR-0 compatibility, then migrate to PSR-4 API at your own pace.
