<?php
namespace Riculum\Auth\core;

use Exception;
use Riculum\Auth\exceptions\UserAlreadyExistsException;
use Riculum\Database as DB;

class User
{
    /**
     * Make sure, email is not in use yet
     * @param string $email
     * @return bool [true] if email is unique
     */
    private static function emailIsUnique(string $email): bool
    {
        return empty(DB::single('SELECT email FROM ' . $_ENV['DB_PREFIX'] . 'user WHERE email = ?', [$email]));
    }

    /**
     * Output the 36 character UUIDv4
     * @throws Exception
     */
    private static function generateUuid(): string
    {
        $data = $data ?? random_bytes(16);
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }


    /**
     * Login current user and set variables
     */
    static function login(string $uuid)
    {
        $token = md5(uniqid(rand(), true));

        $user = [
            "token" => $token,
            "attempts" => 0,
            "online" => 1
        ];

        self::setUser($uuid, $user);

        Session::setUserUUID($uuid);
        Session::setUserToken($token);
    }

    /**
     * Logout user and destroy session
     */
    static function logout()
    {
        $user = [
            "token" => md5(uniqid(rand(), true)),
            "online" => 0
        ];
        self::setUser(Session::getUserUUID(), $user);

        Session::destroySession();
    }

    /**
     * @param array $user
     * @return int|null
     * @throws UserAlreadyExistsException
     * @throws Exception
     */
    static function addUser(array $user): ?int
    {
        if (self::emailIsUnique($user['email'])) {
            $user['uuid'] = self::generateUuid();
            $user['resetToken'] = bin2hex(random_bytes(16));
            return DB::insertAssoc($_ENV['DB_PREFIX'].'user', $user);
        } else {
            throw new UserAlreadyExistsException('User with email ' . $user['email'] . ' already exists');
        }
    }

    /**
     * @param string $uuid
     * @return array|null
     */
    static function getUser(string $uuid): ?array
    {
        return DB::single('SELECT * FROM ' . $_ENV['DB_PREFIX'] . 'user WHERE uuid = ?', [$uuid]);
    }

    /**
     * @param string $email
     * @return array|null
     */
    static function getUserByEmail(string $email): ?array
    {
        return DB::single('SELECT * FROM ' . $_ENV['DB_PREFIX'] . 'user WHERE email = ?', [$email]);
    }

    /**
     * @param string $uuid
     * @param array $user
     * @return void
     */
    static function setUser(string $uuid, array $user)
    {
        $condition = [
            "key" => "uuid",
            "operator" => "=",
            "value" => $uuid
        ];

        DB::updateAssoc($_ENV['DB_PREFIX'].'user', $user, $condition);
    }
}
