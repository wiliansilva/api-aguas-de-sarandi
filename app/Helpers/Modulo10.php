<?php

namespace App\Helpers;

class Modulo10
{
    /**
     * Calcula o dígito verificador pelo Módulo 10 FEBRABAN.
     *
     * Algoritmo:
     * 1. Multiplica os dígitos da direita para a esquerda, alternando 2 e 1
     * 2. Se o resultado for > 9, soma os dígitos (ex: 14 → 1+4 = 5)
     * 3. Soma todos os resultados
     * 4. DV = 10 - (soma % 10). Se resultado for 10, DV = 0
     *
     * @param  string  $numero  Sequência numérica sem formatação
     * @return int
     */
    public static function calcular(string $numero): int
    {
        $soma        = 0;
        $multiplicador = 2;

        // Percorre da direita para a esquerda
        for ($i = strlen($numero) - 1; $i >= 0; $i--) {
            $resultado = (int) $numero[$i] * $multiplicador;

            // Se > 9, soma os dígitos
            if ($resultado > 9) {
                $resultado = intdiv($resultado, 10) + ($resultado % 10);
            }

            $soma += $resultado;

            // Alterna entre 2 e 1
            $multiplicador = $multiplicador === 2 ? 1 : 2;
        }

        $dv = 10 - ($soma % 10);

        return $dv === 10 ? 0 : $dv;
    }
}