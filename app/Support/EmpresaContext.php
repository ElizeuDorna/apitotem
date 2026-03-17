<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\User;

class EmpresaContext
{
    public const SESSION_KEY = 'revenda.empresa_ativa_id';
    public const ADMIN_SESSION_KEY = 'admin.empresa_ativa_id';

    public static function requiresSelection(User $user): bool
    {
        $empresaVinculada = $user->empresa;

        return $empresaVinculada !== null && $empresaVinculada->isRevenda();
    }

    public static function resolveEmpresaIdForUser(User $user): ?int
    {
        if ($user->isDefaultAdmin()) {
            $empresaAtivaId = (int) session(self::ADMIN_SESSION_KEY);

            if ($empresaAtivaId <= 0) {
                return null;
            }

            $exists = Empresa::query()
                ->where('id', $empresaAtivaId)
                ->exists();

            return $exists ? $empresaAtivaId : null;
        }

        $empresaVinculada = $user->empresa;

        if (! $empresaVinculada) {
            return null;
        }

        if (! $empresaVinculada->isRevenda()) {
            return (int) $empresaVinculada->id;
        }

        $empresaAtivaId = (int) session(self::SESSION_KEY);

        if ($empresaAtivaId <= 0) {
            return null;
        }

        $isPermitida = Empresa::query()
            ->where('id', $empresaAtivaId)
            ->where('revenda_id', $empresaVinculada->id)
            ->exists();

        return $isPermitida ? $empresaAtivaId : null;
    }

    public static function requireEmpresaId(User $user): int
    {
        $empresaId = self::resolveEmpresaIdForUser($user);

        if (! $empresaId) {
            if ($user->isDefaultAdmin()) {
                abort(403, 'Selecione uma empresa ativa em Empresas para continuar.');
            }
            abort(403, 'Selecione uma empresa para continuar.');
        }

        return $empresaId;
    }

    public static function activeEmpresa(User $user): ?Empresa
    {
        $empresaId = self::resolveEmpresaIdForUser($user);

        if (! $empresaId) {
            return null;
        }

        return Empresa::query()->find($empresaId);
    }

    public static function setActiveEmpresa(User $user, Empresa $empresa): bool
    {
        if ($user->isDefaultAdmin()) {
            session([self::ADMIN_SESSION_KEY => (int) $empresa->id]);
            return true;
        }

        $empresaVinculada = $user->empresa;

        if (! $empresaVinculada || ! $empresaVinculada->isRevenda()) {
            return false;
        }

        $isPermitida = (int) $empresa->revenda_id === (int) $empresaVinculada->id;

        if (! $isPermitida) {
            return false;
        }

        session([self::SESSION_KEY => (int) $empresa->id]);

        return true;
    }

    public static function clearActiveEmpresa(): void
    {
        session()->forget(self::SESSION_KEY);
        session()->forget(self::ADMIN_SESSION_KEY);
    }
}
