<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const DEFAULT_ADMIN_EMAIL = 'elizeudorna01@gmail.com';

    public const DEFAULT_ADMIN_DOCUMENT = '97779474100';

    public const MENU_CADASTRO_PUBLICO = 'cadastro_publico';

    public const MENU_PRODUTOS = 'produtos';

    public const MENU_EMPRESAS = 'empresas';

    public const MENU_DEPARTAMENTOS = 'departamentos';

    public const MENU_GRUPOS = 'grupos';

    public const MENU_CONFIGURACAO = 'configuracao';

    public const MENU_TOKEN_API = 'token_api';

    public const MENU_ATIVAR_TV = 'ativar_tv';

    public const MENU_GESTAO_TVS = 'gestao_tvs';

    public const MENU_EDITOR_TEMPLATE = 'editor_template';

    public const MENU_GALERIA_NOVA = 'galeria_nova';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'cpf',
        'empresa_id',
        'menu_permissions',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'menu_permissions' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function isDefaultAdmin(): bool
    {
        $defaultAdminEmail = (string) env('DEFAULT_ADMIN_EMAIL', self::DEFAULT_ADMIN_EMAIL);
        $defaultAdminDocument = preg_replace('/\D/', '', (string) env('DEFAULT_ADMIN_DOCUMENT', self::DEFAULT_ADMIN_DOCUMENT));

        return $this->email === $defaultAdminEmail
            && $this->documento() === $defaultAdminDocument;
    }

    public function documento(): string
    {
        return preg_replace('/\D/', '', (string) $this->cpf);
    }

    public static function availableMenuPermissions(): array
    {
        return [
            self::MENU_CADASTRO_PUBLICO => 'Cadastro Público',
            self::MENU_PRODUTOS => 'Produtos',
            self::MENU_EMPRESAS => 'Empresas',
            self::MENU_DEPARTAMENTOS => 'Departamentos',
            self::MENU_GRUPOS => 'Grupos',
            self::MENU_CONFIGURACAO => 'Configuração',
            self::MENU_TOKEN_API => 'Gerar Token API',
            self::MENU_ATIVAR_TV => 'Ativar TV',
            self::MENU_GESTAO_TVS => 'Gestão de TVs',
            self::MENU_EDITOR_TEMPLATE => 'Editor de Template',
            self::MENU_GALERIA_NOVA => 'Galeria de Imagem',
        ];
    }

    public function hasMenuAccess(string $menu): bool
    {
        if ($this->isDefaultAdmin()) {
            return true;
        }

        $permissions = $this->menu_permissions;

        if ($permissions === null) {
            return true;
        }

        return in_array($menu, $permissions, true);
    }
}
