<?php

declare(strict_types=1);

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->garantirTabelaRecuperacao();
    }

    public function listarTodos(): array
    {
        $sql = 'SELECT u.id, u.nome, u.email, u.setor_id, u.ativo, u.created_at, s.nome AS setor_nome
                FROM users u
                LEFT JOIN setores s ON s.id = u.setor_id
                ORDER BY u.nome';

        return $this->db->query($sql)->fetchAll();
    }

    public function criar(string $nome, string $email, string $senha, int $setorId): void
    {
        $sql = 'INSERT INTO users (nome, email, senha, setor_id, ativo) VALUES (?, ?, ?, ?, 1)';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $setorId]);
    }

    public function atualizar(int $id, string $nome, string $email, int $setorId, bool $ativo): void
    {
        $sql = 'UPDATE users SET nome = ?, email = ?, setor_id = ?, ativo = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nome, $email, $setorId, $ativo ? 1 : 0, $id]);
    }

    public function inativar(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE users SET ativo = 0 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function solicitarRecuperacao(string $email): ?string
    {
        $stmt = $this->db->prepare('SELECT id FROM users WHERE email = ? AND ativo = 1 LIMIT 1');
        $stmt->execute([$email]);
        $userId = $stmt->fetchColumn();

        if ($userId === false) {
            return null;
        }

        $token = bin2hex(random_bytes(24));
        $expiraEm = (new DateTimeImmutable('+30 minutes'))->format('Y-m-d H:i:s');

        $sql = 'INSERT INTO recuperacao_senhas (user_id, token, expira_em, usado) VALUES (?, ?, ?, 0)';
        $insert = $this->db->prepare($sql);
        $insert->execute([(int)$userId, $token, $expiraEm]);

        return $token;
    }

    public function redefinirSenhaPorToken(string $token, string $novaSenha): bool
    {
        $sql = 'SELECT id, user_id FROM recuperacao_senhas
                WHERE token = ? AND usado = 0 AND expira_em >= NOW()
                ORDER BY id DESC LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $registro = $stmt->fetch();

        if ($registro === false) {
            return false;
        }

        $this->db->beginTransaction();

        try {
            $upUser = $this->db->prepare('UPDATE users SET senha = ? WHERE id = ?');
            $upUser->execute([password_hash($novaSenha, PASSWORD_DEFAULT), (int)$registro['user_id']]);

            $upToken = $this->db->prepare('UPDATE recuperacao_senhas SET usado = 1 WHERE id = ?');
            $upToken->execute([(int)$registro['id']]);

            $this->db->commit();
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return false;
        }
    }

    private function garantirTabelaRecuperacao(): void
    {
        $sql = 'CREATE TABLE IF NOT EXISTS recuperacao_senhas (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    token VARCHAR(100) NOT NULL UNIQUE,
                    expira_em DATETIME NOT NULL,
                    usado TINYINT(1) NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    CONSTRAINT fk_recuperacao_user FOREIGN KEY (user_id) REFERENCES users(id)
                )';

        $this->db->exec($sql);
    }
}
