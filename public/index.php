<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

$authController = null;
$processoController = null;
$erro = '';
$sucesso = '';
$resultadoConsulta = [];
$cpfConsulta = '';
$acaoRealizada = ''; // Variável extra para saber onde exibir o erro

try {
    $authController = new AuthController();
    $processoController = new ProcessoController();
} catch (Throwable $e) {
    $erro = 'Banco de dados indisponível no momento. Inicie o MySQL no XAMPP e tente novamente.';
}

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($authController === null || $processoController === null) {
        $erro = 'Não foi possível processar a solicitação sem conexão com o banco.';
    }

    $acao = $_POST['acao'] ?? '';
    $acaoRealizada = $acao;

    if ($acao === 'login' && $authController !== null) {
        $email = trim((string)($_POST['email'] ?? ''));
        $senha = (string)($_POST['senha'] ?? '');

        if ($authController->login($email, $senha)) {
            header('Location: dashboard.php');
            exit;
        }

        $erro = 'Credenciais inválidas.';
    }

    if ($acao === 'consulta_publica' && $processoController !== null) {
        $cpfConsulta = (string)($_POST['cpf_consulta'] ?? '');
        $resultadoConsulta = $processoController->consultaPublica($cpfConsulta);

        if ($resultadoConsulta === []) {
            $erro = 'Nenhum processo encontrado para o CPF informado.';
        } else {
            $sucesso = 'Consulta realizada com sucesso.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SisProt - Acesso e Consulta</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col items-center justify-center p-4 md:p-8">

    <!-- Header / Branding -->
    <div class="mb-8 text-center w-full max-w-5xl">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 text-white mb-4 shadow-lg shadow-blue-600/30">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
        </div>
        <h1 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">SisProt</h1>
        <p class="text-slate-500 mt-2 font-medium">Controle institucional de protocolos e tramitações</p>
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-5xl">
        
        <!-- Alertas Globais -->
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

        <!-- Grid Layout for Cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 lg:gap-8 items-start">
            
            <!-- Login Interno -->
            <section class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 hover:shadow-md transition-shadow">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-slate-800">Acesso Restrito</h2>
                    <p class="text-sm text-slate-500 mt-1">Entre com suas credenciais de servidor.</p>
                </div>
                
                <form method="post" class="space-y-4">
                    <input type="hidden" name="acao" value="login">
                    
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">E-mail Corporativo</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input type="email" name="email" placeholder="nome@instituicao.gov.br" required 
                                class="pl-10 w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:bg-white outline-none transition-all sm:text-sm">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Senha</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" name="senha" placeholder="••••••••" required 
                                class="pl-10 w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-blue-600 focus:border-blue-600 focus:bg-white outline-none transition-all sm:text-sm">
                        </div>
                    </div>

                    <button type="submit" class="mt-2 w-full flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 px-4 rounded-xl transition-colors shadow-sm focus:ring-4 focus:ring-blue-600/20 outline-none">
                        <span>Entrar no Sistema</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </button>
                </form>
            </section>

            <!-- Consulta Pública -->
            <section class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 md:p-8 hover:shadow-md transition-shadow">
                <div class="mb-6">
                    <h2 class="text-xl font-semibold text-slate-800">Consulta Pública</h2>
                    <p class="text-sm text-slate-500 mt-1">Acompanhe seus protocolos vinculados ao seu CPF.</p>
                </div>

                <form method="post" class="flex flex-col sm:flex-row gap-3">
                    <input type="hidden" name="acao" value="consulta_publica">
                    
                    <div class="relative flex-grow">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-slate-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <input type="text" name="cpf_consulta" placeholder="Digite apenas números" 
                            value="<?= htmlspecialchars($cpfConsulta, ENT_QUOTES, 'UTF-8') ?>" required 
                            class="pl-10 w-full px-4 py-2.5 bg-slate-50 border border-slate-300 text-slate-900 rounded-xl focus:ring-2 focus:ring-slate-600 focus:border-slate-600 focus:bg-white outline-none transition-all sm:text-sm">
                    </div>
                    
                    <button type="submit" class="flex-shrink-0 bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 px-6 rounded-xl transition-colors shadow-sm focus:ring-4 focus:ring-slate-800/20 outline-none">
                        Consultar
                    </button>
                </form>

                <!-- Tabela de Resultados -->
                <?php if ($resultadoConsulta !== []): ?>
                    <div class="mt-8 border border-slate-200 rounded-xl overflow-hidden bg-white">
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-slate-600">
                                <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th scope="col" class="px-5 py-3.5 font-semibold">Protocolo</th>
                                        <th scope="col" class="px-5 py-3.5 font-semibold">Assunto</th>
                                        <th scope="col" class="px-5 py-3.5 font-semibold">Status</th>
                                        <th scope="col" class="px-5 py-3.5 font-semibold">Criado em</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    <?php foreach ($resultadoConsulta as $item): ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-5 py-4 font-medium text-slate-900">
                                                <?= htmlspecialchars((string)$item['numero_protocolo'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td class="px-5 py-4 text-slate-700">
                                                <?= htmlspecialchars((string)$item['assunto'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                            <td class="px-5 py-4">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                                                    <?= htmlspecialchars((string)$item['status'], ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            </td>
                                            <td class="px-5 py-4 text-slate-500 whitespace-nowrap">
                                                <?= htmlspecialchars((string)$item['created_at'], ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        
        <!-- Footer simples -->
        <p class="text-center text-xs text-slate-400 mt-10">
            &copy; <?= date('Y') ?> Departamento de Tecnologia e Informação
        </p>
    </div>

</body>
</html>
