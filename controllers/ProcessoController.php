<?php

declare(strict_types=1);

class ProcessoController
{
    private Processo $processoModel;
    private Setor $setorModel;
    private ProcessoService $processoService;
    private Tramitacao $tramitacaoModel;
    private Usuario $usuarioModel;

    public function __construct()
    {
        $this->processoModel = new Processo();
        $this->setorModel = new Setor();
        $this->processoService = new ProcessoService();
        $this->tramitacaoModel = new Tramitacao();
        $this->usuarioModel = new Usuario();
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

    public function listarProcessosComFiltros(array $filtros): array
    {
        $dados = [
            'protocolo' => trim((string)($filtros['protocolo'] ?? '')),
            'cpf' => preg_replace('/\D/', '', (string)($filtros['cpf'] ?? '')) ?? '',
            'status' => trim((string)($filtros['status'] ?? '')),
            'setor_id' => (int)($filtros['setor_id'] ?? 0),
            'data_inicio' => trim((string)($filtros['data_inicio'] ?? '')),
            'data_fim' => trim((string)($filtros['data_fim'] ?? '')),
        ];

        return $this->processoModel->listarComFiltros($dados);
    }

    public function obterProcesso(int $id): ?array
    {
        return $this->processoModel->buscarPorId($id);
    }

    public function excluirLogicamente(array $post, int $userId): array
    {
        $processoId = (int)($post['processo_id'] ?? 0);

        if ($processoId <= 0) {
            return ['ok' => false, 'mensagem' => 'ID do processo invalido para exclusao.'];
        }

        $processo = $this->processoModel->buscarPorId($processoId);
        if ($processo === null) {
            return ['ok' => false, 'mensagem' => 'Processo nao encontrado.'];
        }

        $this->processoModel->softDelete($processoId, $userId);

        return ['ok' => true, 'mensagem' => 'Processo removido logicamente com sucesso.'];
    }

    public function finalizar(array $post, int $userId): array
    {
        $processoId = (int)($post['processo_id'] ?? 0);

        if ($processoId <= 0) {
            return ['ok' => false, 'mensagem' => 'ID do processo invalido para finalizacao.'];
        }

        $processo = $this->processoModel->buscarPorId($processoId);
        if ($processo === null) {
            return ['ok' => false, 'mensagem' => 'Processo nao encontrado para finalizacao.'];
        }

        $this->processoModel->finalizar($processoId);
        registrarLog('processo_finalizado', ['processo_id' => $processoId, 'user_id' => $userId]);

        return ['ok' => true, 'mensagem' => 'Processo finalizado com sucesso.'];
    }

    public function editar(array $post, int $userId): array
    {
        $processoId = (int)($post['processo_id'] ?? 0);
        $nome = trim((string)($post['nome'] ?? ''));
        $cpf = preg_replace('/\D/', '', (string)($post['cpf'] ?? '')) ?? '';
        $assunto = trim((string)($post['assunto'] ?? ''));
        $setor = (int)($post['setor_id'] ?? 0);

        $resultado = ['ok' => false, 'mensagem' => 'ID do processo invalido para edicao.'];

        if ($processoId > 0) {
            if ($nome === '' || $assunto === '' || $setor <= 0) {
                $resultado = ['ok' => false, 'mensagem' => 'Preencha os campos obrigatorios para editar.'];
            } elseif (!validarCPF($cpf)) {
                $resultado = ['ok' => false, 'mensagem' => 'CPF invalido para edicao.'];
            } else {
                $processo = $this->processoModel->buscarPorId($processoId);

                if ($processo === null) {
                    $resultado = ['ok' => false, 'mensagem' => 'Processo nao encontrado para edicao.'];
                } else {
                    $this->processoModel->atualizarDados($processoId, $nome, $cpf, $assunto, $setor);
                    registrarLog('processo_editado', ['processo_id' => $processoId, 'user_id' => $userId]);
                    $resultado = ['ok' => true, 'mensagem' => 'Processo atualizado com sucesso.'];
                }
            }
        }

        return $resultado;
    }

    public function historico(int $processoId): array
    {
        if ($processoId <= 0) {
            return [];
        }

        return $this->tramitacaoModel->listarPorProcesso($processoId);
    }

    public function listarSetores(): array
    {
        return $this->setorModel->listarTodos();
    }

    public function listarSetoresGerencial(): array
    {
        return $this->setorModel->listarGerencial();
    }

    public function criarSetor(array $post): array
    {
        $nome = trim((string)($post['setor_nome'] ?? ''));

        if ($nome === '') {
            return ['ok' => false, 'mensagem' => 'Informe o nome do setor.'];
        }

        $this->setorModel->criar($nome);
        return ['ok' => true, 'mensagem' => 'Setor cadastrado com sucesso.'];
    }

    public function atualizarSetor(array $post): array
    {
        $id = (int)($post['setor_id'] ?? 0);
        $nome = trim((string)($post['setor_nome'] ?? ''));
        $ativo = (int)($post['setor_ativo'] ?? 0) === 1;

        if ($id <= 0 || $nome === '') {
            return ['ok' => false, 'mensagem' => 'Dados invalidos para atualizar setor.'];
        }

        $this->setorModel->atualizar($id, $nome, $ativo);
        return ['ok' => true, 'mensagem' => 'Setor atualizado com sucesso.'];
    }

    public function listarUsuarios(): array
    {
        return $this->usuarioModel->listarTodos();
    }

    public function criarUsuario(array $post): array
    {
        $nome = trim((string)($post['usuario_nome'] ?? ''));
        $email = trim((string)($post['usuario_email'] ?? ''));
        $senha = (string)($post['usuario_senha'] ?? '');
        $setorId = (int)($post['usuario_setor_id'] ?? 0);

        $resultado = ['ok' => false, 'mensagem' => 'Preencha todos os campos do usuario.'];

        if ($nome !== '' && $email !== '' && $senha !== '' && $setorId > 0) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $resultado = ['ok' => false, 'mensagem' => 'E-mail invalido para usuario.'];
            } elseif (strlen($senha) < 6) {
                $resultado = ['ok' => false, 'mensagem' => 'A senha do usuario deve ter ao menos 6 caracteres.'];
            } else {
                try {
                    $this->usuarioModel->criar($nome, $email, $senha, $setorId);
                    $resultado = ['ok' => true, 'mensagem' => 'Usuario criado com sucesso.'];
                } catch (Throwable $e) {
                    $resultado = ['ok' => false, 'mensagem' => 'Falha ao criar usuario. Verifique se o e-mail ja existe.'];
                }
            }
        }

