<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProdutoResource;
use App\Http\Resources\Api\TvProdutoResource;
use App\Models\Configuracao;
use App\Models\Device;
use App\Models\DeviceConfiguration;
use App\Models\DeviceActivation;
use App\Models\GlobalImageGallery;
use App\Models\Produto;
use App\Models\TemplateItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $screenConfig = Configuracao::query()->firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        $showImage = (bool) ($screenConfig->showImage ?? true);
        $orderMode = (string) ($screenConfig->productListOrderMode ?? 'grupo');
        $alphabeticalDirection = (string) ($screenConfig->productAlphabeticalDirection ?? 'asc');
        $departmentOrder = collect($screenConfig->productDepartmentOrder ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();
        $groupOrder = collect($screenConfig->productGroupOrder ?? [])->map(fn ($id) => (int) $id)->filter()->values()->all();

        $cacheSeconds = max(5, (int) $configuration->atualizar_produtos_segundos);
        $orderSignature = md5(json_encode([
            'mode' => $orderMode,
            'alpha' => $alphabeticalDirection,
            'departments' => $departmentOrder,
            'groups' => $groupOrder,
        ]));

        $produtos = Cache::remember(
            "tv:produtos:empresa:{$empresaId}:ordem:{$orderSignature}",
            now()->addSeconds($cacheSeconds),
            function () use ($empresaId, $orderMode, $alphabeticalDirection, $departmentOrder, $groupOrder) {
                $items = Produto::query()
                    ->with(['departamento:id,nome', 'grupo:id,nome,departamento_id'])
                    ->where('empresa_id', $empresaId)
                    ->get();

                return $this->sortProductsForTv($items, $orderMode, $alphabeticalDirection, $departmentOrder, $groupOrder);
            }
        );

        if (! $showImage) {
            $produtos = $produtos->map(function ($produto) {
                $produto->setAttribute('IMG', null);

                return $produto;
            });
        }

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

        $globalGalleryCode = substr(preg_replace('/\D/', '', (string) ($config->rightSidebarGlobalGalleryCode ?? '')) ?? '', 0, 14);
        $configuredImageUrls = $this->normalizeImageUrlsList((string) ($config->rightSidebarImageUrls ?? ''));
        $resolvedGlobalGalleryUrls = [];

        if ($globalGalleryCode !== '' && $configuredImageUrls === '') {
            $resolvedGlobalGalleryUrls = $this->resolveGlobalGalleryImageUrls($globalGalleryCode);
        }

        $rightSidebarImageUrls = $configuredImageUrls !== ''
            ? $configuredImageUrls
            : (! empty($resolvedGlobalGalleryUrls)
            ? implode("\n", $resolvedGlobalGalleryUrls)
            : '');

        return response()->json([
            'success' => true,
            'data' => [
                'videoUrl' => $config->videoUrl,
                'videoMuted' => (bool) $config->videoMuted,
                'showVideoPanel' => (bool) ($config->showVideoPanel ?? true),
                'showRightSidebarPanel' => (bool) ($config->showRightSidebarPanel ?? true),
                'isMainBorderEnabled' => (bool) ($config->isMainBorderEnabled ?? false),
                'isRoundedCornersEnabled' => (bool) ($config->isRoundedCornersEnabled ?? true),
                'mainBorderColor' => (string) ($config->mainBorderColor ?? '#000000'),
                'mainBorderWidth' => (int) ($config->mainBorderWidth ?? 1),
                'videoPlaylist' => $playlist,
                'appBackgroundColor' => $config->appBackgroundColor,
                'productsPanelBackgroundColor' => (string) ($config->productsPanelBackgroundColor ?? '#0f172a'),
                'listBorderColor' => (string) ($config->listBorderColor ?? '#334155'),
                'listBorderWidth' => (int) ($config->listBorderWidth ?? 1),
                'videoBackgroundColor' => (string) ($config->videoBackgroundColor ?? '#000000'),
                'showRightSidebarBorder' => (bool) ($config->showRightSidebarBorder ?? true),
                'rightSidebarBorderColor' => (string) ($config->rightSidebarBorderColor ?? '#334155'),
                'rightSidebarBorderWidth' => (int) ($config->rightSidebarBorderWidth ?? 1),
                'rightSidebarMediaType' => (string) ($config->rightSidebarMediaType ?? 'video'),
                'rightSidebarGlobalGalleryCode' => $globalGalleryCode,
                'rightSidebarImageUrls' => $rightSidebarImageUrls,
                'rightSidebarImageSchedules' => collect($config->rightSidebarImageSchedules ?? [])->values()->all(),
                'rightSidebarImageInterval' => (int) ($config->rightSidebarImageInterval ?? 8),
                'rightSidebarImageFit' => (string) ($config->rightSidebarImageFit ?? 'scale-down'),
                'rightSidebarImageHeight' => (int) ($config->rightSidebarImageHeight ?? 96),
                'rightSidebarImageWidth' => (int) ($config->rightSidebarImageWidth ?? 0),
                'rightSidebarHybridVideoDuration' => (int) ($config->rightSidebarHybridVideoDuration ?? 2),
                'rightSidebarHybridImageDuration' => (int) ($config->rightSidebarHybridImageDuration ?? 4),
                'productListType' => (string) ($config->productListType ?? '1'),
                'productListLeftGroupIds' => collect($config->productListLeftGroupIds ?? [])->map(fn ($id) => (int) $id)->values()->all(),
                'productListRightGroupIds' => collect($config->productListRightGroupIds ?? [])->map(fn ($id) => (int) $id)->values()->all(),
                'isVideoPanelTransparent' => (bool) ($config->isVideoPanelTransparent ?? false),
                'rowBackgroundColor' => $config->rowBackgroundColor,
                'borderColor' => $config->borderColor,
                'rowBorderWidth' => (int) ($config->rowBorderWidth ?? 1),
                'isRowBorderTransparent' => (bool) ($config->isRowBorderTransparent ?? false),
                'priceColor' => $config->priceColor,
                'showBorder' => (bool) $config->showBorder,
                'showTitle' => (bool) ($config->showTitle ?? true),
                'titleText' => (string) ($config->titleText ?? 'Lista de Produtos (TV)'),
                'isTitleDynamic' => (bool) ($config->isTitleDynamic ?? false),
                'titlePosition' => (string) ($config->titlePosition ?? 'top'),
                'titleFontSize' => (int) ($config->titleFontSize ?? 32),
                'titleFontFamily' => (string) ($config->titleFontFamily ?? 'arial'),
                'titleTextColor' => (string) ($config->titleTextColor ?? '#f8fafc'),
                'titleBackgroundColor' => (string) ($config->titleBackgroundColor ?? '#0f172a'),
                'isTitleBackgroundTransparent' => (bool) ($config->isTitleBackgroundTransparent ?? false),
                'showTitleBorder' => (bool) ($config->showTitleBorder ?? true),
                'showImage' => (bool) ($config->showImage ?? true),
                'isRowRoundedEnabled' => (bool) ($config->isRowRoundedEnabled ?? false),
                'useGradient' => (bool) $config->useGradient,
                'gradientStartColor' => $config->gradientStartColor,
                'gradientEndColor' => $config->gradientEndColor,
                'showBackgroundImage' => (bool) ($config->showBackgroundImage ?? false),
                'isProductsPanelTransparent' => (bool) ($config->isProductsPanelTransparent ?? false),
                'isListBorderTransparent' => (bool) ($config->isListBorderTransparent ?? false),
                'backgroundImageUrl' => (string) ($config->backgroundImageUrl ?? ''),
                'imageWidth' => (int) ($config->imageWidth ?? 56),
                'imageHeight' => (int) ($config->imageHeight ?? 56),
                'rowVerticalPadding' => (int) ($config->rowVerticalPadding ?? 9),
                'listFontSize' => (int) ($config->listFontSize ?? 16),
                'groupLabelFontSize' => (int) ($config->groupLabelFontSize ?? 14),
                'groupLabelFontFamily' => (string) ($config->groupLabelFontFamily ?? 'arial'),
                'groupLabelColor' => (string) ($config->groupLabelColor ?? '#cbd5e1'),
                'showGroupLabelBadge' => (bool) ($config->showGroupLabelBadge ?? false),
                'groupLabelBadgeColor' => (string) ($config->groupLabelBadgeColor ?? '#0f172a'),
                'isPaginationEnabled' => (bool) $config->isPaginationEnabled,
                'pageSize' => (int) ($config->pageSize ?? 10),
                'paginationInterval' => (int) ($config->paginationInterval ?? 5),
            ],
        ]);
    }

    private function resolveGlobalGalleryImageUrls(string $code): array
    {
        $gallery = GlobalImageGallery::query()
            ->where('code', $code)
            ->with('items')
            ->first();

        if (! $gallery) {
            return [];
        }

        return $gallery->items
            ->sortBy('slot')
            ->map(function ($item) {
                if ($item->source_type === 'link') {
                    return trim((string) ($item->external_url ?? ''));
                }

                if ($item->source_type === 'upload' && !empty($item->file_path)) {
                    return $this->publicStorageUrl((string) $item->file_path);
                }

                return '';
            })
            ->filter(fn ($url) => $url !== '')
            ->values()
            ->all();
    }

    private function normalizeImageUrlsList(string $raw): string
    {
        $normalized = collect(preg_split('/\r?\n/', $raw) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->map(function (string $line) {
                if (preg_match('#^https?://localhost/storage/(.+)$#i', $line, $matches)) {
                    return '/storage/'.ltrim((string) ($matches[1] ?? ''), '/');
                }

                if (str_starts_with($line, 'storage/')) {
                    return '/'.ltrim($line, '/');
                }

                return $line;
            })
            ->values();

        return $normalized->implode("\n");
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
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

    private function sortProductsForTv(Collection $products, string $mode, string $alphabeticalDirection, array $departmentOrder, array $groupOrder): Collection
    {
        $departmentRank = array_flip(array_values(array_unique(array_map('intval', $departmentOrder))));
        $groupRank = array_flip(array_values(array_unique(array_map('intval', $groupOrder))));
        $direction = $alphabeticalDirection === 'desc' ? 'desc' : 'asc';

        return $products
            ->sort(function ($left, $right) use ($mode, $direction, $departmentRank, $groupRank) {
                $leftDepartmentId = (int) ($left->departamento_id ?? 0);
                $rightDepartmentId = (int) ($right->departamento_id ?? 0);
                $leftGroupId = (int) ($left->grupo_id ?? 0);
                $rightGroupId = (int) ($right->grupo_id ?? 0);

                $leftDepartmentPriority = array_key_exists($leftDepartmentId, $departmentRank)
                    ? (int) $departmentRank[$leftDepartmentId]
                    : 100000 + $leftDepartmentId;
                $rightDepartmentPriority = array_key_exists($rightDepartmentId, $departmentRank)
                    ? (int) $departmentRank[$rightDepartmentId]
                    : 100000 + $rightDepartmentId;

                $leftGroupPriority = array_key_exists($leftGroupId, $groupRank)
                    ? (int) $groupRank[$leftGroupId]
                    : 100000 + $leftGroupId;
                $rightGroupPriority = array_key_exists($rightGroupId, $groupRank)
                    ? (int) $groupRank[$rightGroupId]
                    : 100000 + $rightGroupId;

                if ($mode === 'departamento') {
                    if ($leftDepartmentPriority !== $rightDepartmentPriority) {
                        return $leftDepartmentPriority <=> $rightDepartmentPriority;
                    }

                    if ($leftGroupPriority !== $rightGroupPriority) {
                        return $leftGroupPriority <=> $rightGroupPriority;
                    }
                } else {
                    if ($leftGroupPriority !== $rightGroupPriority) {
                        return $leftGroupPriority <=> $rightGroupPriority;
                    }

                    if ($leftDepartmentPriority !== $rightDepartmentPriority) {
                        return $leftDepartmentPriority <=> $rightDepartmentPriority;
                    }
                }

                $leftName = Str::ascii(mb_strtolower((string) ($left->NOME ?? '')));
                $rightName = Str::ascii(mb_strtolower((string) ($right->NOME ?? '')));
                $nameComparison = $leftName <=> $rightName;

                if ($nameComparison !== 0) {
                    return $direction === 'desc' ? -$nameComparison : $nameComparison;
                }

                return (int) $left->id <=> (int) $right->id;
            })
            ->values();
    }
}
