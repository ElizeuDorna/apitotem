<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpjValido implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $documento = preg_replace('/\D/', '', (string) $value);

        $isValid = $this->validarCpf($documento) || $this->validarCnpj($documento);

        if (! $isValid) {
            $fail('O campo :attribute deve ser um CPF ou CNPJ válido.');
        }
    }

    private function validarCpf(string $cpf): bool
    {
        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += (int) $cpf[$c] * (($t + 1) - $c);
            }

            $d = ((10 * $d) % 11) % 10;
            if ((int) $cpf[$c] !== $d) {
                return false;
            }
        }

        return true;
    }

    private function validarCnpj(string $cnpj): bool
    {
        if (strlen($cnpj) !== 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $pesosPrimeiroDigito = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $pesosSegundoDigito = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $soma = 0;
        for ($i = 0; $i < 12; $i++) {
            $soma += (int) $cnpj[$i] * $pesosPrimeiroDigito[$i];
        }
        $resto = $soma % 11;
        $digito1 = $resto < 2 ? 0 : 11 - $resto;

        if ((int) $cnpj[12] !== $digito1) {
            return false;
        }

        $soma = 0;
        for ($i = 0; $i < 13; $i++) {
            $soma += (int) $cnpj[$i] * $pesosSegundoDigito[$i];
        }
        $resto = $soma % 11;
        $digito2 = $resto < 2 ? 0 : 11 - $resto;

        return (int) $cnpj[13] === $digito2;
    }
}
