<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

requireAuth();

// --- INICIALIZAÇÃO DE VARIÁVEIS E OBJETOS ---
$authController = null;
$processoController = null;

$erro = '';
$sucesso = '';
$userId = (int)($_SESSION['user_id'] ?? 0);
$isAdmin = $userId === 1; // Idealmente, usar uma role do banco de dados no futuro

// Geração de Token CSRF para segurança dos formulários
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

try {
    $authController = new AuthController();
    $processoController = new ProcessoController();
} catch (Throwable $e) {
    $erro = 'Banco de dados indisponível no momento. Inicie o MySQL e recarregue a página.';
}

// --- PROCESSAMENTO DE AÇÕES (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $processoController !== null) {
    $acao = (string)($_POST['acao'] ?? '');
    $postToken = (string)($_POST['csrf_token'] ?? '');
    $resultado = ['ok' => false, 'mensagem' => 'Ação não reconhecida.'];

    // Validação de segurança CSRF
    if (!hash_equals($csrfToken, $postToken)) {
        $resultado = ['ok' => false, 'mensagem' => 'Sessão expirada ou requisição inválida. Tente novamente.'];
    } else {
        // Roteamento de ações
        switch ($acao) {
            case 'logout':
                if ($authController !== null) {
                    $authController->logout();
                    header('Location: index.php');
                    exit;
                }
                break;
            case 'cadastrar_processo':
                $resultado = $processoController->cadastrar($_POST, $userId);
                break;
            case 'tramitar_processo':
                $resultado = $processoController->tramitar($_POST, $userId);
                break;
            case 'excluir_processo':
                $resultado = $processoController->excluirLogicamente($_POST, $userId);
                break;
            case 'finalizar_processo':
                $resultado = $processoController->finalizar($_POST, $userId);
                break;
            case 'editar_processo':
                $resultado = $processoController->editar($_POST, $userId);
                break;
            // Ações exclusivas de Administrador
            case 'criar_usuario':
                if ($isAdmin) $resultado = $processoController->criarUsuario($_POST);
                break;
            case 'atualizar_usuario':
                if ($isAdmin) $resultado = $processoController->atualizarUsuario($_POST);
                break;
            case 'inativar_usuario':
                if ($isAdmin) $resultado = $processoController->inativarUsuario($_POST);
                break;
            case 'criar_setor':
                if ($isAdmin) $resultado = $processoController->criarSetor($_POST);
                break;
            case 'atualizar_setor':
                if ($isAdmin) $resultado = $processoController->atualizarSetor($_POST);
                break;
        }
    }

    if ($resultado['ok']) {
        $sucesso = (string)$resultado['mensagem'];
    } else {
        $erro = (string)$resultado['mensagem'];
    }
}

// --- CONSULTAS E FILTROS (GET) ---
$filtros = [
    'protocolo'   => trim((string)($_GET['protocolo'] ?? '')),
    'cpf'         => trim((string)($_GET['cpf'] ?? '')),
    'status'      => trim((string)($_GET['status'] ?? '')),
    'setor_id'    => (string)($_GET['setor_id'] ?? ''),
    'data_inicio' => trim((string)($_GET['data_inicio'] ?? '')),
    'data_fim'    => trim((string)($_GET['data_fim'] ?? '')),
];

$setores = $processoController !== null ? $processoController->listarSetores() : [];
$setoresGerencial = $processoController !== null ? $processoController->listarSetoresGerencial() : [];
$processos = $processoController !== null ? $processoController->listarProcessosComFiltros($filtros) : [];
$usuarios = ($processoController !== null && $isAdmin) ? $processoController->listarUsuarios() : [];

$editarId = (int)($_GET['editar_id'] ?? 0);
$historicoId = (int)($_GET['historico_id'] ?? 0);
$processoEdicao = ($processoController !== null && $editarId > 0) ? $processoController->obterProcesso($editarId) : null;
$historico = ($processoController !== null && $historicoId > 0) ? $processoController->historico($historicoId) : [];

