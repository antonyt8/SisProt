<?php

declare(strict_types=1);

class ProcessoController
{
    private Processo $processoModel;
    private Setor $setorModel;
    private ProcessoService $processoService;

    public function __construct()
    {
        $this->processoModel = new Processo();
        $this->setorModel = new Setor();
        $this->processoService = new ProcessoService();
    }

    public function cadastrar(array $post, int $userId): array
    {
        $nome = trim((string)($post['nome'] ?? ''));
        $cpf = preg_replace('/\D/', '', (string)($post['cpf'] ?? '')) ?? '';
        $assunto = trim((string)($post['assunto'] ?? ''));
        $setor = (int)($post['setor_id'] ?? 0);

        if ($nome === '' || $assunto === '' || $setor <= 0) {
            return ['ok' => false, 'mensagem' => 'Preencha todos os campos obrigatorios.'];
        }

        if (!validarCPF($cpf)) {
            return ['ok' => false, 'mensagem' => 'CPF invalido.'];
        }

        $id = $this->processoModel->criar([
            'nome' => $nome,
            'cpf' => $cpf,
            'assunto' => $assunto,
            'setor' => $setor,
            'user' => $userId,
        ]);

        registrarLog('processo_criado', ['processo_id' => $id, 'user_id' => $userId]);

        return ['ok' => true, 'mensagem' => 'Processo cadastrado com sucesso.'];
    }

    public function tramitar(array $post, int $userId): array
    {
        $processoId = (int)($post['processo_id'] ?? 0);
        $destino = (int)($post['destino_setor_id'] ?? 0);
        $despacho = trim((string)($post['despacho'] ?? ''));

        if ($processoId <= 0 || $destino <= 0 || $despacho === '') {
            return ['ok' => false, 'mensagem' => 'Dados da tramitacao invalidos.'];
        }

        $ok = $this->processoService->tramitar($processoId, $destino, $despacho, $userId);

        if (!$ok) {
            return ['ok' => false, 'mensagem' => 'Falha ao tramitar processo.'];
        }

        return ['ok' => true, 'mensagem' => 'Processo tramitado com sucesso.'];
    }

    public function consultaPublica(string $cpf): array
    {
        $cpfLimpo = preg_replace('/\D/', '', $cpf) ?? '';

        if (!validarCPF($cpfLimpo)) {
            return [];
        }

        return $this->processoModel->buscarPublicoPorCPF($cpfLimpo);
    }

    public function listarProcessos(): array
    {
        return $this->processoModel->listarAtivos();
    }

    public function listarSetores(): array
    {
        return $this->setorModel->listarTodos();
    }
}
