# Token Test Architecture

## Directory Structure

```
test/
├── bootstrap.php              # Modern PHPUnit bootstrap (PHPUnit 11-13)
├── Unit/                      # Business logic tests (no DB required)
│   ├── Legacy/               # Legacy PSR-0 backend tests
│   │   ├── BackendTestCase.php  # Shared test suite for all backends
│   │   └── FileTest.php         # File backend tests (20 tests)
│   └── Mock/                 # Mock interfaces for PSR-4 tests
│       ├── TokenStorageInterface.php  # Storage abstraction
│       └── InMemoryTokenStorage.php   # In-memory implementation
└── Db/                       # Database persistence tests
    ├── SqlTest.php           # SQL backend tests (20 tests, skipped)
    └── MongoTest.php         # MongoDB backend tests (20 tests, skipped)
```

## Test Organization

### test/Unit/Legacy/
Legacy tests for PSR-0 backends. These test the full integration
of token generation/validation with file-based storage.

**Status:** ✅ All 20 tests passing (FileTest)

**Coverage:**
- Token generation and validation
- Seed-based tokens
- Token expiration and timeouts
- Unique token verification
- Exception handling

### test/Db/
Database persistence tests for SQL and MongoDB backends.

**Status:** ⏭️ Skipped (Horde_Test_Factory_Db not available)

**Note:** These tests require:
- `Horde_Test_Factory_Db` for SqlTest
- `Horde_Test_Factory_Mongo` for MongoTest

When running, these will test the full integration with real
database adapters (SQLite for SqlTest, MongoDB for MongoTest).

### test/Unit/Mock/
Mock storage interfaces for testing token business logic
without database coupling.

**Purpose:**
- Decouple token logic from DB implementation
- Enable fast, reliable unit tests
- Provide pattern for PSR-4 conversion

**Migration Note:**
This mocking pattern should be migrated to `Horde\Db` once that
package is modernized. The tight coupling of DB access to business
logic is an architectural issue inherited from Horde 3/4 era.

## Running Tests

```bash
# All tests
vendor/bin/phpunit

# Unit tests only (no DB required)
vendor/bin/phpunit --testsuite unit

# Database tests (requires DB adapters)
vendor/bin/phpunit --testsuite database

# With coverage (requires xdebug or pcov)
vendor/bin/phpunit --coverage-html build/coverage
```

## Test Dependencies

### Required (for unit tests)
- PHPUnit 11.5+
- PHP 8.4+ (or 8.1+ with adjustments)

### Optional (for database tests)
- `horde/test` - Provides `Horde_Test_Factory_Db` and `Horde_Test_Factory_Mongo`
- `horde/db` - SQL database abstraction
- `horde/mongo` - MongoDB abstraction
- SQLite PDO extension (for SqlTest)
- MongoDB extension (for MongoTest)

## Architectural Notes

### Separation of Concerns

The test reorganization separates:
1. **Business Logic Tests** (`test/Unit/`) - Token generation, validation, expiration
2. **Persistence Tests** (`test/Db/`) - Database storage and retrieval

This separation enables:
- Testing token logic without DB dependencies
- Faster test execution (unit tests run in seconds)
- Cleaner PSR-4 conversion with proper layer separation

### Future Direction

During PSR-4 conversion, consider:
1. **Extract Storage Interface** - Create `TokenStorageInterface` in main code
2. **Implement Adapters** - Refactor File/Sql/Mongo to implement interface
3. **Dependency Injection** - Inject storage into token service
4. **Split Package** - Consider separating `horde/token` (core) from `horde/token-db` (persistence)

This would align with modern architecture:
```
Horde\Token\Token               # Core service (business logic)
Horde\Token\Storage\*           # Storage adapters (infrastructure)
Horde\Token\Test\Unit\*         # Unit tests (fast, no DB)
Horde\Token\Test\Integration\*  # Integration tests (with DB)
```

### Migration to Horde\Db

The mock storage pattern (`test/Unit/Mock/`) should eventually
migrate to `Horde\Db` as a reusable test infrastructure:

```
Horde\Db\Test\Mock\DatabaseInterface
Horde\Db\Test\Mock\InMemoryDatabase
```

This would provide:
- Consistent mocking across all Horde packages
- Reduced duplication of test infrastructure
- Better testing patterns for Horde 6+
