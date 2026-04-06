<?php

declare(strict_types=1);

function validarCPF(string $cpf): bool
{
    $cpf = preg_replace('/\D/', '', $cpf) ?? '';
    $valido = true;

    if (strlen($cpf) !== 11) {
        $valido = false;
    }

    if ($valido && preg_match('/^(\d)\1{10}$/', $cpf) === 1) {
        $valido = false;
    }

    if ($valido) {
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;

            for ($c = 0; $c < $t; $c++) {
                $soma += (int)$cpf[$c] * (($t + 1) - $c);
            }

            $digito = ((10 * $soma) % 11) % 10;

            if ((int)$cpf[$t] !== $digito) {
                $valido = false;
                break;
            }
        }
    }

    return $valido;
}
