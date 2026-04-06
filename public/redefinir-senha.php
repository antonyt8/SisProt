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
$token = trim((string)($_GET['token'] ?? ''));

try {
    $authController = new AuthController();
} catch (Throwable $e) {
    $erro = 'Banco de dados indisponivel no momento. Inicie o MySQL no XAMPP e tente novamente.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim((string)($_POST['token_recuperacao'] ?? ''));
    $novaSenha = (string)($_POST['nova_senha'] ?? '');

    if ($authController === null) {
        $erro = 'Nao foi possivel processar a solicitacao sem conexao com o banco.';
    } else {
        $resultado = $authController->redefinirSenhaComToken($token, $novaSenha);

        if ($resultado['ok']) {
            $sucesso = (string)$resultado['mensagem'];
            $token = '';
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
    <title>SisProt - Redefinir Senha</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen p-4">
    <main class="max-w-xl mx-auto mt-8 bg-white border rounded p-5 space-y-4">
        <h1 class="text-xl font-bold">Redefinir senha com token</h1>
        <p class="text-sm text-slate-600">Informe o token recebido e a nova senha de acesso.</p>

        <?php if ($erro !== ''): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 rounded p-3 text-sm"><?= htmlspecialchars($erro, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($sucesso !== ''): ?>
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 rounded p-3 text-sm"><?= htmlspecialchars($sucesso, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" class="space-y-3">
            <label for="token_recuperacao" class="block text-sm font-medium">Token</label>
            <input id="token_recuperacao" class="w-full border rounded px-3 py-2" type="text" name="token_recuperacao" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>" required>

            <label for="nova_senha" class="block text-sm font-medium">Nova senha</label>
            <input id="nova_senha" class="w-full border rounded px-3 py-2" type="password" name="nova_senha" minlength="6" required>

            <button class="w-full bg-emerald-600 hover:bg-emerald-700 text-white rounded px-3 py-2" type="submit">Redefinir senha</button>
        </form>

        <div class="flex gap-2">
            <a class="text-center flex-1 border rounded px-3 py-2 text-sm" href="index.php">Voltar ao login</a>
            <a class="text-center flex-1 border rounded px-3 py-2 text-sm border-indigo-300 text-indigo-700" href="recuperar-senha.php">Precisa de token?</a>
        </div>
    </main>
</body>
</html>
