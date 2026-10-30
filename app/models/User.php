<?php

class User extends Model
{
    public function getByEmail($email)
    {
        $database = Database::openConnection();

        $database->prepare("
            SELECT *
            FROM users
            WHERE email = :email
            LIMIT 1
        ");

        $database->bindValue(
            ':email',
            $email
        );

        $database->execute();

        return $database->fetchAssociative();
    }


    public function getById($id)
    {
        $database = Database::openConnection();

        $database->prepare("
            SELECT
                id,
                name,
                email,
                role,
                department,
                created_at,
                updated_at
            FROM users
            WHERE id = :id
            LIMIT 1
        ");

        $database->bindValue(
            ':id',
            $id
        );

        $database->execute();

        return $database->fetchAssociative();
    }


    public function create($data)
    {
        $database = Database::openConnection();

        /*
        |--------------------------------------------------------------------------
        | HASH PASSWORD
        |--------------------------------------------------------------------------
        */

        $hashedPassword =
            password_hash(
                $data['password'],
                PASSWORD_DEFAULT
            );


        $database->prepare("
            INSERT INTO users (
                name,
                email,
                password,
                role,
                department
            )
            VALUES (
                :name,
                :email,
                :password,
                :role,
                :department
            )
        ");

        $database->bindValue(
            ':name',
            $data['name']
        );

        $database->bindValue(
            ':email',
            $data['email']
        );

        $database->bindValue(
            ':password',
            $hashedPassword
        );

        $database->bindValue(
            ':role',
            $data['role']
        );

        $database->bindValue(':department', isset($data['department']) ? $data['department'] : null);
        return $database->execute();
    }


    public function emailExists($email)
    {
        return $this->getByEmail($email)
            ? true
            : false;
    }

    public function getAll()
    {
        $database = Database::openConnection();

        $database->prepare("
        SELECT
            id,
            name,
            email,
            role,
            department,
            created_at,
            updated_at
        FROM users
        ORDER BY
            CASE WHEN role = 'admin' THEN 0 ELSE 1 END,
            name ASC
    ");

        $database->execute();

        return $database->fetchAllAssociative();
    }


    public function emailExistsExcept($email, $userId)
    {
        $database = Database::openConnection();

        $database->prepare("
        SELECT id
        FROM users
        WHERE email = :email
        AND id != :id
        LIMIT 1
    ");

        $database->bindValue(
            ':email',
            $email
        );

        $database->bindValue(
            ':id',
            $userId
        );

        $database->execute();

        return $database->fetchAssociative()
            ? true
            : false;
    }


    public function updateUser(
        $id,
        $data
    ) {
        $database = Database::openConnection();

        /*
        |--------------------------------------------------------------------------
        | PASSWORD PROVIDED
        |--------------------------------------------------------------------------
        */

        $department = array_key_exists('department', $data) ? $data['department'] : $this->getById($id)['department'];

        if (!empty($data['password'])) {

            $hashedPassword =
                password_hash(
                    $data['password'],
                    PASSWORD_DEFAULT
                );

            $database->prepare("
            UPDATE users
            SET
                name = :name,
                email = :email,
                password = :password,
                role = :role,
                department = :department
            WHERE id = :id
        ");

            $database->bindValue(
                ':password',
                $hashedPassword
            );

        } else {

            /*
            |--------------------------------------------------------------------------
            | KEEP EXISTING PASSWORD
            |--------------------------------------------------------------------------
            */

            $database->prepare("
            UPDATE users
            SET
                name = :name,
                email = :email,
                role = :role,
                department = :department
            WHERE id = :id
        ");
        }


        $database->bindValue(
            ':name',
            $data['name']
        );

        $database->bindValue(
            ':email',
            $data['email']
        );

        $database->bindValue(
            ':role',
            $data['role']
        );

        $database->bindValue(':department', $department);

        $database->bindValue(
            ':id',
            $id
        );

        return $database->execute();
    }


    public function deleteUser($id)
    {
        $database = Database::openConnection();

        $database->prepare("
        DELETE FROM users
        WHERE id = :id
    ");

        $database->bindValue(
            ':id',
            $id
        );

        return $database->execute();
    }
}
