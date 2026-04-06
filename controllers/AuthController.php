<?php

declare(strict_types=1);

class AuthController
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function login(string $email, string $senha): bool
    {
        $sql = 'SELECT id, nome, email, senha, setor_id FROM users WHERE email = ? AND ativo = 1 LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user === false) {
            registrarLog('login_falha', ['email' => $email, 'motivo' => 'usuario_nao_encontrado']);
            return false;
        }

        if (!password_verify($senha, (string)$user['senha'])) {
            registrarLog('login_falha', ['email' => $email, 'motivo' => 'senha_invalida']);
            return false;
        }

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_nome'] = (string)$user['nome'];
        $_SESSION['setor_id'] = (int)$user['setor_id'];

        registrarLog('login_sucesso', ['user_id' => (int)$user['id']]);

        return true;
    }

    public function logout(): void
    {
        $userId = $_SESSION['user_id'] ?? null;

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }

        session_destroy();

        registrarLog('logout', ['user_id' => $userId]);
    }
}
