<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

requireAuth();

$authController = null;
$processoController = null;

$erro = '';
$sucesso = '';
$userId = (int)$_SESSION['user_id'];

try {
    $authController = new AuthController();
    $processoController = new ProcessoController();
} catch (Throwable $e) {
    $erro = 'Banco de dados indisponível no momento. Inicie o MySQL no XAMPP e recarregue a página.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'logout' && $authController !== null) {
        $authController->logout();
        header('Location: index.php');
        exit;
    }

    if ($acao === 'cadastrar_processo' && $processoController !== null) {
        $resultado = $processoController->cadastrar($_POST, $userId);
        if ($resultado['ok']) {
            $sucesso = $resultado['mensagem'];
        } else {
            $erro = $resultado['mensagem'];
        }
    }

    if ($acao === 'tramitar_processo' && $processoController !== null) {
        $resultado = $processoController->tramitar($_POST, $userId);
        if ($resultado['ok']) {
            $sucesso = $resultado['mensagem'];
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}

$setores = $processoController !== null ? $processoController->listarSetores() : [];
$processos = $processoController !== null ? $processoController->listarProcessos() : [];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SisProt - Painel Interno</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col">

    <!-- Topbar / Navbar -->
    <nav class="bg-slate-900 text-white shadow-md sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-3">
                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-blue-600 text-white">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="font-bold text-xl tracking-tight">SisProt <span class="text-slate-400 text-sm font-normal ml-2 hidden sm:inline">Painel Interno</span></span>
                </div>
                <form method="post" class="m-0">
                    <input type="hidden" name="acao" value="logout">
                    <button type="submit" class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors focus:ring-4 focus:ring-red-600/30 outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Alertas de Feedback Globais -->
        <?php if ($erro !== ''): ?>
            <div class="mb-6 p-4 rounded-xl flex items-start gap-3 bg-red-50 text-red-800 border border-red-200 shadow-sm animate-pulse">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 mt-0.5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="mb-6 p-4 rounded-xl flex items-start gap-3 bg-emerald-50 text-emerald-800 border border-emerald-200 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0 mt-0.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <p class="font-medium text-sm"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        <?php endif; ?>

        <!-- Formulários Grid (2 colunas) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            
            <!-- Card Novo Processo -->
            <section class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 hover:shadow-md transition-shadow">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Novo Processo
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Cadastre uma nova solicitação no sistema.</p>
                </div>

                <form method="post" class="space-y-4">
                    <input type="hidden" name="acao" value="cadastrar_processo">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Nome do Interessado</label>
                            <input type="text" name="nome" placeholder="Nome completo" required 
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:bg-white outline-none transition-all sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">CPF</label>
                            <input type="text" name="cpf" placeholder="Apenas números" required 
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:bg-white outline-none transition-all sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Assunto / Motivo</label>
                        <input type="text" name="assunto" placeholder="Resumo da solicitação" required 
                            class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:bg-white outline-none transition-all sm:text-sm">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Setor Inicial</label>
                        <select name="setor_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:bg-white outline-none transition-all sm:text-sm">
                            <option value="" disabled selected>Selecione para onde enviar...</option>
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?= (int)$setor['id'] ?>"><?= htmlspecialchars((string)$setor['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="mt-4 w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors shadow-sm focus:ring-4 focus:ring-blue-600/20 outline-none">
                        Cadastrar Processo
                    </button>
                </form>
            </section>

            <!-- Card Tramitar Processo -->
            <section class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 hover:shadow-md transition-shadow">
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                        </svg>
                        Tramitar Processo
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Mova um processo para outro setor ou anexe um despacho.</p>
                </div>

                <form method="post" class="space-y-4">
                    <input type="hidden" name="acao" value="tramitar_processo">
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">ID do Processo</label>
                            <input type="number" name="processo_id" placeholder="Ex: 15" min="1" required 
                                class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:bg-white outline-none transition-all sm:text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Setor Destino</label>
                            <select name="destino_setor_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:bg-white outline-none transition-all sm:text-sm">
                                <option value="" disabled selected>Mover para...</option>
                                <?php foreach ($setores as $setor): ?>
                                    <option value="<?= (int)$setor['id'] ?>"><?= htmlspecialchars((string)$setor['nome'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Despacho / Parecer</label>
                        <textarea name="despacho" rows="3" placeholder="Insira o texto do despacho aqui..." required 
                            class="w-full px-4 py-2 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:bg-white outline-none transition-all sm:text-sm resize-y"></textarea>
                    </div>

                    <button type="submit" class="mt-4 w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors shadow-sm focus:ring-4 focus:ring-slate-800/20 outline-none">
                        Realizar Tramitação
                    </button>
                </form>
            </section>

        </div>

        <!-- Tabela de Processos Ativos -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-6 border-b border-slate-200 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Acompanhamento de Processos
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Lista geral de todos os processos cadastrados no sistema.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-slate-600 whitespace-nowrap">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th scope="col" class="px-6 py-4 font-semibold">ID</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Protocolo</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Interessado</th>
                            <th scope="col" class="px-6 py-4 font-semibold">CPF</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Assunto</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Status</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Setor Atual</th>
                            <th scope="col" class="px-6 py-4 font-semibold">Criado em</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <?php if (count($processos) === 0): ?>
                            <tr>
                                <td colspan="8" class="px-6 py-8 text-center text-slate-500">
                                    Nenhum processo encontrado no momento.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($processos as $processo): ?>
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4 font-medium text-slate-900">#<?= (int)$processo['id'] ?></td>
                                    <td class="px-6 py-4 font-mono text-xs font-semibold bg-slate-100 rounded-md inline-block mt-3 mb-3 ml-2 border border-slate-200 text-slate-700">
                                        <?= htmlspecialchars((string)$processo['numero_protocolo'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-700 font-medium">
                                        <?= htmlspecialchars((string)$processo['interessado_nome'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-500">
                                        <?= htmlspecialchars((string)$processo['interessado_cpf'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-700 max-w-xs truncate" title="<?= htmlspecialchars((string)$processo['assunto'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars((string)$processo['assunto'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border
                                            <?= strtolower($processo['status']) === 'concluído' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200' ?>
                                        ">
                                            <?= htmlspecialchars((string)$processo['status'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-medium text-slate-800">
                                        <?= htmlspecialchars((string)($processo['setor_nome'] ?? 'Sem setor'), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="px-6 py-4 text-slate-400 text-xs">
                                        <?= htmlspecialchars((string)$processo['created_at'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        
        <!-- Footer simples -->
        <p class="text-center text-xs text-slate-400 mt-10">
            &copy; <?= date('Y') ?> Departamento de Tecnologia e Informação
        </p>
    </main>

</body>
</html>