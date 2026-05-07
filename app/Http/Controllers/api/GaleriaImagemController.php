<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\GaleriaNova;
use App\Support\ImageStorage;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class GaleriaImagemController extends Controller
{
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

        $upload = $request->file('image');
        $imageHash = hash_file('sha256', (string) $upload->getRealPath());

        $existing = GaleriaNova::query()
            ->where('empresa_id', $empresa->id)
            ->where('source_type', 'upload')
            ->where('image_hash', $imageHash)
            ->first();

        if ($existing) {
            return response()->json([
                'sucesso' => true,
                'criado' => false,
                'mensagem' => 'Imagem ja cadastrada para a empresa.',
                'dados' => $this->payload($existing),
            ], 200);
        }

        $gallery = GaleriaNova::query()->create([
            'code' => $this->generateUniqueCode(),
            'empresa_id' => $empresa->id,
            'is_public' => (bool) ($validated['is_public'] ?? false),
            'name' => trim((string) ($validated['name'] ?? '')) !== ''
                ? (string) $validated['name']
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

        return response()->json([
            'sucesso' => true,
            'criado' => true,
            'mensagem' => 'Imagem enviada com sucesso.',
            'dados' => $this->payload($gallery->fresh()),
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

    private function generateUniqueCode(): string
    {
        do {
            $code = (string) random_int(10000000000000, 99999999999999);
            $exists = GaleriaNova::query()->where('code', $code)->exists();
        } while ($exists);

        return $code;
    }
}