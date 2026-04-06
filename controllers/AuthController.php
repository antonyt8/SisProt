<?php

declare(strict_types=1);

class AuthController
{
    private PDO $db;
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->usuarioModel = new Usuario();
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

    public function solicitarRecuperacaoSenha(string $email): array
    {
        $email = trim($email);

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'mensagem' => 'Informe um e-mail valido.'];
        }

        $token = $this->usuarioModel->solicitarRecuperacao($email);

        if ($token === null) {
            return ['ok' => false, 'mensagem' => 'Nao foi possivel localizar usuario ativo com este e-mail.'];
        }

        registrarLog('recuperacao_solicitada', ['email' => $email]);

        return [
            'ok' => true,
            'mensagem' => 'Solicitacao registrada. Utilize o token gerado para redefinir a senha.',
            'token' => $token,
        ];
    }

    public function redefinirSenhaComToken(string $token, string $novaSenha): array
    {
        $resultado = ['ok' => false, 'mensagem' => 'Token invalido.'];

        if (strlen($token) >= 20) {
            if (strlen($novaSenha) < 6) {
                $resultado = ['ok' => false, 'mensagem' => 'A nova senha deve ter ao menos 6 caracteres.'];
            } else {
                $ok = $this->usuarioModel->redefinirSenhaPorToken($token, $novaSenha);

                if ($ok) {
                    registrarLog('senha_redefinida', ['token_prefixo' => substr($token, 0, 8)]);
                    $resultado = ['ok' => true, 'mensagem' => 'Senha redefinida com sucesso.'];
                } else {
                    $resultado = ['ok' => false, 'mensagem' => 'Token expirado ou ja utilizado.'];
                }
            }
        }

        return $resultado;
    }
}
