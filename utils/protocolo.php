<?php

declare(strict_types=1);

function gerarProtocolo(int $sequencial): string
{
    $prefixo = date('Y.m');
    $seq = str_pad((string)$sequencial, 4, '0', STR_PAD_LEFT);

    return $prefixo . '.' . $seq;
}
