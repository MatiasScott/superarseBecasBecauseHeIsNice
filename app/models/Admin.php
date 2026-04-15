<?php

class Admin
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function buscarPorUsuario($usuario)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE usuario = ? AND activo = 1 LIMIT 1');
            $stmt->execute([$usuario]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Error al buscar admin por usuario: ' . $e->getMessage());
            return null;
        }
    }

    public function buscarPorId($id)
    {
        try {
            $stmt = $this->pdo->prepare('SELECT * FROM admins WHERE id = ? AND activo = 1 LIMIT 1');
            $stmt->execute([(int) $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (PDOException $e) {
            error_log('Error al buscar admin por ID: ' . $e->getMessage());
            return null;
        }
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify((string) $password, (string) $hash);
    }

    public function hashPassword($password)
    {
        return password_hash((string) $password, PASSWORD_DEFAULT);
    }

    public function actualizarContrasenia($id, $passwordHash, $primerInicio = 0)
    {
        try {
            $stmt = $this->pdo->prepare('UPDATE admins SET contrasenia_login = ?, primer_inicio = ? WHERE id = ?');
            return $stmt->execute([(string) $passwordHash, (int) $primerInicio, (int) $id]);
        } catch (PDOException $e) {
            error_log('Error al actualizar contraseña de admin: ' . $e->getMessage());
            return false;
        }
    }

    public function crearAdmin($usuario, $nombre, $passwordHash, $primerInicio = 1)
    {
        try {
            $stmt = $this->pdo->prepare('INSERT INTO admins (usuario, nombre, contrasenia_login, primer_inicio, activo) VALUES (?, ?, ?, ?, 1)');
            return $stmt->execute([
                (string) $usuario,
                $nombre !== null ? (string) $nombre : null,
                (string) $passwordHash,
                (int) $primerInicio,
            ]);
        } catch (PDOException $e) {
            error_log('Error al crear cuenta admin: ' . $e->getMessage());
            return false;
        }
    }
}