// Helper para escapar strings no HTML
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SisProt - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        /* Melhorias visuais sutis */
        input:focus, select:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.2);
            border-color: #6366f1;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen font-sans">
    
    <!-- HEADER -->
    <header class="bg-slate-900 text-white shadow-md sticky top-0 z-10">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="bg-indigo-600 p-2 rounded-lg">
                    <i data-lucide="layers" class="w-6 h-6 text-white"></i>
                </div>
                <div>
                    <h1 class="text-xl font-bold tracking-tight">SisProt</h1>
                    <p class="text-xs text-slate-300 flex items-center gap-1">
                        <i data-lucide="user" class="w-3 h-3"></i>
                        <?= e((string)($_SESSION['user_nome'] ?? 'N/A')) ?> <?= $isAdmin ? '<span class="text-indigo-300 font-semibold">(Admin)</span>' : '' ?>
                    </p>
                </div>
            </div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <input type="hidden" name="acao" value="logout">
                <button class="bg-red-500 hover:bg-red-600 transition-colors px-4 py-2 rounded-lg text-sm font-semibold flex items-center gap-2 shadow-sm" type="submit">
                    <i data-lucide="log-out" class="w-4 h-4"></i> Sair
                </button>
            </form>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 space-y-8 mt-4">
        
        <!-- ALERTS -->
        <?php if ($erro !== ''): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r-lg p-4 shadow-sm flex items-start gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
                <div>
                    <h3 class="font-bold text-sm">Erro detectado</h3>
                    <p class="text-sm"><?= e($erro) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 rounded-r-lg p-4 shadow-sm flex items-start gap-3">
                <i data-lucide="check-circle-2" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
                <div>
                    <h3 class="font-bold text-sm">Sucesso</h3>
                    <p class="text-sm"><?= e($sucesso) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- GRID AÇÕES RÁPIDAS -->
        <section class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- NOVO PROCESSO -->
            <article class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="flex items-center gap-2 border-b pb-3">
                    <i data-lucide="file-plus" class="w-5 h-5 text-indigo-600"></i>
                    <h2 class="font-bold text-lg">Novo Processo</h2>
                </div>
                <form method="post" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="acao" value="cadastrar_processo">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input class="w-full border rounded-lg px-4 py-2.5 text-sm transition-all" type="text" name="nome" placeholder="Nome do interessado" required>
                        <input class="w-full border rounded-lg px-4 py-2.5 text-sm transition-all" type="text" name="cpf" placeholder="CPF" required>
                    </div>
                    <input class="w-full border rounded-lg px-4 py-2.5 text-sm transition-all" type="text" name="assunto" placeholder="Assunto do processo" required>
                    <select class="w-full border rounded-lg px-4 py-2.5 text-sm transition-all bg-white" name="setor_id" required>
                        <option value="" disabled selected>Selecione o setor inicial</option>
                        <?php foreach ($setores as $setor): ?>
                            <option value="<?= (int)$setor['id'] ?>"><?= e((string)$setor['nome']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg px-4 py-2.5 font-semibold transition-colors flex justify-center items-center gap-2" type="submit">
                        Cadastrar Processo
                    </button>
                </form>
            </article>

            <!-- TRAMITAR PROCESSO -->
            <article class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                <div class="flex items-center gap-2 border-b pb-3">
                    <i data-lucide="send" class="w-5 h-5 text-amber-500"></i>
                    <h2 class="font-bold text-lg">Tramitar Processo</h2>
                </div>
                <form method="post" class="space-y-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="acao" value="tramitar_processo">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input class="w-full border rounded-lg px-4 py-2.5 text-sm transition-all" type="number" name="processo_id" min="1" placeholder="ID do Processo" required>
                        <select class="w-full border rounded-lg px-4 py-2.5 text-sm transition-all bg-white" name="destino_setor_id" required>
                            <option value="" disabled selected>Setor de destino</option>
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?= (int)$setor['id'] ?>"><?= e((string)$setor['nome']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <textarea class="w-full border rounded-lg px-4 py-2.5 text-sm transition-all resize-none" rows="2" name="despacho" placeholder="Despacho ou observação da tramitação" required></textarea>
                    <button class="w-full bg-slate-800 hover:bg-slate-900 text-white rounded-lg px-4 py-2.5 font-semibold transition-colors flex justify-center items-center gap-2" type="submit">
                        Confirmar Tramitação
                    </button>
                </form>
            </article>
        </section>

        <!-- FILTROS -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
            <div class="flex items-center gap-2 mb-2">
                <i data-lucide="filter" class="w-5 h-5 text-slate-500"></i>
                <h2 class="font-bold text-lg">Pesquisa e Filtros</h2>
            </div>
            <form method="get" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <input class="border rounded-lg px-4 py-2 text-sm" type="text" name="protocolo" value="<?= e($filtros['protocolo']) ?>" placeholder="Nº Protocolo">
                <input class="border rounded-lg px-4 py-2 text-sm" type="text" name="cpf" value="<?= e($filtros['cpf']) ?>" placeholder="CPF">
                
                <select class="border rounded-lg px-4 py-2 text-sm bg-white" name="status">
                    <option value="">Todos os Status</option>
                    <option value="aberto" <?= $filtros['status'] === 'aberto' ? 'selected' : '' ?>>Aberto</option>
                    <option value="em_tramitacao" <?= $filtros['status'] === 'em_tramitacao' ? 'selected' : '' ?>>Em Tramitação</option>
                    <option value="finalizado" <?= $filtros['status'] === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
                </select>
                
                <select class="border rounded-lg px-4 py-2 text-sm bg-white" name="setor_id">
                    <option value="">Todos os Setores</option>
                    <?php foreach ($setores as $setor): ?>
                        <option value="<?= (int)$setor['id'] ?>" <?= (string)(int)$setor['id'] === $filtros['setor_id'] ? 'selected' : '' ?>><?= e((string)$setor['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <input class="border rounded-lg px-4 py-2 text-sm text-slate-600" type="date" name="data_inicio" value="<?= e($filtros['data_inicio']) ?>" title="Data Inicial">
                <input class="border rounded-lg px-4 py-2 text-sm text-slate-600" type="date" name="data_fim" value="<?= e($filtros['data_fim']) ?>" title="Data Final">
                
                <div class="md:col-span-3 lg:col-span-6 flex justify-end">
                    <button class="bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-300 rounded-lg px-6 py-2 text-sm font-semibold transition-colors flex items-center gap-2" type="submit">
                        <i data-lucide="search" class="w-4 h-4"></i> Aplicar Filtros
                    </button>
                </div>
            </form>
        </section>

        <!-- EDIÇÃO DE PROCESSO -->
        <?php if ($processoEdicao !== null): ?>
            <section class="bg-amber-50 rounded-xl shadow-sm border border-amber-200 p-5 space-y-4">
                <div class="flex items-center gap-2 border-b border-amber-200 pb-3 text-amber-800">
                    <i data-lucide="edit-3" class="w-5 h-5"></i>
                    <h2 class="font-bold text-lg">Editando Processo #<?= (int)$processoEdicao['id'] ?></h2>
                </div>
                <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="acao" value="editar_processo">
                    <input type="hidden" name="processo_id" value="<?= (int)$processoEdicao['id'] ?>">
                    
                    <div>
                        <label class="block text-xs font-semibold text-amber-800 mb-1">Nome do Interessado</label>
                        <input class="w-full border border-amber-300 rounded-lg px-4 py-2 text-sm" type="text" name="nome" value="<?= e((string)$processoEdicao['interessado_nome']) ?>" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-amber-800 mb-1">CPF</label>
                        <input class="w-full border border-amber-300 rounded-lg px-4 py-2 text-sm" type="text" name="cpf" value="<?= e((string)$processoEdicao['interessado_cpf']) ?>" required>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-amber-800 mb-1">Assunto</label>
                        <input class="w-full border border-amber-300 rounded-lg px-4 py-2 text-sm" type="text" name="assunto" value="<?= e((string)$processoEdicao['assunto']) ?>" required>
                    </div>
                    <div class="md:col-span-2 flex justify-end gap-2 mt-2">
                        <a href="?" class="bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded-lg px-4 py-2 text-sm font-semibold transition-colors">Cancelar</a>
                        <button class="bg-amber-600 hover:bg-amber-700 text-white rounded-lg px-6 py-2 text-sm font-semibold transition-colors flex items-center gap-2" type="submit">
                            <i data-lucide="save" class="w-4 h-4"></i> Salvar Alterações
                        </button>
                    </div>
                </form>
            </section>
        <?php endif; ?>

        <!-- TABELA DE PROCESSOS -->
        <section class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b bg-slate-50 flex justify-between items-center">
                <div class="flex items-center gap-2">
                    <i data-lucide="list" class="w-5 h-5 text-slate-500"></i>
                    <h2 class="font-bold text-lg">Processos Ativos</h2>
                </div>
                <span class="bg-indigo-100 text-indigo-800 text-xs font-bold px-2.5 py-0.5 rounded-full"><?= count($processos) ?> resultados</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 font-semibold">ID</th>
                            <th class="px-4 py-3 font-semibold">Protocolo</th>
                            <th class="px-4 py-3 font-semibold">Interessado</th>
                            <th class="px-4 py-3 font-semibold">Status</th>
                            <th class="px-4 py-3 font-semibold">Setor Atual</th>
                            <th class="px-4 py-3 font-semibold text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if ($processos === []): ?>
                            <tr>
                                <td class="px-4 py-8 text-center text-slate-500" colspan="6">
                                    <div class="flex flex-col items-center justify-center gap-2">
                                        <i data-lucide="inbox" class="w-8 h-8 text-slate-300"></i>
                                        <p>Nenhum processo encontrado com os filtros atuais.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                        
                        <?php foreach ($processos as $processo): ?>
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-3 font-medium text-slate-900">#<?= (int)$processo['id'] ?></td>
                                <td class="px-4 py-3 font-mono text-slate-600"><?= e((string)$processo['numero_protocolo']) ?></td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-800"><?= e((string)$processo['interessado_nome']) ?></div>
                                    <div class="text-xs text-slate-500"><?= e((string)$processo['interessado_cpf']) ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <?php 
                                        $statusClass = match((string)$processo['status']) {
                                            'aberto' => 'bg-blue-100 text-blue-800',
                                            'em_tramitacao' => 'bg-amber-100 text-amber-800',
                                            'finalizado' => 'bg-emerald-100 text-emerald-800',
                                            default => 'bg-slate-100 text-slate-800'
                                        };
                                    ?>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold <?= $statusClass ?>">
                                        <?= ucfirst(str_replace('_', ' ', e((string)$processo['status']))) ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-slate-600"><?= e((string)($processo['setor_nome'] ?? '-')) ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end items-center gap-2">
                                        <a title="Editar" class="text-indigo-600 hover:bg-indigo-50 p-1.5 rounded transition-colors" href="?editar_id=<?= (int)$processo['id'] ?>">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                        <a title="Ver Histórico" class="text-slate-600 hover:bg-slate-100 p-1.5 rounded transition-colors" href="?historico_id=<?= (int)$processo['id'] ?>">
                                            <i data-lucide="history" class="w-4 h-4"></i>
                                        </a>
                                        
                                        <?php if ($processo['status'] !== 'finalizado'): ?>
                                        <form method="post" class="inline" title="Finalizar">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="acao" value="finalizar_processo">
                                            <input type="hidden" name="processo_id" value="<?= (int)$processo['id'] ?>">
                                            <button class="text-emerald-600 hover:bg-emerald-50 p-1.5 rounded transition-colors" type="submit" onclick="return confirm('Deseja realmente finalizar este processo?');">
                                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        <?php endif; ?>

                                        <form method="post" class="inline" title="Excluir">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <input type="hidden" name="acao" value="excluir_processo">
                                            <input type="hidden" name="processo_id" value="<?= (int)$processo['id'] ?>">
                                            <button class="text-red-600 hover:bg-red-50 p-1.5 rounded transition-colors" type="submit" onclick="return confirm('Confirma a exclusão lógica do processo #<?= (int)$processo['id'] ?>?');">
                                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- HISTÓRICO -->
        <?php if ($historicoId > 0): ?>
            <section class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4" id="secao-historico">
                <div class="flex items-center justify-between border-b pb-3 text-slate-800">
                    <div class="flex items-center gap-2">
                        <i data-lucide="history" class="w-5 h-5"></i>
                        <h2 class="font-bold text-lg">Histórico do Processo #<?= $historicoId ?></h2>
                    </div>
                    <a href="?" class="text-slate-400 hover:text-slate-600"><i data-lucide="x" class="w-5 h-5"></i></a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left border-collapse">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Data/Hora</th>
                                <th class="px-4 py-3 font-semibold">Origem → Destino</th>
                                <th class="px-4 py-3 font-semibold">Despacho</th>
                                <th class="px-4 py-3 font-semibold">Usuário (ID)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if ($historico === []): ?>
                                <tr>
                                    <td class="px-4 py-6 text-center text-slate-500" colspan="4">Nenhum histórico encontrado para este processo.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($historico as $item): ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 text-slate-600 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime((string)$item['data_hora'])) ?></td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2 text-sm">
                                            <span class="text-slate-500"><?= e((string)($item['origem_nome'] ?? 'Criação')) ?></span>
                                            <i data-lucide="arrow-right" class="w-3 h-3 text-slate-300"></i>
                                            <span class="font-medium text-slate-800"><?= e((string)($item['destino_nome'] ?? '-')) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700 italic">"<?= e((string)$item['despacho']) ?>"</td>
                                    <td class="px-4 py-3 text-slate-500"><i data-lucide="user" class="w-3 h-3 inline mr-1"></i> <?= (int)$item['user_id'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <!-- Auto-scroll para histórico ao carregar -->
            <script>document.getElementById('secao-historico').scrollIntoView({behavior: 'smooth'});</script>
        <?php endif; ?>

        <!-- ÁREA ADMINISTRATIVA -->
        <?php if ($isAdmin): ?>
            <div class="pt-6 mt-6 border-t border-slate-200">
                <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i data-lucide="settings" class="w-6 h-6 text-indigo-600"></i> Administração do Sistema
                </h2>
                
                <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                    
                    <!-- GESTÃO DE USUÁRIOS -->
                    <article class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i data-lucide="users" class="w-5 h-5 text-blue-500"></i> Gestão de Usuários</h3>
                        
                        <form method="post" class="bg-slate-50 p-4 rounded-lg border border-slate-100 grid grid-cols-1 md:grid-cols-2 gap-3">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="acao" value="criar_usuario">
                            <input class="border rounded-lg px-3 py-2 text-sm" type="text" name="usuario_nome" placeholder="Nome completo" required>
                            <input class="border rounded-lg px-3 py-2 text-sm" type="email" name="usuario_email" placeholder="E-mail" required>
                            <input class="border rounded-lg px-3 py-2 text-sm" type="password" name="usuario_senha" placeholder="Senha (min 6 chars)" minlength="6" required>
                            <select class="border rounded-lg px-3 py-2 text-sm bg-white" name="usuario_setor_id" required>
                                <option value="" disabled selected>Vincular a um setor</option>
                                <?php foreach ($setores as $setor): ?>
                                    <option value="<?= (int)$setor['id'] ?>"><?= e((string)$setor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="md:col-span-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors flex justify-center items-center gap-2" type="submit">
                                <i data-lucide="user-plus" class="w-4 h-4"></i> Criar Usuário
                            </button>
                        </form>

                        <div class="overflow-x-auto border rounded-lg">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                                    <tr>
                                        <th class="px-3 py-2">Nome</th>
                                        <th class="px-3 py-2">Setor</th>
                                        <th class="px-3 py-2 text-center">Status</th>
                                        <th class="px-3 py-2 text-right">Ação</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-3 py-2">
                                                <div class="font-medium text-slate-800"><?= e((string)$usuario['nome']) ?></div>
                                                <div class="text-xs text-slate-500"><?= e((string)$usuario['email']) ?></div>
                                            </td>
                                            <td class="px-3 py-2 text-slate-600 text-xs"><?= e((string)($usuario['setor_nome'] ?? '-')) ?></td>
                                            <td class="px-3 py-2 text-center">
                                                <?php if((int)$usuario['ativo'] === 1): ?>
                                                    <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-xs font-bold">Ativo</span>
                                                <?php else: ?>
                                                    <span class="bg-red-100 text-red-700 px-2 py-0.5 rounded text-xs font-bold">Inativo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                <form method="post" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                    <input type="hidden" name="acao" value="inativar_usuario">
                                                    <input type="hidden" name="usuario_id" value="<?= (int)$usuario['id'] ?>">
                                                    <?php if((int)$usuario['ativo'] === 1): ?>
                                                        <button class="text-red-500 hover:bg-red-50 p-1.5 rounded transition-colors" title="Inativar" type="submit" onclick="return confirm('Deseja inativar este usuário?');"><i data-lucide="user-x" class="w-4 h-4"></i></button>
                                                    <?php else: ?>
                                                        <!-- Poderia ter uma ação de reativar aqui -->
                                                        <button class="text-slate-300 cursor-not-allowed p-1.5 rounded" type="button" disabled><i data-lucide="user-x" class="w-4 h-4"></i></button>
                                                    <?php endif; ?>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>

                    <!-- GESTÃO DE SETORES -->
                    <article class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 space-y-4">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2"><i data-lucide="building-2" class="w-5 h-5 text-emerald-500"></i> Gestão de Setores</h3>
                        
                        <form method="post" class="flex gap-2">
                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <input type="hidden" name="acao" value="criar_setor">
                            <input class="flex-1 border rounded-lg px-4 py-2 text-sm" type="text" name="setor_nome" placeholder="Novo nome de setor" required>
                            <button class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg px-4 py-2 text-sm font-semibold transition-colors flex items-center gap-2" type="submit">
                                <i data-lucide="plus" class="w-4 h-4"></i> Adicionar
                            </button>
                        </form>

                        <div class="overflow-y-auto max-h-96 border rounded-lg">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-slate-50 text-slate-500 text-xs uppercase sticky top-0 shadow-sm">
                                    <tr>
                                        <th class="px-3 py-2 w-16">ID</th>
                                        <th class="px-3 py-2">Nome do Setor</th>
                                        <th class="px-3 py-2 w-24 text-center">Status</th>
                                        <th class="px-3 py-2 w-20 text-center">Salvar</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($setoresGerencial as $setor): ?>
                                        <tr class="hover:bg-slate-50">
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <input type="hidden" name="acao" value="atualizar_setor">
                                                <input type="hidden" name="setor_id" value="<?= (int)$setor['id'] ?>">
                                                
                                                <td class="px-3 py-2 text-slate-500 font-mono text-xs">#<?= (int)$setor['id'] ?></td>
                                                <td class="px-3 py-2">
                                                    <input class="w-full border-slate-200 rounded px-2 py-1 text-sm bg-transparent focus:bg-white focus:border-indigo-300" type="text" name="setor_nome" value="<?= e((string)$setor['nome']) ?>" required>
                                                </td>
                                                <td class="px-3 py-2">
                                                    <select class="w-full border-slate-200 rounded px-2 py-1 text-xs bg-transparent focus:bg-white focus:border-indigo-300" name="setor_ativo">
                                                        <option value="1" <?= (int)$setor['ativo'] === 1 ? 'selected' : '' ?>>Ativo</option>
                                                        <option value="0" <?= (int)$setor['ativo'] === 0 ? 'selected' : '' ?>>Inativo</option>
                                                    </select>
                                                </td>
                                                <td class="px-3 py-2 text-center">
                                                    <button class="text-indigo-600 hover:bg-indigo-50 p-1.5 rounded transition-colors" type="submit" title="Atualizar Setor">
                                                        <i data-lucide="save" class="w-4 h-4"></i>
                                                    </button>
                                                </td>
                                            </form>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </article>
                    
                </section>
            </div>
        <?php endif; ?>
    </main>

    <!-- Inicialização dos Ícones Lucide -->
    <script>
        lucide.createIcons();
    </script>
</body>