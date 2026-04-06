<?php

declare(strict_types=1);

class Setor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function listarTodos(): array
    {
        $stmt = $this->db->query('SELECT id, nome, ativo FROM setores WHERE ativo = 1 ORDER BY nome');

        return $stmt->fetchAll();
    }

    public function listarGerencial(): array
    {
        $stmt = $this->db->query('SELECT id, nome, ativo, created_at FROM setores ORDER BY nome');

        return $stmt->fetchAll();
    }

    public function criar(string $nome): void
    {
        $stmt = $this->db->prepare('INSERT INTO setores (nome, ativo) VALUES (?, 1)');
        $stmt->execute([$nome]);
    }

    public function atualizar(int $id, string $nome, bool $ativo): void
    {
        $stmt = $this->db->prepare('UPDATE setores SET nome = ?, ativo = ? WHERE id = ?');
        $stmt->execute([$nome, $ativo ? 1 : 0, $id]);
    }
}
