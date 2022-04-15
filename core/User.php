<?php
namespace Auth\Core;

use Auth\Exceptions\UserAlreadyExistsException;
use Database\Core\Database as DB;
use Exception;

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
     * Output the 36 character UUID
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
    static function login(int $id)
    {
        $token = md5(uniqid(rand(), true));

        $user = [
            "token" => $token,
            "attempts" => 0,
            "online" => 1
        ];

        self::setUser($id, $user);

        Session::setUserId($id);
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
        self::setUser(Session::getUserId(), $user);

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
            return DB::insertAssoc($_ENV['DB_PREFIX'].'user', $user);
        } else {
            throw new UserAlreadyExistsException('User with email ' . $user['email'] . ' already exists');
        }
    }

    static function deleteUser(int $id)
    {
        DB::delete("DELETE FROM " . $_ENV['DB_PREFIX'] . "user WHERE id = ?", [$id]);
    }

    /**
     * @param int $id
     * @return array|null
     */
    static function getUser(int $id): ?array
    {
        return DB::single('SELECT * FROM ' . $_ENV['DB_PREFIX'] . 'user WHERE id = ?', [$id]);
    }

    static function getUserByEmail(string $email): ?array
    {
        return DB::single('SELECT * FROM ' . $_ENV['DB_PREFIX'] . 'user WHERE email = ?', [$email]);
    }

    /**
     * @param int $id
     * @param array $user
     * @return void
     */
    static function setUser(int $id, array $user)
    {
        $condition = [
            "key" => "id",
            "operator" => "=",
            "value" => $id
        ];

        DB::updateAssoc($_ENV['DB_PREFIX'].'user', $user, $condition);
    }
}
