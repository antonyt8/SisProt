<?php

declare(strict_types=1);

class ProcessoService
{
    private PDO $db;
    private Processo $processoModel;
    private Tramitacao $tramitacaoModel;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->processoModel = new Processo();
        $this->tramitacaoModel = new Tramitacao();
    }

    public function tramitar(int $processoId, int $destinoSetor, string $despacho, int $userId): bool
    {
        $processo = $this->processoModel->buscarPorId($processoId);

        if ($processo === null) {
            return false;
        }

        $origemSetor = (int)$processo['setor_atual_id'];

        try {
            $this->db->beginTransaction();

            $this->processoModel->atualizarSetorStatus($processoId, $destinoSetor, 'em_tramitacao');

            $this->tramitacaoModel->criar([
                'processo_id' => $processoId,
                'origem_setor_id' => $origemSetor,
                'destino_setor_id' => $destinoSetor,
                'despacho' => $despacho,
                'user_id' => $userId,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido',
            ]);

            $this->db->commit();

            registrarLog('processo_tramitado', [
                'processo_id' => $processoId,
                'origem_setor_id' => $origemSetor,
                'destino_setor_id' => $destinoSetor,
                'user_id' => $userId,
            ]);

            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            registrarLog('erro_tramitacao', [
                'processo_id' => $processoId,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
