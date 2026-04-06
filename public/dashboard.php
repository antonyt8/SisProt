<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

requireAuth();

$authController = null;
$processoController = null;

$erro = '';
$sucesso = '';
$userId = (int)$_SESSION['user_id'];
$isAdmin = $userId === 1;

try {
    $authController = new AuthController();
    $processoController = new ProcessoController();
} catch (Throwable $e) {
    $erro = 'Banco de dados indisponivel no momento. Inicie o MySQL no XAMPP e recarregue a pagina.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $processoController !== null) {
    $acao = (string)($_POST['acao'] ?? '');
    $resultado = ['ok' => false, 'mensagem' => 'Acao nao reconhecida.'];

    if ($acao === 'logout' && $authController !== null) {
        $authController->logout();
        header('Location: index.php');
        exit;
    }

    if ($acao === 'cadastrar_processo') {
        $resultado = $processoController->cadastrar($_POST, $userId);
    }

    if ($acao === 'tramitar_processo') {
        $resultado = $processoController->tramitar($_POST, $userId);
    }

    if ($acao === 'excluir_processo') {
        $resultado = $processoController->excluirLogicamente($_POST, $userId);
    }

    if ($acao === 'finalizar_processo') {
        $resultado = $processoController->finalizar($_POST, $userId);
    }

    if ($acao === 'editar_processo') {
        $resultado = $processoController->editar($_POST, $userId);
    }

    if ($isAdmin && $acao === 'criar_usuario') {
        $resultado = $processoController->criarUsuario($_POST);
    }

    if ($isAdmin && $acao === 'atualizar_usuario') {
        $resultado = $processoController->atualizarUsuario($_POST);
    }

    if ($isAdmin && $acao === 'inativar_usuario') {
        $resultado = $processoController->inativarUsuario($_POST);
    }

    if ($isAdmin && $acao === 'criar_setor') {
        $resultado = $processoController->criarSetor($_POST);
    }

    if ($isAdmin && $acao === 'atualizar_setor') {
        $resultado = $processoController->atualizarSetor($_POST);
    }

    if ($resultado['ok']) {
        $sucesso = (string)$resultado['mensagem'];
    } else {
        $erro = (string)$resultado['mensagem'];
    }
}

$filtros = [
    'protocolo' => trim((string)($_GET['protocolo'] ?? '')),
    'cpf' => trim((string)($_GET['cpf'] ?? '')),
    'status' => trim((string)($_GET['status'] ?? '')),
    'setor_id' => (string)($_GET['setor_id'] ?? ''),
    'data_inicio' => trim((string)($_GET['data_inicio'] ?? '')),
    'data_fim' => trim((string)($_GET['data_fim'] ?? '')),
];

$setores = $processoController !== null ? $processoController->listarSetores() : [];
$setoresGerencial = $processoController !== null ? $processoController->listarSetoresGerencial() : [];
$processos = $processoController !== null ? $processoController->listarProcessosComFiltros($filtros) : [];
$usuarios = ($processoController !== null && $isAdmin) ? $processoController->listarUsuarios() : [];

