<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ProdutoResource;
use App\Models\Empresa;
use App\Models\GaleriaNova;
use App\Models\Produto;
use App\Support\ImageStorage;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GaleriaImagemController extends Controller
{
    #[OA\Post(
        path: '/api/produtos/{produto}/imagem',
        tags: ['Galeria Imagem'],
        summary: 'Envia imagem para galeria e vincula ao produto pelo CODIGO',
        security: [['CompanyBearer' => []]],
        parameters: [
            new OA\Parameter(name: 'produto', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Imagem Produto X'),
                            new OA\Property(property: 'is_public', type: 'boolean', example: false),
                            new OA\Property(property: 'image', type: 'string', format: 'binary'),
                            new OA\Property(property: 'external_url', type: 'string', format: 'uri', maxLength: 1000, example: 'https://cdn.exemplo.com/imagem.jpg'),
                        ]
                    )
                ),
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        properties: [
                            new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Imagem Produto X'),
                            new OA\Property(property: 'is_public', type: 'boolean', example: false),
                            new OA\Property(property: 'external_url', type: 'string', format: 'uri', maxLength: 1000, example: 'https://cdn.exemplo.com/imagem.jpg'),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 200, description: 'Imagem vinculada ao produto com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 404, description: 'Produto não encontrado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function vincularProduto(Request $request, string $produto)
    {
        /** @var Empresa $empresa */
        $empresa = $request->attributes->get('empresa');

        $produtoModel = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('CODIGO', $produto)
            ->first();

        if (! $produtoModel) {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Produto não encontrado para a empresa autenticada.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'external_url' => ['nullable', 'url', 'max:1000'],
        ]);

        $upload = $request->file('image');
        $externalUrl = trim((string) ($validated['external_url'] ?? ''));

        if (! $upload && $externalUrl === '') {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Informe image ou external_url para vincular ao produto.',
            ], 422);
        }

        if ($upload && $externalUrl !== '') {
            return response()->json([
                'sucesso' => false,
                'mensagem' => 'Informe apenas image ou external_url, não ambos.',
            ], 422);
        }

        if ($upload) {
            $gallery = $this->createOrReuseUploadGallery($empresa, $upload, (string) ($validated['name'] ?? ''), (bool) ($validated['is_public'] ?? false));
        } else {
            $gallery = $this->createOrReuseLinkGallery($empresa, $externalUrl, (string) ($validated['name'] ?? ''), (bool) ($validated['is_public'] ?? false));
        }

        $galleryPayload = $this->payload($gallery);
        $produtoModel->update([
            'IMG' => (string) ($galleryPayload['url'] ?? ''),
        ]);

        return response()->json([
            'sucesso' => true,
            'mensagem' => 'Imagem vinculada ao produto com sucesso.',
            'dados' => [
                'produto' => new ProdutoResource($produtoModel->fresh()->load(['departamento:id,nome', 'grupo:id,nome,departamento_id'])),
                'imagem' => $galleryPayload,
            ],
        ], 200);
    }

    #[OA\Get(
        path: '/api/galeria-imagem',
        tags: ['Galeria Imagem'],
        summary: 'Lista imagens visiveis para a empresa autenticada',
        security: [['CompanyBearer' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista de imagens carregada com sucesso'),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function index(Request $request)
    {
        /** @var Empresa $empresa */
        $empresa = $request->attributes->get('empresa');

        $galleries = GaleriaNova::query()
            ->where(function ($query) use ($empresa) {
                $query->where('empresa_id', $empresa->id)
                    ->orWhereNull('empresa_id')
                    ->orWhere('is_public', true);
            })
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'sucesso' => true,
            'dados' => $galleries->map(fn (GaleriaNova $gallery) => $this->payload($gallery))->values(),
        ]);
    }

    #[OA\Post(
        path: '/api/galeria-imagem/upload',
        tags: ['Galeria Imagem'],
        summary: 'Envia imagem da empresa para a galeria da API',
        security: [['CompanyBearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'multipart/form-data',
                    schema: new OA\Schema(
                        required: ['image'],
                        properties: [
                            new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Banner Promocional'),
                            new OA\Property(property: 'is_public', type: 'boolean', example: false),
                            new OA\Property(property: 'image', type: 'string', format: 'binary'),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 201, description: 'Imagem enviada com sucesso'),
            new OA\Response(response: 200, description: 'Imagem já existia para a empresa e foi reutilizada'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function upload(Request $request)
    {
        /** @var Empresa $empresa */
        $empresa = $request->attributes->get('empresa');

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'is_public' => ['nullable', 'boolean'],
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $gallery = $this->createOrReuseUploadGallery(
            $empresa,
            $request->file('image'),
            (string) ($validated['name'] ?? ''),
            (bool) ($validated['is_public'] ?? false)
        );

        return response()->json([
            'sucesso' => true,
            'criado' => true,
            'mensagem' => 'Imagem enviada com sucesso.',
            'dados' => $this->payload($gallery->fresh()),
        ], 201);
    }

    #[OA\Post(
        path: '/api/galeria-imagem/link',
        tags: ['Galeria Imagem'],
        summary: 'Cadastra imagem por link externo na galeria da API',
        security: [['CompanyBearer' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: [
                new OA\MediaType(
                    mediaType: 'application/json',
                    schema: new OA\Schema(
                        required: ['external_url'],
                        properties: [
                            new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Banner Promocional Link'),
                            new OA\Property(property: 'external_url', type: 'string', format: 'uri', maxLength: 1000, example: 'https://cdn.exemplo.com/banner.jpg'),
                            new OA\Property(property: 'is_public', type: 'boolean', example: false),
                        ]
                    )
                ),
            ]
        ),
        responses: [
            new OA\Response(response: 201, description: 'Link cadastrado com sucesso'),
            new OA\Response(response: 200, description: 'Link já existia para a empresa e foi reutilizado'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function link(Request $request)
    {
        /** @var Empresa $empresa */
        $empresa = $request->attributes->get('empresa');

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'external_url' => ['required', 'url', 'max:1000'],
            'is_public' => ['nullable', 'boolean'],
        ]);

        $gallery = $this->createOrReuseLinkGallery(
            $empresa,
            trim((string) $validated['external_url']),
            (string) ($validated['name'] ?? ''),
            (bool) ($validated['is_public'] ?? false)
        );

        return response()->json([
            'sucesso' => true,
            'criado' => true,
            'mensagem' => 'Link cadastrado com sucesso.',
            'dados' => $this->payload($gallery),
        ], 201);
    }

    private function payload(GaleriaNova $gallery): array
    {
        return [
            'id' => $gallery->id,
            'code' => $gallery->code,
            'empresa_id' => $gallery->empresa_id,
            'is_public' => (bool) $gallery->is_public,
            'name' => $gallery->name,
            'source_type' => $gallery->source_type,
            'url' => $gallery->source_type === 'upload' && $gallery->file_path
                ? ImageStorage::publicUrl((string) $gallery->file_path)
                : trim((string) ($gallery->external_url ?? '')),
        ];
    }

    private function createOrReuseUploadGallery(Empresa $empresa, $upload, string $name, bool $isPublic): GaleriaNova
    {
        $imageHash = hash_file('sha256', (string) $upload->getRealPath());

        $existing = GaleriaNova::query()
            ->where('empresa_id', $empresa->id)
            ->where('source_type', 'upload')
            ->where('image_hash', $imageHash)
            ->first();

        if ($existing) {
            return $existing;
        }

        $gallery = GaleriaNova::query()->create([
            'code' => $this->generateUniqueCode(),
            'empresa_id' => $empresa->id,
            'is_public' => $isPublic,
            'name' => trim($name) !== ''
                ? $name
                : pathinfo((string) $upload->getClientOriginalName(), PATHINFO_FILENAME),
            'source_type' => 'upload',
            'external_url' => null,
            'file_path' => null,
            'image_hash' => $imageHash,
            'created_by' => null,
        ]);

        $extension = strtolower((string) $upload->getClientOriginalExtension());
        $document = preg_replace('/\D/', '', (string) ($empresa->cnpj_cpf ?? '')) ?: 'empresa';
        $fileName = $gallery->code.($extension !== '' ? '.'.$extension : '');
        $path = $upload->storeAs('empresas/'.$document.'/galeria', $fileName, ImageStorage::disk());

        $gallery->update([
            'file_path' => $path,
        ]);

        return $gallery->fresh();
    }

    private function createOrReuseLinkGallery(Empresa $empresa, string $externalUrl, string $name, bool $isPublic): GaleriaNova
    {
        $existing = GaleriaNova::query()
            ->where('empresa_id', $empresa->id)
            ->where('source_type', 'link')
            ->where('external_url', $externalUrl)
            ->first();

        if ($existing) {
            return $existing;
        }

        return GaleriaNova::query()->create([
            'code' => $this->generateUniqueCode(),
            'empresa_id' => $empresa->id,
            'is_public' => $isPublic,
            'name' => trim($name) !== '' ? $name : 'Imagem via link',
            'source_type' => 'link',
            'external_url' => $externalUrl,
            'file_path' => null,
            'image_hash' => null,
            'created_by' => null,
        ]);
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = (string) random_int(10000000000000, 99999999999999);
            $exists = GaleriaNova::query()->where('code', $code)->exists();
        } while ($exists);

        return $code;
    }
}