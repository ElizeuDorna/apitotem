<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProdutoResource;
use App\Http\Resources\Api\TvProdutoResource;
use App\Models\Configuracao;
use App\Models\Device;
use App\Models\DeviceConfiguration;
use App\Models\DeviceActivation;
use App\Models\Produto;
use App\Models\TemplateItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

class TvController extends Controller
{
    private const ACTIVATION_EXPIRES_SECONDS = 300;
    private const ACTIVATION_CODE_LENGTH = 10;

    #[OA\Post(
        path: '/api/tv/activation-code',
        tags: ['TV'],
        summary: 'Gera código de ativação para TV',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/TvActivationCodePayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Código gerado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function activationCode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_uuid' => ['required', 'string', 'max:100'],
        ]);

        $code = $this->generateUniqueActivationCode();

        DeviceActivation::create([
            'device_uuid' => $validated['device_uuid'],
            'code' => $code,
            'expires_at' => now()->addSeconds(self::ACTIVATION_EXPIRES_SECONDS),
            'activated' => false,
        ]);

        return response()->json([
            'code' => $code,
            'expires_in' => self::ACTIVATION_EXPIRES_SECONDS,
        ]);
    }

    #[OA\Post(
        path: '/api/tv/check-activation',
        tags: ['TV'],
        summary: 'Consulta status de ativação da TV',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/TvCheckActivationPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Status pending ou activated'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function checkActivation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_uuid' => ['required', 'string', 'max:100'],
        ]);

        $activation = DeviceActivation::query()
            ->with('device')
            ->where('device_uuid', $validated['device_uuid'])
            ->latest('id')
            ->first();

        if (! $activation || ! $activation->activated || ! $activation->device) {
            return response()->json([
                'status' => 'pending',
            ]);
        }

        return response()->json([
            'status' => 'activated',
            'token' => $activation->device->token,
        ]);
    }

    #[OA\Post(
        path: '/api/tv/heartbeat',
        tags: ['TV'],
        summary: 'Atualiza last_seen_at da TV',
        requestBody: new OA\RequestBody(
            required: false,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/TvHeartbeatPayload')
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Heartbeat atualizado'),
            new OA\Response(response: 401, description: 'Token inválido'),
            new OA\Response(response: 422, description: 'Token não informado')
        ]
    )]
    public function heartbeat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['nullable', 'string', 'max:100'],
        ]);

        $token = $request->bearerToken() ?: ($validated['token'] ?? null);

        if (! $token) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token não informado.',
            ], 422);
        }

        $device = Device::query()
            ->where('token', $token)
            ->where('ativo', true)
            ->first();

        if (! $device) {
            return response()->json([
                'status' => 'error',
                'message' => 'Dispositivo não encontrado.',
            ], 401);
        }

        $device->last_seen_at = now();
        $device->save();

        return response()->json([
            'status' => 'ok',
            'last_seen_at' => $device->last_seen_at,
        ]);
    }

    #[OA\Get(
        path: '/api/tv/produtos',
        tags: ['TV'],
        summary: 'Lista produtos da empresa vinculada ao dispositivo',
        security: [['DeviceBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Token do device inválido')
        ]
    )]
    public function produtos(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'data' => [
                    'produtos' => [],
                ],
                'meta' => [
                    'total_produtos' => 0,
                ],
            ], 401);
        }

        $device = Device::query()
            ->where('token', $token)
            ->where('ativo', true)
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'data' => [
                    'produtos' => [],
                ],
                'meta' => [
                    'total_produtos' => 0,
                ],
            ], 401);
        }

        $empresaId = (int) $device->empresa_id;
        $configuration = $this->resolveDeviceConfiguration($device->id);

        $cacheSeconds = max(5, (int) $configuration->atualizar_produtos_segundos);

        $produtos = Cache::remember(
            "tv:produtos:empresa:{$empresaId}",
            now()->addSeconds($cacheSeconds),
            function () use ($empresaId) {
                return Produto::query()
                    ->with(['departamento:id,nome', 'grupo:id,nome,departamento_id'])
                    ->where('empresa_id', $empresaId)
                    ->orderBy('NOME')
                    ->get();
            }
        );

        return response()->json([
            'success' => true,
            'data' => [
                'produtos' => TvProdutoResource::collection($produtos),
            ],
            'meta' => [
                'total_produtos' => $produtos->count(),
            ],
        ]);
    }

    #[OA\Get(
        path: '/api/tv/bootstrap',
        tags: ['TV'],
        summary: 'Retorna bootstrap da TV com dados de device, configuração e template',
        security: [['DeviceBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Token do device inválido')
        ]
    )]
    public function bootstrap(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');
        $configuration = $this->resolveDeviceConfiguration($device->id);
        $template = $configuration->template;
        $items = $template ? $template->items()->orderBy('ordem')->get() : collect();

        return response()->json([
            'status' => 'ok',
            'device' => [
                'id' => $device->id,
                'nome' => $device->nome,
                'local' => $device->local,
                'empresa_id' => $device->empresa_id,
                'last_seen_at' => $device->last_seen_at,
            ],
            'empresa' => [
                'id' => $device->empresa?->id,
                'nome' => $device->empresa?->NOME,
                'cnpj_cpf' => $device->empresa?->CNPJ_CPF,
            ],
            'configuracao' => [
                'id' => $configuration->id,
                'template_id' => $configuration->template_id,
                'atualizar_produtos_segundos' => $configuration->atualizar_produtos_segundos,
                'volume' => $configuration->volume,
                'orientacao' => $configuration->orientacao,
            ],
            'template' => $template ? [
                'id' => $template->id,
                'nome' => $template->nome,
                'tipo_layout' => $template->tipo_layout,
            ] : null,
            'items' => $items->map(fn ($item) => [
                'id' => $item->id,
                'tipo' => $item->tipo,
                'ordem' => $item->ordem,
                'conteudo' => $item->conteudo,
                'config_json' => $item->config_json,
            ])->values(),
        ]);
    }

    #[OA\Get(
        path: '/api/tv/midias',
        tags: ['TV'],
        summary: 'Retorna mídias do template da TV (vídeos, imagens, banners)',
        security: [['DeviceBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Token do device inválido')
        ]
    )]
    public function midias(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');
        $configuration = $this->resolveDeviceConfiguration($device->id);

        if (! $configuration->template_id) {
            return response()->json([
                'status' => 'ok',
                'videos' => [],
                'imagens' => [],
                'banners' => [],
            ]);
        }

        $items = TemplateItem::query()
            ->where('template_id', $configuration->template_id)
            ->whereIn('tipo', ['video', 'imagem', 'banner'])
            ->orderBy('ordem')
            ->get();

        $map = static fn ($item) => [
            'id' => $item->id,
            'ordem' => $item->ordem,
            'conteudo' => $item->conteudo,
            'config_json' => $item->config_json,
        ];

        return response()->json([
            'status' => 'ok',
            'videos' => $items->where('tipo', 'video')->map($map)->values(),
            'imagens' => $items->where('tipo', 'imagem')->map($map)->values(),
            'banners' => $items->where('tipo', 'banner')->map($map)->values(),
        ]);
    }

    #[OA\Get(
        path: '/api/tv/ofertas',
        tags: ['TV'],
        summary: 'Lista ofertas da empresa vinculada ao dispositivo',
        security: [['DeviceBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'OK'),
            new OA\Response(response: 401, description: 'Token do device inválido')
        ]
    )]
    public function ofertas(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $ofertas = Produto::query()
            ->with(['departamento:id,nome', 'grupo:id,nome,departamento_id'])
            ->where('empresa_id', $device->empresa_id)
            ->whereNotNull('OFERTA')
            ->where('OFERTA', '>', 0)
            ->orderBy('NOME')
            ->get();

        return response()->json([
            'status' => 'ok',
            'empresa_id' => $device->empresa_id,
            'device_id' => $device->id,
            'total' => $ofertas->count(),
            'dados' => ProdutoResource::collection($ofertas),
        ]);
    }

    public function webScreenConfig(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'message' => 'Token não informado.',
            ], 401);
        }

        $device = Device::query()
            ->where('token', $token)
            ->where('ativo', true)
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo inválido.',
            ], 401);
        }

        $config = Configuracao::query()->firstOrCreate([
            'empresa_id' => $device->empresa_id,
        ], []);

        $playlist = collect($config->videoPlaylist ?? [])
            ->map(function ($item) {
                return [
                    'url' => (string) ($item['url'] ?? ''),
                    'muted' => (bool) ($item['muted'] ?? false),
                    'active' => (bool) ($item['active'] ?? true),
                    'fullscreen' => (bool) ($item['fullscreen'] ?? false),
                    'durationSeconds' => (int) ($item['durationSeconds'] ?? 0),
                    'heightPx' => (int) ($item['heightPx'] ?? 0),
                ];
            })
            ->filter(fn ($item) => $item['url'] !== '')
            ->values();

        return response()->json([
            'success' => true,
            'data' => [
                'videoUrl' => $config->videoUrl,
                'videoMuted' => (bool) $config->videoMuted,
                'showVideoPanel' => (bool) ($config->showVideoPanel ?? true),
                'videoPlaylist' => $playlist,
                'appBackgroundColor' => $config->appBackgroundColor,
                'productsPanelBackgroundColor' => (string) ($config->productsPanelBackgroundColor ?? '#0f172a'),
                'listBorderColor' => (string) ($config->listBorderColor ?? '#334155'),
                'videoBackgroundColor' => (string) ($config->videoBackgroundColor ?? '#000000'),
                'rowBackgroundColor' => $config->rowBackgroundColor,
                'borderColor' => $config->borderColor,
                'isRowBorderTransparent' => (bool) ($config->isRowBorderTransparent ?? false),
                'priceColor' => $config->priceColor,
                'showBorder' => (bool) $config->showBorder,
                'showTitle' => (bool) ($config->showTitle ?? true),
                'useGradient' => (bool) $config->useGradient,
                'gradientStartColor' => $config->gradientStartColor,
                'gradientEndColor' => $config->gradientEndColor,
                'showBackgroundImage' => (bool) ($config->showBackgroundImage ?? false),
                'isProductsPanelTransparent' => (bool) ($config->isProductsPanelTransparent ?? false),
                'isListBorderTransparent' => (bool) ($config->isListBorderTransparent ?? false),
                'backgroundImageUrl' => (string) ($config->backgroundImageUrl ?? ''),
                'imageWidth' => (int) ($config->imageWidth ?? 56),
                'imageHeight' => (int) ($config->imageHeight ?? 56),
                'listFontSize' => (int) ($config->listFontSize ?? 16),
                'groupLabelFontSize' => (int) ($config->groupLabelFontSize ?? 14),
                'groupLabelColor' => (string) ($config->groupLabelColor ?? '#cbd5e1'),
                'isPaginationEnabled' => (bool) $config->isPaginationEnabled,
                'pageSize' => (int) ($config->pageSize ?? 10),
                'paginationInterval' => (int) ($config->paginationInterval ?? 5),
            ],
        ]);
    }

    private function generateUniqueActivationCode(): string
    {
        for ($attempt = 1; $attempt <= 50; $attempt++) {
            $code = Str::upper(Str::random(self::ACTIVATION_CODE_LENGTH));

            $inUse = DeviceActivation::query()
                ->where('code', $code)
                ->where('activated', false)
                ->where('expires_at', '>', now())
                ->exists();

            if (! $inUse) {
                return $code;
            }
        }

        abort(503, 'Não foi possível gerar um código de ativação no momento.');
    }

    private function resolveDeviceConfiguration(int $deviceId): DeviceConfiguration
    {
        return DeviceConfiguration::query()->firstOrCreate(
            ['device_id' => $deviceId],
            [
                'atualizar_produtos_segundos' => 30,
                'volume' => 50,
                'orientacao' => 'landscape',
            ]
        )->load('template');
    }
}
