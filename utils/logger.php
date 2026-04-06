<?php

declare(strict_types=1);

function registrarLog(string $evento, array $contexto = []): void
{
    $baseDir = dirname(__DIR__) . '/storage/logs';

    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    $registro = [
        'data_hora' => date('Y-m-d H:i:s'),
        'evento' => $evento,
        'contexto' => $contexto,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    ];

    $linha = json_encode($registro, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($linha === false) {
        return;
    }

    file_put_contents($baseDir . '/app.log', $linha . PHP_EOL, FILE_APPEND);
}
