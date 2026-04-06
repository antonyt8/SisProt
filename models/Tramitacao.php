<?php

declare(strict_types=1);

class Tramitacao
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function criar(array $dados): void
    {
        $sql = 'INSERT INTO tramitacoes (
                    processo_id,
                    origem_setor_id,
                    destino_setor_id,
                    despacho,
                    user_id,
                    ip,
                    user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            (int)$dados['processo_id'],
            (int)$dados['origem_setor_id'],
            (int)$dados['destino_setor_id'],
            $dados['despacho'],
            (int)$dados['user_id'],
            $dados['ip'],
            $dados['user_agent'],
        ]);
    }

    public function listarPorProcesso(int $processoId): array
    {
        $sql = 'SELECT t.*, s1.nome AS origem_nome, s2.nome AS destino_nome
                FROM tramitacoes t
                LEFT JOIN setores s1 ON s1.id = t.origem_setor_id
                LEFT JOIN setores s2 ON s2.id = t.destino_setor_id
                WHERE t.processo_id = ?
                ORDER BY t.data_hora DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$processoId]);

        return $stmt->fetchAll();
    }
}
