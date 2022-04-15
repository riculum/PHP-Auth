# PHP-Auth
A complete user authentication library written in PHP

## Installation
Use the package manager [composer](https://getcomposer.org) to install the library.
```bash
composer require riculum/php-auth
```

## Initial setup
### Credentials
The basic database settings can be set through environment variables. Add a `.env` file in the root of your project. Make sure the `.env` file is added to your `.gitignore` so it is not checked-in the code. By default, the library looks for the following variables:

* DB_HOST
* DB_NAME
* DB_USERNAME
* DB_PASSWORD
* DB_PREFIX

More information how to use environment variables [here](https://github.com/vlucas/phpdotenv)

### Database
```sql
CREATE TABLE IF NOT EXISTS user (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(50) NOT NULL UNIQUE,
    firstname VARCHAR(50) NOT NULL,
    lastname VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    token VARCHAR(255) NOT NULL,
    attempts TINYINT NOT NULL DEFAULT 0,
    online TINYINT NOT NULL DEFAULT 0,
    verified TINYINT NOT NULL DEFAULT 0,
    enabled TINYINT NOT NULL DEFAULT 1,
    updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    createdAt DATETIME DEFAULT CURRENT_TIMESTAMP
)
```

*Note: We recommend to set a database prefix*

### Configuration
Import vendor/autoload.php and load the `.env` settings
```php
require_once 'vendor/autoload.php';

use Auth\Core\Authentication as Auth;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
```

## Usage
### Registration
Use an associative array with user data to register a new user
```php
$user = array(
    'firstname' => 'John',
    'lastname' => 'Doe',
    'email' => 'john.doe@example.com', //must be unique
    'password' => '$2y$10$jNtkQSKNni2ELyoi9Y/lpedy7v92FYzqz5ePm1M6jPGY9hb8TCmAq',
    'token' => md5(uniqid(rand(), true))
);

try {
    echo Auth::register($user);
} catch (UserAlreadyExistsException $e) {
    echo 'User with the specified email address already exists';
} catch (Exception $e) {
    echo "Something went wrong";
}
```

### Login
```php
try {
    Auth::login('john.doe@example.com', '123456');
    echo 'Login successful';
} catch (InvalidEmailException | InvalidPasswordException $e) {
    echo 'Email or Password are wrong';
} catch(UserNotEnabledException $e) {
    echo 'User account has been deactivated';
} catch (TooManyAttemptsException $e) {
    echo 'Too many failed login attempts';
} catch (Exception $e) {
    echo "Something went wrong";
}
```

### Verify
```php
if (Auth::verify()) {
    echo "Authorization successful";
} else {
    echo "Authorization failed";
}
```
### Logout
```php
try {
    Auth::logout();
} catch (Exception $e) {
    echo 'Something went wrong';
}
```

## Bugreport & Contribution
If you find a bug, please either create a ticket in github, or initiate a pull request

## Versioning
We adhere to semantic (major.minor.patch) versioning (https://semver.org/). This means that:

* Patch (x.x.patch) versions fix bugs
* Minor (x.minor.x) versions introduce new, backwards compatible features or improve existing code.
* Major (major.x.x) versions introduce radical changes which are not backwards compatible.

In your automation or procedure you can always safely update patch & minor versions without the risk of your application failing.


