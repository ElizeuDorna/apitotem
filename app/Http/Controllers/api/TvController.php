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
use App\Models\Empresa;
use App\Models\Produto;
use App\Models\Template;
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
    private const TEMPLATE_DEV_MODE_CACHE_PREFIX = 'tv:web:disable-templates:empresa:';
    private const RESPONSIVE_MODE_CACHE_PREFIX = 'tv:web:enable-responsive:empresa:';

    #[OA\Post(
        path: '/api/tv/activation-code',
        tags: ['TV'],
        summary: 'Gera código de ativação para TV',
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(ref: '#/components/schemas/TvActivationCodePayload'),
                    examples: [
                        new OA\Examples(
                            example: 'device_uuid',
                            summary: 'Gerar código para um dispositivo ainda não ativado',
                            value: ['device_uuid' => '9f23edc7-9a60-4f18-bf4b-29f5d71b7030']
                        ),
                    ]
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Código gerado',
                content: new OA\JsonContent(example: [
                    'code' => 'AB12CD34EF',
                    'expires_in' => 300,
                ])
            ),
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
                    schema: new OA\Schema(ref: '#/components/schemas/TvCheckActivationPayload'),
                    examples: [
                        new OA\Examples(
                            example: 'device_uuid',
                            summary: 'Consultar se o dispositivo já recebeu token',
                            value: ['device_uuid' => '9f23edc7-9a60-4f18-bf4b-29f5d71b7030']
                        ),
                    ]
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Status pending ou activated',
                content: new OA\JsonContent(examples: [
                    new OA\Examples(example: 'pending', summary: 'Dispositivo ainda não ativado', value: ['status' => 'pending']),
                    new OA\Examples(example: 'activated', summary: 'Dispositivo ativado', value: ['status' => 'activated', 'token' => 'TOKEN_DA_TV']),
                ])
            ),
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
                    schema: new OA\Schema(ref: '#/components/schemas/TvHeartbeatPayload'),
                    examples: [
                        new OA\Examples(
                            example: 'token',
                            summary: 'Heartbeat enviado no body quando não usar Bearer token',
                            value: ['token' => 'TOKEN_DA_TV']
                        ),
                    ]
                ),
            ]
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Heartbeat atualizado',
                content: new OA\JsonContent(example: [
                    'status' => 'ok',
                    'last_seen_at' => '2026-04-17T10:30:00Z',
                ])
            ),
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
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(example: [
                    'success' => true,
                    'data' => [
                        'produtos' => [
                            [
                                'id' => 100,
                                'codigo' => '78901',
                                'nome' => 'Coca-Cola 2L',
                                'preco' => 9.99,
                                'oferta' => 8.99,
                                'imagem' => 'https://exemplo.com/imagens/coca-2l.jpg',
                                'grupo' => ['id' => 10, 'nome' => 'Refrigerantes'],
                                'departamento' => ['id' => 1, 'nome' => 'Bebidas'],
                            ],
                        ],
                    ],
                    'meta' => ['total_produtos' => 1],
                ])
            ),
            new OA\Response(response: 401, description: 'Token do device inválido')
        ]
    )]
    public function produtos(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'success' => false,
                'reason' => 'token_missing',
                'forceReconfigure' => false,
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
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'reason' => 'device_not_found',
                'forceReconfigure' => true,
                'data' => [
                    'produtos' => [],
                ],
                'meta' => [
                    'total_produtos' => 0,
                ],
            ], 401);
        }

        if (! (bool) $device->ativo) {
            return response()->json([
                'success' => false,
                'reason' => 'device_inactive',
                'forceReconfigure' => true,
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
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(example: [
                    'status' => 'ok',
                    'device' => [
                        'id' => 1,
                        'nome' => 'TV Açougue',
                        'local' => 'Setor Açougue',
                        'empresa_id' => 1,
                        'last_seen_at' => '2026-04-17T10:30:00Z',
                    ],
                    'empresa' => [
                        'id' => 1,
                        'nome' => 'Mercado Exemplo',
                        'cnpj_cpf' => '12345678000199',
                    ],
                    'configuracao' => [
                        'id' => 1,
                        'template_id' => 2,
                        'atualizar_produtos_segundos' => 30,
                        'volume' => 10,
                        'orientacao' => 'landscape',
                    ],
                    'template' => [
                        'id' => 2,
                        'nome' => 'Template Principal',
                        'tipo_layout' => 'split',
                    ],
                    'items' => [
                        [
                            'id' => 11,
                            'tipo' => 'video',
                            'ordem' => 1,
                            'conteudo' => 'https://exemplo.com/video.mp4',
                            'config_json' => null,
                        ],
                    ],
                ])
            ),
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
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(example: [
                    'status' => 'ok',
                    'videos' => [
                        [
                            'id' => 11,
                            'ordem' => 1,
                            'conteudo' => 'https://exemplo.com/video.mp4',
                            'config_json' => null,
                        ],
                    ],
                    'imagens' => [
                        [
                            'id' => 12,
                            'ordem' => 2,
                            'conteudo' => 'https://exemplo.com/banner.jpg',
                            'config_json' => null,
                        ],
                    ],
                    'banners' => [],
                ])
            ),
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
            new OA\Response(
                response: 200,
                description: 'OK',
                content: new OA\JsonContent(example: [
                    'status' => 'ok',
                    'empresa_id' => 1,
                    'device_id' => 1,
                    'total' => 1,
                    'dados' => [
                        [
                            'id' => 100,
                            'codigo' => '78901',
                            'nome' => 'Coca-Cola 2L',
                            'preco' => 9.99,
                            'oferta' => 8.99,
                            'imagem' => 'https://exemplo.com/imagens/coca-2l.jpg',
                            'grupo' => ['id' => 10, 'nome' => 'Refrigerantes'],
                            'departamento' => ['id' => 1, 'nome' => 'Bebidas'],
                        ],
                    ],
                ])
            ),
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
                'reason' => 'token_missing',
                'forceReconfigure' => false,
            ], 401);
        }

        $device = Device::query()
            ->where('token', $token)
            ->first();

        if (! $device) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo inválido.',
                'reason' => 'device_not_found',
                'forceReconfigure' => true,
            ], 401);
        }

        if (! (bool) $device->ativo) {
            return response()->json([
                'success' => false,
                'message' => 'Dispositivo desativado.',
                'reason' => 'device_inactive',
                'forceReconfigure' => true,
            ], 401);
        }

        $config = Configuracao::query()->firstOrCreate([
            'empresa_id' => $device->empresa_id,
        ], []);

        $deviceConfiguration = $this->resolveDeviceConfiguration((int) $device->id);
        $templatePayload = $this->resolveWebTemplatePayloadForDevice($device, $deviceConfiguration);
        if (!empty($templatePayload)) {
            $config = $this->applyWebTemplatePayloadToConfig($config, $templatePayload);
        }

        $empresa = Empresa::query()->find($device->empresa_id);
        $configuredRightSidebarLogoUrl = $this->normalizeCompanyLogoUrl((string) ($config->rightSidebarLogoUrl ?? ''));
        $companyLogoUrl = $this->normalizeCompanyLogoUrl((string) ($empresa->urlimagem ?? ''));
        $rightSidebarLogoUrl = $configuredRightSidebarLogoUrl !== ''
            ? $configuredRightSidebarLogoUrl
            : $companyLogoUrl;
        $leftVerticalLogoUrl = $this->normalizeCompanyLogoUrl((string) ($config->leftVerticalLogoUrl ?? ''));

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
        $rightSidebarImageUrls = $configuredImageUrls;

        if ($rightSidebarImageUrls === '') {
            $scheduleUrls = collect((array) ($config->rightSidebarImageSchedules ?? []))
                ->map(fn ($item) => $this->normalizeSlideUrlForCompare((string) (($item['url'] ?? ''))))
                ->filter(fn (string $url) => $url !== '')
                ->unique()
                ->values()
                ->all();

            if (! empty($scheduleUrls)) {
                $rightSidebarImageUrls = $this->normalizeImageUrlsList(implode("\n", $scheduleUrls));
            }
        }

        if ($rightSidebarImageUrls === '' && $globalGalleryCode !== '') {
            $globalGalleryUrls = $this->resolveGlobalGalleryImageUrls($globalGalleryCode);
            $rightSidebarImageUrls = $this->normalizeImageUrlsList(implode("\n", $globalGalleryUrls));
        }

        $isAndroidRuntime = preg_match('/android/i', (string) ($request->userAgent() ?? '')) === 1;
        $rightSidebarImageUrls = $this->filterRightSidebarImageUrlsBySchedule(
            $rightSidebarImageUrls,
            (array) ($config->rightSidebarImageSchedules ?? []),
            $isAndroidRuntime
        );

        return response()->json([
            'success' => true,
            'data' => [
                'videoUrl' => $config->videoUrl,
                'apiRefreshInterval' => (int) ($config->apiRefreshInterval ?? 30),
                'videoMuted' => (bool) $config->videoMuted,
                'showVideoPanel' => (bool) ($config->showVideoPanel ?? true),
                'showRightSidebarPanel' => (bool) ($config->showRightSidebarPanel ?? true),
                'enableResponsiveLayout' => $this->isResponsiveModeEnabled((int) $device->empresa_id),
                'showRightSidebarLogo' => (bool) ($config->showRightSidebarLogo ?? false),
                'rightSidebarLogoPosition' => 'sidebar_top',
                'rightSidebarLogoPositionWindows' => (string) ($config->rightSidebarLogoPositionWindows ?? $config->rightSidebarLogoPosition ?? 'sidebar_top'),
                'rightSidebarLogoPositionAndroid' => (string) ($config->rightSidebarLogoPositionAndroid ?? $config->rightSidebarLogoPosition ?? 'sidebar_top'),
                'rightSidebarLogoUrl' => $rightSidebarLogoUrl,
                'rightSidebarLogoWidth' => (int) ($config->rightSidebarLogoWidth ?? 220),
                'rightSidebarLogoHeight' => (int) ($config->rightSidebarLogoHeight ?? 58),
                'rightSidebarLogoWidthWindows' => (int) ($config->rightSidebarLogoWidthWindows ?? $config->rightSidebarLogoWidth ?? 220),
                'rightSidebarLogoHeightWindows' => (int) ($config->rightSidebarLogoHeightWindows ?? $config->rightSidebarLogoHeight ?? 58),
                'rightSidebarLogoWidthAndroid' => (int) ($config->rightSidebarLogoWidthAndroid ?? $config->rightSidebarLogoWidth ?? 220),
                'rightSidebarLogoHeightAndroid' => (int) ($config->rightSidebarLogoHeightAndroid ?? $config->rightSidebarLogoHeight ?? 58),
                'showLeftVerticalLogo' => (bool) ($config->showLeftVerticalLogo ?? false),
                'leftVerticalLogoUrl' => $leftVerticalLogoUrl,
                'leftVerticalLogoWidth' => (int) ($config->leftVerticalLogoWidth ?? 120),
                'leftVerticalLogoHeight' => (int) ($config->leftVerticalLogoHeight ?? 220),
                'leftVerticalLogoWidthWindows' => (int) ($config->leftVerticalLogoWidthWindows ?? $config->leftVerticalLogoWidth ?? 120),
                'leftVerticalLogoHeightWindows' => (int) ($config->leftVerticalLogoHeightWindows ?? $config->leftVerticalLogoHeight ?? 220),
                'leftVerticalLogoWidthAndroid' => (int) ($config->leftVerticalLogoWidthAndroid ?? $config->leftVerticalLogoWidth ?? 120),
                'leftVerticalLogoHeightAndroid' => (int) ($config->leftVerticalLogoHeightAndroid ?? $config->leftVerticalLogoHeight ?? 220),
                'rightSidebarLogoBackgroundColor' => (string) ($config->rightSidebarLogoBackgroundColor ?? '#0f172a'),
                'isRightSidebarLogoBackgroundTransparent' => (bool) ($config->isRightSidebarLogoBackgroundTransparent ?? false),
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
                'fullScreenSlideImageUrls' => $this->normalizeImageUrlsList((string) ($config->fullScreenSlideImageUrls ?? '')),
                'fullScreenSlideInterval' => (int) ($config->fullScreenSlideInterval ?? 8),
                'fullScreenSlideReturnDelaySeconds' => (int) ($config->fullScreenSlideReturnDelaySeconds ?? 0),
                'fullScreenSlideEnabled' => (bool) ($config->fullScreenSlideEnabled ?? false),
                'fullScreenSlideStartDate' => (string) ($config->fullScreenSlideStartDate ?? ''),
                'fullScreenSlideEndDate' => (string) ($config->fullScreenSlideEndDate ?? ''),
                'fullScreenSlideEnabledWindows' => (bool) ($config->fullScreenSlideEnabledWindows ?? true),
                'fullScreenSlideEnabledAndroid' => (bool) ($config->fullScreenSlideEnabledAndroid ?? true),
                'fullScreenSlideImageWidthWindows' => (int) ($config->fullScreenSlideImageWidthWindows ?? 0),
                'fullScreenSlideImageHeightWindows' => (int) ($config->fullScreenSlideImageHeightWindows ?? 0),
                'fullScreenSlideImageWidthAndroid' => (int) ($config->fullScreenSlideImageWidthAndroid ?? 0),
                'fullScreenSlideImageHeightAndroid' => (int) ($config->fullScreenSlideImageHeightAndroid ?? 0),
                'rightSidebarImageSchedules' => collect($config->rightSidebarImageSchedules ?? [])->values()->all(),
                'rightSidebarImageInterval' => (int) ($config->rightSidebarImageInterval ?? 8),
                'rightSidebarImageFit' => (string) ($config->rightSidebarImageFit ?? 'scale-down'),
                'rightSidebarImageHeight' => (int) ($config->rightSidebarImageHeight ?? 96),
                'rightSidebarImageWidth' => (int) ($config->rightSidebarImageWidth ?? 0),
                'rightSidebarAndroidHeight' => (int) ($config->rightSidebarAndroidHeight ?? 0),
                'rightSidebarAndroidWidth' => (int) ($config->rightSidebarAndroidWidth ?? 0),
                'rightSidebarAndroidVerticalOffset' => (int) ($config->rightSidebarAndroidVerticalOffset ?? 0),
                'rightSidebarHybridVideoDuration' => (int) ($config->rightSidebarHybridVideoDuration ?? 2),
                'rightSidebarHybridImageDuration' => (int) ($config->rightSidebarHybridImageDuration ?? 4),
                'rightSidebarProductCarouselEnabled' => (bool) ($config->rightSidebarProductCarouselEnabled ?? false),
                'rightSidebarProductDisplayMode' => (string) ($config->rightSidebarProductDisplayMode ?? 'all'),
                'rightSidebarProductTransitionMode' => (string) ($config->rightSidebarProductTransitionMode ?? 'products_only'),
                'rightSidebarPlaybackSequence' => (string) ($config->rightSidebarPlaybackSequence ?? 'products,image,video'),
                'rightSidebarProductInterval' => (int) ($config->rightSidebarProductInterval ?? 8),
                'rightSidebarProductShowImage' => (bool) ($config->rightSidebarProductShowImage ?? true),
                'rightSidebarProductShowName' => (bool) ($config->rightSidebarProductShowName ?? true),
                'rightSidebarProductShowPrice' => (bool) ($config->rightSidebarProductShowPrice ?? true),
                'rightSidebarProductNamePosition' => (string) ($config->rightSidebarProductNamePosition ?? 'top'),
                'rightSidebarProductPricePosition' => (string) ($config->rightSidebarProductPricePosition ?? 'bottom'),
                'rightSidebarProductNameColor' => (string) ($config->rightSidebarProductNameColor ?? '#FFFFFF'),
                'rightSidebarProductPriceColor' => (string) ($config->rightSidebarProductPriceColor ?? '#FDE68A'),
                'rightSidebarProductNameBadgeEnabled' => (bool) ($config->rightSidebarProductNameBadgeEnabled ?? true),
                'rightSidebarProductNameBadgeColor' => (string) ($config->rightSidebarProductNameBadgeColor ?? '#0F172A'),
                'rightSidebarProductPriceBadgeEnabled' => (bool) ($config->rightSidebarProductPriceBadgeEnabled ?? true),
                'rightSidebarProductPriceBadgeColor' => (string) ($config->rightSidebarProductPriceBadgeColor ?? '#0F172A'),
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
                'rowLineSpacing' => (int) ($config->rowLineSpacing ?? 12),
                'productListVerticalOffset' => (int) ($config->productListVerticalOffset ?? 0),
                'listFontSize' => (int) ($config->listFontSize ?? 16),
                'groupLabelFontSize' => (int) ($config->groupLabelFontSize ?? 14),
                'groupLabelVerticalOffset' => (int) ($config->groupLabelVerticalOffset ?? 0),
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

    private function filterRightSidebarImageUrlsBySchedule(string $rawUrls, array $schedules, bool $isAndroidRuntime): string
    {
        $urlList = collect(preg_split('/\r?\n/', (string) $rawUrls) ?: [])
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values();

        if ($urlList->isEmpty() || empty($schedules)) {
            return $urlList->implode("\n");
        }

        $scheduleByKey = [];
        $scheduleByLooseKey = [];

        foreach ($schedules as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $normalizedUrl = $this->normalizeSlideUrlForCompare((string) ($entry['url'] ?? ''));
            if ($normalizedUrl === '') {
                continue;
            }

            $normalizedSchedule = [
                'startDate' => trim((string) ($entry['startDate'] ?? '')),
                'endDate' => trim((string) ($entry['endDate'] ?? '')),
                'enabledForWindows' => $this->toBooleanFlag($entry['enabledForWindows'] ?? null, true),
                'enabledForAndroid' => $this->toBooleanFlag($entry['enabledForAndroid'] ?? null, true),
            ];

            $scheduleByKey[$normalizedUrl] = $normalizedSchedule;
            $scheduleByLooseKey[$this->toLooseSlideUrlKey($normalizedUrl)] = $normalizedSchedule;
        }

        $today = now()->toDateString();

        $filteredUrls = $urlList->filter(function (string $url) use ($scheduleByKey, $scheduleByLooseKey, $today, $isAndroidRuntime) {
            $normalizedUrl = $this->normalizeSlideUrlForCompare($url);

            $schedule = $scheduleByKey[$normalizedUrl]
                ?? $scheduleByLooseKey[$this->toLooseSlideUrlKey($normalizedUrl)]
                ?? null;

            if (!is_array($schedule)) {
                return true;
            }

            $startDate = (string) ($schedule['startDate'] ?? '');
            $endDate = (string) ($schedule['endDate'] ?? '');

            if ($startDate !== '' && $today < $startDate) {
                return false;
            }

            if ($endDate !== '' && $today > $endDate) {
                return false;
            }

            if ($isAndroidRuntime && ($schedule['enabledForAndroid'] ?? true) === false) {
                return false;
            }

            if (!$isAndroidRuntime && ($schedule['enabledForWindows'] ?? true) === false) {
                return false;
            }

            return true;
        })->values();

        return $filteredUrls->implode("\n");
    }

    private function normalizeSlideUrlForCompare(string $raw): string
    {
        $value = trim((string) $raw);
        if ($value === '') {
            return '';
        }

        $value = trim((string) preg_replace('/[?#].*$/', '', $value));

        if (preg_match('#^https?://localhost/storage/(.+)$#i', $value, $matches)) {
            return '/storage/'.ltrim((string) ($matches[1] ?? ''), '/');
        }

        if (preg_match('#^storage/#i', $value)) {
            return '/'.ltrim($value, '/');
        }

        if (preg_match('#^https?://#i', $value)) {
            $path = (string) parse_url($value, PHP_URL_PATH);
            if ($path !== '' && preg_match('#^/storage/#i', $path)) {
                return $path;
            }
        }

        return $value;
    }

    private function toLooseSlideUrlKey(string $url): string
    {
        return rtrim($this->normalizeSlideUrlForCompare($url), '/');
    }

    private function toBooleanFlag(mixed $value, bool $default = false): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return ((int) $value) === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return in_array($normalized, ['1', 'true', 'on', 'yes'], true);
    }

    private function publicStorageUrl(string $path): string
    {
        return '/storage/'.ltrim($path, '/');
    }

    private function normalizeCompanyLogoUrl(string $raw): string
    {
        $value = trim($raw);

        if ($value === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        if (preg_match('#^https?://localhost/storage/(.+)$#i', $value, $matches)) {
            return '/storage/'.ltrim((string) ($matches[1] ?? ''), '/');
        }

        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        if (str_starts_with($value, 'storage/')) {
            return '/'.ltrim($value, '/');
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        return '/storage/'.ltrim($value, '/');
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

    private function resolveWebTemplatePayloadForDevice(Device $device, DeviceConfiguration $configuration): array
    {
        if ($this->isTemplateDevModeEnabled((int) $device->empresa_id)) {
            return [];
        }

        $template = $configuration->template;

        if ($template && is_array($template->web_config_payload) && !empty($template->web_config_payload)) {
            return $template->web_config_payload;
        }

        $defaultTemplate = Template::query()
            ->where('empresa_id', $device->empresa_id)
            ->where('is_default_web', true)
            ->latest('id')
            ->first();

        if ($defaultTemplate && is_array($defaultTemplate->web_config_payload) && !empty($defaultTemplate->web_config_payload)) {
            return $defaultTemplate->web_config_payload;
        }

        return [];
    }

    private function applyWebTemplatePayloadToConfig(Configuracao $config, array $payload): Configuracao
    {
        $fillable = array_flip((new Configuracao())->getFillable());
        $blockedKeys = array_flip([
            'rightSidebarMediaType',
            'rightSidebarGlobalGalleryCode',
            'rightSidebarImageUrls',
            'rightSidebarImageSchedules',
            'rightSidebarImageInterval',
            'rightSidebarImageFit',
            'rightSidebarImageHeight',
            'rightSidebarImageWidth',
            'rightSidebarAndroidHeight',
            'rightSidebarAndroidWidth',
            'rightSidebarAndroidVerticalOffset',
            'rightSidebarPlaybackSequence',
        ]);

        foreach ($payload as $key => $value) {
            if (!is_string($key) || !isset($fillable[$key])) {
                continue;
            }

            if (in_array($key, ['empresa_id'], true)) {
                continue;
            }

            if (isset($blockedKeys[$key])) {
                continue;
            }

            $config->setAttribute($key, $value);
        }

        return $config;
    }

    private function isTemplateDevModeEnabled(int $empresaId): bool
    {
        return (bool) Cache::get(self::TEMPLATE_DEV_MODE_CACHE_PREFIX.$empresaId, false);
    }

    private function isResponsiveModeEnabled(int $empresaId): bool
    {
        return (bool) Cache::get(self::RESPONSIVE_MODE_CACHE_PREFIX.$empresaId, false);
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
