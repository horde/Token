# Horde Token System

(AI generated on 2025 April 18th)

## Overview
The Horde Token system is a core component of the Horde Framework that provides secure token generation, validation, and management functionality. It is used across various Horde applications for authentication, session management, and secure URL generation.

## Key Features

### Token Types
1. **URL Tokens**
   - Used for secure URL generation and validation
   - Configurable lifetime (default: 30 seconds)
   - HMAC-based validation for security

2. **Authentication Tokens**
   - Used for session management and authentication
   - Supports various authentication backends
   - Configurable session timeout

3. **CSRF Protection Tokens**
   - Prevents Cross-Site Request Forgery attacks
   - Automatically generated and validated for forms

### Storage Backends
The token system supports multiple storage backends:

1. **SQL Storage**
   - Supports various SQL databases:
     - PostgreSQL
     - MySQL/MariaDB
     - SQLite
   - Schema for SQL storage:
   ```sql
   CREATE TABLE horde_tokens (
       token_address VARCHAR(100) NOT NULL,
       token_id VARCHAR(32) NOT NULL,
       token_timestamp BIGINT NOT NULL,
       PRIMARY KEY (token_address, token_id)
   );
   ```

2. **File Storage**
   - Simple file-based storage
   - Suitable for single-server deployments
   - Easy to backup and maintain
   - Configuration example:
   ```php
   $conf['token']['driver'] = 'File';
   $conf['token']['params']['path'] = '/path/to/token/storage';
   ```

3. **MongoDB Storage**
   - NoSQL storage option
   - High scalability
   - Document-based storage
   - Configuration example:
   ```php
   $conf['token']['driver'] = 'Mongo';
   $conf['token']['params']['mongo'] = array(
       'hostspec' => 'mongodb://localhost:27017',
       'database' => 'horde',
       'collection' => 'tokens'
   );
   ```

### Configuration
The token system is configured through Horde's main configuration file (`conf.php`):

```php
// Basic token settings
$conf['urls']['token_lifetime'] = 30;  // Token lifetime in seconds
$conf['urls']['hmac_lifetime'] = 30;   // HMAC validation lifetime

// Storage driver configuration
$conf['token']['driver'] = 'Sql';      // or 'File' or 'Mongo'
$conf['token']['params'] = array(
    // Driver-specific parameters
);
```

### Security Features
1. **Token Generation**
   - Cryptographically secure random token generation
   - Unique token IDs for each token address
   - Timestamp-based expiration

2. **Token Validation**
   - HMAC-based validation for URL tokens
   - Timestamp validation for expiration
   - Address-based token lookup

3. **Storage Security**
   - Secure storage across all backends
   - Automatic token cleanup
   - Protection against token reuse

## Integration

### Usage in Applications
The token system is integrated into various Horde applications:

1. **Authentication**
   - Session token management
   - Secure login handling
   - Password reset tokens

2. **URL Generation**
   - Secure link generation
   - One-time use URLs
   - Protected resource access

3. **Form Security**
   - CSRF protection
   - Secure form submission
   - State management

### API Usage
```php
// Get token instance
$token = $injector->getInstance('Horde_Token');

// Generate a new token
$tokenId = $token->get('token_address');

// Validate a token
$valid = $token->isValid($tokenId, 'token_address');

// Delete a token
$token->delete('token_address', $tokenId);
```

## Best Practices

1. **Storage Selection**
   - SQL: For traditional relational database setups
   - File: For simple, single-server deployments
   - MongoDB: For scalable, distributed systems

2. **Token Lifetime**
   - Set appropriate token lifetimes based on use case
   - Shorter lifetimes for sensitive operations
   - Consider user experience when setting expiration

3. **Security**
   - Always validate tokens before use
   - Use HTTPS for token transmission
   - Implement proper token cleanup

4. **Performance**
   - Choose appropriate storage backend for your needs
   - Implement caching where appropriate
   - Clean up expired tokens regularly

## Dependencies
- Horde Core Framework
- Storage backend requirements:
  - SQL: PDO or native database extension
  - File: Writeable directory
  - MongoDB: MongoDB PHP extension
- PHP 7.4 or higher

## Configuration Options

### Token Settings
- `token_lifetime`: Token validity period in seconds
- `hmac_lifetime`: HMAC validation period in seconds
- `token_driver`: Storage driver (Sql, File, or Mongo)

### Storage Options
- SQL Database (PostgreSQL, MySQL, SQLite)
- File System
- MongoDB
- Custom storage drivers

## Troubleshooting

### Common Issues
1. **Token Validation Failures**
   - Check token lifetime settings
   - Verify system time synchronization
   - Ensure proper token storage configuration

2. **Performance Issues**
   - Choose appropriate storage backend
   - Implement caching
   - Optimize database queries
   - Consider using MongoDB for high scalability

3. **Security Concerns**
   - Verify HTTPS usage
   - Check token generation randomness
   - Monitor token usage patterns
   - Ensure proper file permissions for file storage

## Contributing
Contributions to the Horde Token system are welcome. Please follow the Horde contribution guidelines and submit pull requests through GitHub.

## License
The Horde Token system is licensed under the same terms as the Horde Framework.