$editarId = (int)($_GET['editar_id'] ?? 0);
$historicoId = (int)($_GET['historico_id'] ?? 0);
$processoEdicao = ($processoController !== null && $editarId > 0) ? $processoController->obterProcesso($editarId) : null;
$historico = ($processoController !== null && $historicoId > 0) ? $processoController->historico($historicoId) : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SisProt - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen">
    <header class="bg-slate-900 text-white">
        <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
            <div>
                <h1 class="text-xl font-bold">SisProt - Painel Interno</h1>
                <p class="text-xs text-slate-300">Usuario: <?= htmlspecialchars((string)($_SESSION['user_nome'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') ?><?= $isAdmin ? ' (admin)' : '' ?></p>
            </div>
            <form method="post">
                <input type="hidden" name="acao" value="logout">
                <button class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-sm font-semibold" type="submit">Sair</button>
            </form>
        </div>
    </header>

    <main class="max-w-7xl mx-auto p-4 space-y-6">
        <?php if ($erro !== ''): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded p-3"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded p-3"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <article class="bg-white border rounded p-4 space-y-3">
                <h2 class="font-semibold">Novo processo</h2>
                <form method="post" class="space-y-2">
                    <input type="hidden" name="acao" value="cadastrar_processo">
                    <input class="w-full border rounded px-3 py-2" type="text" name="nome" placeholder="Nome do interessado" required>
                    <input class="w-full border rounded px-3 py-2" type="text" name="cpf" placeholder="CPF" required>
                    <input class="w-full border rounded px-3 py-2" type="text" name="assunto" placeholder="Assunto" required>
                    <select class="w-full border rounded px-3 py-2" name="setor_id" required>
                        <option value="">Setor inicial</option>
                        <?php foreach ($setores as $setor): ?>
                            <option value="<?= (int)$setor['id'] ?>"><?= htmlspecialchars((string)$setor['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="w-full bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-2" type="submit">Cadastrar</button>
                </form>
            </article>

            <article class="bg-white border rounded p-4 space-y-3">
                <h2 class="font-semibold">Tramitar processo</h2>
                <form method="post" class="space-y-2">
                    <input type="hidden" name="acao" value="tramitar_processo">
                    <input class="w-full border rounded px-3 py-2" type="number" name="processo_id" min="1" placeholder="ID do processo" required>
                    <select class="w-full border rounded px-3 py-2" name="destino_setor_id" required>
                        <option value="">Setor destino</option>
                        <?php foreach ($setores as $setor): ?>
                            <option value="<?= (int)$setor['id'] ?>"><?= htmlspecialchars((string)$setor['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <textarea class="w-full border rounded px-3 py-2" rows="3" name="despacho" placeholder="Despacho" required></textarea>
                    <button class="w-full bg-slate-800 hover:bg-slate-900 text-white rounded px-3 py-2" type="submit">Tramitar</button>
                </form>
            </article>
        </section>

        <section class="bg-white border rounded p-4 space-y-3">
            <h2 class="font-semibold">Pesquisa e filtros</h2>
            <form method="get" class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-2">
                <input class="border rounded px-3 py-2" type="text" name="protocolo" value="<?= htmlspecialchars($filtros['protocolo'], ENT_QUOTES, 'UTF-8') ?>" placeholder="Protocolo">
                <input class="border rounded px-3 py-2" type="text" name="cpf" value="<?= htmlspecialchars($filtros['cpf'], ENT_QUOTES, 'UTF-8') ?>" placeholder="CPF">
                <select class="border rounded px-3 py-2" name="status">
                    <option value="">Status</option>
                    <option value="aberto" <?= $filtros['status'] === 'aberto' ? 'selected' : '' ?>>aberto</option>
                    <option value="em_tramitacao" <?= $filtros['status'] === 'em_tramitacao' ? 'selected' : '' ?>>em_tramitacao</option>
                    <option value="finalizado" <?= $filtros['status'] === 'finalizado' ? 'selected' : '' ?>>finalizado</option>
                </select>
                <select class="border rounded px-3 py-2" name="setor_id">
                    <option value="">Setor</option>
                    <?php foreach ($setores as $setor): ?>
                        <option value="<?= (int)$setor['id'] ?>" <?= (string)(int)$setor['id'] === $filtros['setor_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$setor['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <input class="border rounded px-3 py-2" type="date" name="data_inicio" value="<?= htmlspecialchars($filtros['data_inicio'], ENT_QUOTES, 'UTF-8') ?>">
                <input class="border rounded px-3 py-2" type="date" name="data_fim" value="<?= htmlspecialchars($filtros['data_fim'], ENT_QUOTES, 'UTF-8') ?>">
                <button class="md:col-span-3 lg:col-span-6 bg-indigo-600 hover:bg-indigo-700 text-white rounded px-3 py-2" type="submit">Aplicar filtros</button>
            </form>
        </section>

        <?php if ($processoEdicao !== null): ?>
            <section class="bg-white border rounded p-4 space-y-3">
                <h2 class="font-semibold">Edicao de processo #<?= (int)$processoEdicao['id'] ?></h2>
                <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <input type="hidden" name="acao" value="editar_processo">
                    <input type="hidden" name="processo_id" value="<?= (int)$processoEdicao['id'] ?>">
                    <input class="border rounded px-3 py-2" type="text" name="nome" value="<?= htmlspecialchars((string)$processoEdicao['interessado_nome'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <input class="border rounded px-3 py-2" type="text" name="cpf" value="<?= htmlspecialchars((string)$processoEdicao['interessado_cpf'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <input class="md:col-span-2 border rounded px-3 py-2" type="text" name="assunto" value="<?= htmlspecialchars((string)$processoEdicao['assunto'], ENT_QUOTES, 'UTF-8') ?>" required>
                    <select class="border rounded px-3 py-2" name="setor_id" required>
                        <?php foreach ($setores as $setor): ?>
                            <option value="<?= (int)$setor['id'] ?>" <?= (int)$setor['id'] === (int)$processoEdicao['setor_atual_id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$setor['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="bg-amber-600 hover:bg-amber-700 text-white rounded px-3 py-2" type="submit">Salvar alteracoes</button>
                </form>
            </section>
        <?php endif; ?>

        <section class="bg-white border rounded overflow-hidden">
            <div class="p-4 border-b font-semibold">Processos ativos</div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100">
                        <tr>
                            <th class="text-left p-2">ID</th>
                            <th class="text-left p-2">Protocolo</th>
                            <th class="text-left p-2">Interessado</th>
                            <th class="text-left p-2">CPF</th>
                            <th class="text-left p-2">Status</th>
                            <th class="text-left p-2">Setor</th>
                            <th class="text-left p-2">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($processos === []): ?>
                            <tr><td class="p-3" colspan="7">Nenhum processo encontrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($processos as $processo): ?>
                            <tr class="border-t">
                                <td class="p-2"><?= (int)$processo['id'] ?></td>
                                <td class="p-2"><?= htmlspecialchars((string)$processo['numero_protocolo'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="p-2"><?= htmlspecialchars((string)$processo['interessado_nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="p-2"><?= htmlspecialchars((string)$processo['interessado_cpf'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="p-2"><?= htmlspecialchars((string)$processo['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="p-2"><?= htmlspecialchars((string)($processo['setor_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="p-2">
                                    <div class="flex flex-wrap gap-1">
                                        <a class="text-xs bg-indigo-600 text-white px-2 py-1 rounded" href="?editar_id=<?= (int)$processo['id'] ?>">Editar</a>
                                        <a class="text-xs bg-slate-600 text-white px-2 py-1 rounded" href="?historico_id=<?= (int)$processo['id'] ?>">Historico</a>
                                        <form method="post" class="inline">
                                            <input type="hidden" name="acao" value="finalizar_processo">
                                            <input type="hidden" name="processo_id" value="<?= (int)$processo['id'] ?>">
                                            <button class="text-xs bg-emerald-600 text-white px-2 py-1 rounded" type="submit">Finalizar</button>
                                        </form>
                                        <form method="post" class="inline" onsubmit="return confirm('Confirma exclusao logica do processo?');">
                                            <input type="hidden" name="acao" value="excluir_processo">
                                            <input type="hidden" name="processo_id" value="<?= (int)$processo['id'] ?>">
                                            <button class="text-xs bg-red-600 text-white px-2 py-1 rounded" type="submit">Excluir logico</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <?php if ($historicoId > 0): ?>
            <section class="bg-white border rounded p-4">
                <h2 class="font-semibold mb-3">Historico de tramitacao do processo #<?= $historicoId ?></h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="text-left p-2">Data</th>
                                <th class="text-left p-2">Origem</th>
                                <th class="text-left p-2">Destino</th>
                                <th class="text-left p-2">Despacho</th>
                                <th class="text-left p-2">Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($historico === []): ?>
                                <tr><td class="p-2" colspan="5">Nenhum historico encontrado para este processo.</td></tr>
                            <?php endif; ?>
                            <?php foreach ($historico as $item): ?>
                                <tr class="border-t">
                                    <td class="p-2"><?= htmlspecialchars((string)$item['data_hora'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="p-2"><?= htmlspecialchars((string)($item['origem_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="p-2"><?= htmlspecialchars((string)($item['destino_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="p-2"><?= htmlspecialchars((string)$item['despacho'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="p-2"><?= (int)$item['user_id'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($isAdmin): ?>
            <section class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <article class="bg-white border rounded p-4 space-y-3">
                    <h2 class="font-semibold">Gestao de usuarios</h2>
                    <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-2">
                        <input type="hidden" name="acao" value="criar_usuario">
                        <input class="border rounded px-3 py-2" type="text" name="usuario_nome" placeholder="Nome" required>
                        <input class="border rounded px-3 py-2" type="email" name="usuario_email" placeholder="E-mail" required>
                        <input class="border rounded px-3 py-2" type="password" name="usuario_senha" placeholder="Senha minima 6" required>
                        <select class="border rounded px-3 py-2" name="usuario_setor_id" required>
                            <option value="">Setor</option>
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?= (int)$setor['id'] ?>"><?= htmlspecialchars((string)$setor['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="md:col-span-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded px-3 py-2" type="submit">Criar usuario</button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="text-left p-2">Nome</th>
                                    <th class="text-left p-2">Email</th>
                                    <th class="text-left p-2">Setor</th>
                                    <th class="text-left p-2">Ativo</th>
                                    <th class="text-left p-2">Acoes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <tr class="border-t">
                                        <td class="p-2"><?= htmlspecialchars((string)$usuario['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="p-2"><?= htmlspecialchars((string)$usuario['email'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="p-2"><?= htmlspecialchars((string)($usuario['setor_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="p-2"><?= (int)$usuario['ativo'] === 1 ? 'sim' : 'nao' ?></td>
                                        <td class="p-2">
                                            <form method="post" class="inline">
                                                <input type="hidden" name="acao" value="inativar_usuario">
                                                <input type="hidden" name="usuario_id" value="<?= (int)$usuario['id'] ?>">
                                                <button class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded" type="submit">Inativar</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="bg-white border rounded p-4 space-y-3">
                    <h2 class="font-semibold">Gestao de setores</h2>
                    <form method="post" class="flex gap-2">
                        <input type="hidden" name="acao" value="criar_setor">
                        <input class="flex-1 border rounded px-3 py-2" type="text" name="setor_nome" placeholder="Nome do setor" required>
                        <button class="bg-blue-600 hover:bg-blue-700 text-white rounded px-3 py-2" type="submit">Adicionar</button>
                    </form>

                    <div class="overflow-x-auto">
                        <table class="w-full text-xs">
                            <thead class="bg-slate-100">
                                <tr>
                                    <th class="text-left p-2">ID</th>
                                    <th class="text-left p-2">Nome</th>
                                    <th class="text-left p-2">Ativo</th>
                                    <th class="text-left p-2">Salvar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($setoresGerencial as $setor): ?>
                                    <tr class="border-t">
                                        <form method="post">
                                            <td class="p-2"><?= (int)$setor['id'] ?><input type="hidden" name="acao" value="atualizar_setor"><input type="hidden" name="setor_id" value="<?= (int)$setor['id'] ?>"></td>
                                            <td class="p-2"><input class="border rounded px-2 py-1 w-full" type="text" name="setor_nome" value="<?= htmlspecialchars((string)$setor['nome'], ENT_QUOTES, 'UTF-8') ?>" required></td>
                                            <td class="p-2">
                                                <select class="border rounded px-2 py-1" name="setor_ativo">
                                                    <option value="1" <?= (int)$setor['ativo'] === 1 ? 'selected' : '' ?>>sim</option>
                                                    <option value="0" <?= (int)$setor['ativo'] === 0 ? 'selected' : '' ?>>nao</option>
                                                </select>
                                            </td>
                                            <td class="p-2"><button class="bg-emerald-600 hover:bg-emerald-700 text-white px-2 py-1 rounded" type="submit">Salvar</button></td>
                                        </form>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>
        <?php endif; ?>
    </main>
</body>
</html>
