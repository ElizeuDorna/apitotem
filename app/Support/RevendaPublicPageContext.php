<?php

namespace App\Support;

use App\Models\Empresa;
use App\Models\User;

class RevendaPublicPageContext
{
    public static function resolveTargetEmpresa(User $user): ?Empresa
    {
        if ($user->isDefaultAdmin()) {
            $empresa = EmpresaContext::activeEmpresa($user);

            return $empresa && $empresa->isRevenda() ? $empresa : null;
        }

        $empresa = $user->empresa;

        return $empresa && $empresa->isRevenda() ? $empresa : null;
    }

    public static function canEdit(User $user): bool
    {
        $empresa = self::resolveTargetEmpresa($user);

        if (! $empresa) {
            return false;
        }

        if ($user->isDefaultAdmin()) {
            return true;
        }

        return (bool) ($empresa->public_page_enabled ?? false);
    }
}