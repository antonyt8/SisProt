<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$authController = null;
$erro = '';
$sucesso = '';
$tokenGerado = '';

try {
    $authController = new AuthController();
} catch (Throwable $e) {
    $erro = 'Banco de dados indisponivel no momento. Inicie o MySQL no XAMPP e tente novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string)($_POST['email_recuperacao'] ?? ''));

    if ($authController === null) {
        $erro = 'Nao foi possivel processar a solicitacao sem conexao com o banco.';
    } else {
        $resultado = $authController->solicitarRecuperacaoSenha($email);

        if ($resultado['ok']) {
            $sucesso = (string)$resultado['mensagem'];
            $tokenGerado = (string)($resultado['token'] ?? '');
        } else {
            $erro = (string)$resultado['mensagem'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SisProt - Recuperacao de Senha</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-4">
    <main class="max-w-xl mx-auto mt-8 bg-white border rounded p-5 space-y-4">
        <h1 class="text-xl font-bold">Recuperacao de senha</h1>
        <p class="text-sm text-slate-600">Informe o e-mail para gerar um token temporario.</p>

        <?php if ($erro !== ''): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded p-3 text-sm"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded p-3 text-sm">
                <?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?>
                <?php if ($tokenGerado !== ''): ?>
                    <div class="mt-2 break-all"><strong>Token:</strong> <?= htmlspecialchars($tokenGerado, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="space-y-3">
            <label for="email_recuperacao" class="block text-sm font-medium">E-mail</label>
            <input id="email_recuperacao" class="w-full border rounded px-3 py-2" type="email" name="email_recuperacao" placeholder="email@instituicao.gov.br" required>
            <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white rounded px-3 py-2" type="submit">Gerar token</button>
        </form>

        <div class="flex gap-2">
            <a class="text-center flex-1 border rounded px-3 py-2 text-sm" href="index.php">Voltar ao login</a>
            <a class="text-center flex-1 border rounded px-3 py-2 text-sm border-emerald-300 text-emerald-700" href="redefinir-senha.php">Ir para redefinir senha</a>
        </div>
    </main>
</body>
</html>
