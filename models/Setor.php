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
}
