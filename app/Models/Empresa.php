<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Empresa extends Model
{
    use HasFactory;

    public const NIVEL_CLIENTE_FINAL = 1;

    public const NIVEL_REVENDA = 2;
    
    protected $table = 'empresa'; 

    protected $fillable = [
        'codigo',
        'nome',
        'razaosocial',
        'cnpj_cpf',
        'email',
        'fone',
        'password',
        'api_token',
        'nivel_acesso',
        'revenda_id',
        'endereco',
        'bairro',
        'numero',
        'cep',
        'fantasia',
        'urlimagem'
    ];

    protected $hidden = [
        'password',
        'api_token',
    ];

    protected $casts = [
        'nivel_acesso' => 'integer',
        'revenda_id' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (Empresa $empresa) {
            if (empty($empresa->codigo)) {
                $empresa->codigo = self::gerarCodigoAutomatico();
            }

            if (empty($empresa->api_token)) {
                $empresa->api_token = Str::random(60);
            }
        });
    }

    private static function gerarCodigoAutomatico(): string
    {
        $ultimoCodigo = DB::table('empresa')
            ->lockForUpdate()
            ->whereRaw("codigo REGEXP '^[0-9]+$'")
            ->selectRaw('MAX(CAST(codigo AS UNSIGNED)) as ultimo')
            ->value('ultimo');

        $proximoCodigo = ((int) $ultimoCodigo) + 1;

        return (string) $proximoCodigo;
    }

    /**
     * Uma Empresa TEM MUITOS registros de detalhes de produto (Produtodnm).
     */
    public function detalhes(): HasMany
    {
        return $this->hasMany(Produtodnm::class); 
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function departamentos(): HasMany
    {
        return $this->hasMany(Departamento::class);
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    public function configuracoes(): HasMany
    {
        return $this->hasMany(Configuracao::class);
    }

    public function produtos(): HasMany
    {
        return $this->hasMany(Produto::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function revenda(): BelongsTo
    {
        return $this->belongsTo(self::class, 'revenda_id');
    }

    public function clientesRevenda(): HasMany
    {
        return $this->hasMany(self::class, 'revenda_id');
    }

    public function isRevenda(): bool
    {
        return (int) $this->nivel_acesso === self::NIVEL_REVENDA;
    }

    public function isClienteFinal(): bool
    {
        return (int) $this->nivel_acesso === self::NIVEL_CLIENTE_FINAL;
    }

    public function templates(): HasMany
    {
        return $this->hasMany(Template::class);
    }
}
