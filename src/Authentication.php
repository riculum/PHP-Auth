<?php
namespace Riculum\Auth;

use Riculum\Auth\core\Session;
use Riculum\Auth\core\User;
use Riculum\Auth\exceptions\InvalidEmailException;
use Riculum\Auth\exceptions\InvalidPasswordException;
use Riculum\Auth\exceptions\TooManyAttemptsException;
use Riculum\Auth\exceptions\UserAlreadyExistsException;
use Riculum\Auth\exceptions\UserNotEnabledException;

class Authentication
{
    private const MAX_LOGIN_ATTEMPTS = 5;

    /**
     * @throws UserAlreadyExistsException
     */
    static function register(array $user): ?int
    {
        return User::addUser($user);
    }

    /**
     * @throws InvalidEmailException
     * @throws InvalidPasswordException
     * @throws TooManyAttemptsException
     * @throws UserNotEnabledException
     */
    static function login(string $email, string $password): bool
    {
        $currentUser = User::getUserByEmail($email);

        if (empty($currentUser)) {
            throw new InvalidEmailException('E-Mail address could not be found');
        }

        if (password_verify($password, $currentUser['password']) && $currentUser['attempts'] < self::MAX_LOGIN_ATTEMPTS && $currentUser['enabled'] == 1) {
            User::login($currentUser['uuid']);
        } else if ($currentUser['enabled'] != 1) {
            throw new UserNotEnabledException('User account has been deactivated');
        } else if ($currentUser['attempts'] >= self::MAX_LOGIN_ATTEMPTS) {
            throw new TooManyAttemptsException('Too many failed login attempts');
        } else {
            User::setUser($currentUser['uuid'], ['attempts' => $currentUser['attempts'] + 1]);
            throw new InvalidPasswordException('Incorrect Password');
        }

        return true;
    }

    static function logout(): bool
    {
        if (!empty(Session::getUserUUID())) {
            User::logout();
        }

        return true;
    }

    static function verify(): bool
    {
        $sessionToken = Session::getUserToken();
        $sessionUserUUID = Session::getUserUUID();

        if (empty($sessionUserUUID) || empty($sessionToken)) {
            return false;
        }

        $userToken = User::getUser($sessionUserUUID)['token'] ?? null;

        if (empty($userToken)) {
            return false;
        }

        if ($sessionToken === $userToken) {
            return true;
        }

        return false;
    }
}