        return $resultado;
    }

    public function atualizarUsuario(array $post): array
    {
        $id = (int)($post['usuario_id'] ?? 0);
        $nome = trim((string)($post['usuario_nome'] ?? ''));
        $email = trim((string)($post['usuario_email'] ?? ''));
        $setorId = (int)($post['usuario_setor_id'] ?? 0);
        $ativo = (int)($post['usuario_ativo'] ?? 0) === 1;

        $resultado = ['ok' => false, 'mensagem' => 'Dados invalidos para atualizar usuario.'];

        if ($id > 0 && $nome !== '' && $email !== '' && $setorId > 0) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $resultado = ['ok' => false, 'mensagem' => 'E-mail invalido para usuario.'];
            } else {
                try {
                    $this->usuarioModel->atualizar($id, $nome, $email, $setorId, $ativo);
                    $resultado = ['ok' => true, 'mensagem' => 'Usuario atualizado com sucesso.'];
                } catch (Throwable $e) {
                    $resultado = ['ok' => false, 'mensagem' => 'Falha ao atualizar usuario.'];
                }
            }
        }

        return $resultado;
    }

    public function inativarUsuario(array $post): array
    {
        $id = (int)($post['usuario_id'] ?? 0);

        if ($id <= 0) {
            return ['ok' => false, 'mensagem' => 'ID de usuario invalido para inativacao.'];
        }

        $this->usuarioModel->inativar($id);
        return ['ok' => true, 'mensagem' => 'Usuario inativado com sucesso.'];
    }
}
