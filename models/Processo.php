<?php

declare(strict_types=1);

require_once __DIR__ . '/ProtocoloGenerationException.php';

class Processo
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function criar(array $dados): int
    {
        $tentativas = 0;

        while ($tentativas < 5) {
            $tentativas++;
            $numeroProtocolo = $this->proximoNumeroProtocolo();

            try {
                $sql = 'INSERT INTO processos (
                            numero_protocolo,
                            interessado_nome,
                            interessado_cpf,
                            assunto,
                            status,
                            setor_atual_id,
                            user_id
                        ) VALUES (?, ?, ?, ?, ?, ?, ?)';

                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    $numeroProtocolo,
                    $dados['nome'],
                    $dados['cpf'],
                    $dados['assunto'],
                    'aberto',
                    (int)$dados['setor'],
                    (int)$dados['user'],
                ]);

                return (int)$this->db->lastInsertId();
            } catch (PDOException $e) {
                if ((string)$e->getCode() !== '23000') {
                    throw $e;
                }
            }
        }

        throw new ProtocoloGenerationException('Nao foi possivel gerar um numero de protocolo unico.');
    }

    public function buscarPorId(int $id): ?array
    {
        $sql = 'SELECT * FROM processos WHERE id = ? AND deleted_at IS NULL LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $dados = $stmt->fetch();

        return $dados !== false ? $dados : null;
    }

    public function listarAtivos(): array
    {
        $sql = 'SELECT p.*, s.nome AS setor_nome
                FROM processos p
                LEFT JOIN setores s ON s.id = p.setor_atual_id
                WHERE p.deleted_at IS NULL
                ORDER BY p.created_at DESC';

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll();
    }

    public function listarComFiltros(array $filtros): array
    {
        $sql = 'SELECT p.*, s.nome AS setor_nome
                FROM processos p
                LEFT JOIN setores s ON s.id = p.setor_atual_id
                WHERE p.deleted_at IS NULL';
        $params = [];

        if (($filtros['protocolo'] ?? '') !== '') {
            $sql .= ' AND p.numero_protocolo LIKE ?';
            $params[] = '%' . $filtros['protocolo'] . '%';
        }

        if (($filtros['cpf'] ?? '') !== '') {
            $sql .= ' AND p.interessado_cpf = ?';
            $params[] = $filtros['cpf'];
        }

        if (($filtros['status'] ?? '') !== '') {
            $sql .= ' AND p.status = ?';
            $params[] = $filtros['status'];
        }

        if ((int)($filtros['setor_id'] ?? 0) > 0) {
            $sql .= ' AND p.setor_atual_id = ?';
            $params[] = (int)$filtros['setor_id'];
        }

        if (($filtros['data_inicio'] ?? '') !== '') {
            $sql .= ' AND DATE(p.created_at) >= ?';
            $params[] = $filtros['data_inicio'];
        }

        if (($filtros['data_fim'] ?? '') !== '') {
            $sql .= ' AND DATE(p.created_at) <= ?';
            $params[] = $filtros['data_fim'];
        }

        $sql .= ' ORDER BY p.created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    public function buscarPublicoPorCPF(string $cpf): array
    {
        $sql = 'SELECT numero_protocolo, assunto, status, created_at
                FROM processos
                WHERE interessado_cpf = ?
                  AND deleted_at IS NULL
                ORDER BY created_at DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cpf]);

        return $stmt->fetchAll();
    }

    public function atualizarSetorStatus(int $processoId, int $setorId, string $status): void
    {
        $sql = 'UPDATE processos
                SET setor_atual_id = ?, status = ?
                WHERE id = ? AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$setorId, $status, $processoId]);
    }

    public function atualizarDados(int $id, string $nome, string $cpf, string $assunto, int $setorId): void
    {
        $sql = 'UPDATE processos
                SET interessado_nome = ?, interessado_cpf = ?, assunto = ?, setor_atual_id = ?
                WHERE id = ? AND deleted_at IS NULL';

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$nome, $cpf, $assunto, $setorId, $id]);
    }

    public function finalizar(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE processos SET status = ? WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute(['finalizado', $id]);
    }

    public function softDelete(int $id, int $userId): void
    {
        $sql = 'UPDATE processos SET deleted_at = CURRENT_TIMESTAMP WHERE id = ? AND deleted_at IS NULL';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        registrarLog('processo_soft_delete', [
            'processo_id' => $id,
            'user_id' => $userId,
        ]);
    }

    private function proximoNumeroProtocolo(): string
    {
        $prefixo = date('Y.m');
        $stmt = $this->db->prepare('SELECT numero_protocolo FROM processos WHERE numero_protocolo LIKE ? ORDER BY id DESC LIMIT 1');
        $stmt->execute([$prefixo . '.%']);
        $ultimo = $stmt->fetchColumn();

        $sequencial = 1;

        if (is_string($ultimo) && str_contains($ultimo, '.')) {
            $partes = explode('.', $ultimo);
            $ultimoSeq = (int)($partes[2] ?? 0);
            $sequencial = $ultimoSeq + 1;
        }

        return gerarProtocolo($sequencial);
    }
}
