<?php

namespace App\Services\Api;

use App\Models\Empresa;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmpresaAuthService
{
    private ?bool $hasAtivoColumn = null;

    public function normalizeDocumento(string $documento): string
    {
        return preg_replace('/\D/', '', $documento) ?? '';
    }

    public function findByDocumento(string $documento): ?Empresa
    {
        $documentoNormalizado = $this->normalizeDocumento($documento);

        if ($documentoNormalizado === '') {
            return null;
        }

        $empresa = Empresa::query()
            ->whereIn('cnpj_cpf', [$documento, $documentoNormalizado])
            ->first();

        if ($empresa) {
            return $empresa;
        }

        return Empresa::query()
            ->cursor()
            ->first(function (Empresa $empresa) use ($documentoNormalizado) {
                return $this->normalizeDocumento((string) $empresa->cnpj_cpf) === $documentoNormalizado;
            });
    }

    public function attemptLogin(string $documento, string $senha): ?Empresa
    {
        $empresa = $this->findByDocumento($documento);

        if (! $empresa || ! $empresa->password) {
            return null;
        }

        if (! Hash::check($senha, (string) $empresa->password)) {
            return null;
        }

        if ($this->empresaInativa($empresa)) {
            return null;
        }

        if (! $empresa->api_token) {
            $empresa->api_token = Str::random(60);
            $empresa->save();
        }

        return $empresa;
    }

    public function refreshToken(Empresa $empresa): Empresa
    {
        $empresa->api_token = Str::random(60);
        $empresa->save();

        return $empresa->fresh();
    }

    public function findByToken(string $token): ?Empresa
    {
        $empresa = Empresa::query()->where('api_token', $token)->first();

        if (! $empresa || $this->empresaInativa($empresa)) {
            return null;
        }

        return $empresa;
    }

    private function empresaInativa(Empresa $empresa): bool
    {
        if ($this->hasAtivoColumn === null) {
            $this->hasAtivoColumn = Schema::hasColumn('empresa', 'ativo');
        }

        if (! $this->hasAtivoColumn) {
            return false;
        }

        return (int) ($empresa->ativo ?? 1) !== 1;
    }
}
