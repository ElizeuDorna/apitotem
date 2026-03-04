<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Configuracao;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebScreenConfigController extends Controller
{
    public function edit(): View
    {
        $empresaId = $this->resolveEmpresaId();

        $config = Configuracao::firstOrCreate([
            'empresa_id' => $empresaId,
        ], []);

        return view('admin.web-screen-config', [
            'config' => $config->fresh(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $empresaId = $this->resolveEmpresaId();

        $rawVideoUrls = collect($request->input('video_urls', []))
            ->map(fn ($value) => trim((string) $value))
            ->values();

        $rawMutedFlags = collect($request->input('video_muted_flags', []))
            ->map(fn ($value) => (string) $value === '1')
            ->values();

        $rawActiveFlags = collect($request->input('video_active_flags', []))
            ->map(fn ($value) => (string) $value === '1')
            ->values();

        $rawFullscreenFlags = collect($request->input('video_fullscreen_flags', []))
            ->map(fn ($value) => (string) $value === '1')
            ->values();

        $rawDurationSeconds = collect($request->input('video_duration_seconds', []))
            ->map(function ($value) {
                if ($value === null || $value === '') {
                    return 0;
                }

                return max(0, (int) $value);
            })
            ->values();

        $rawVideoHeights = collect($request->input('video_heights', []))
            ->map(function ($value) {
                if ($value === null || $value === '') {
                    return 0;
                }

                return max(0, (int) $value);
            })
            ->values();

        $playlist = collect(range(0, 9))
            ->map(function (int $index) use ($rawVideoUrls, $rawMutedFlags, $rawActiveFlags, $rawFullscreenFlags, $rawDurationSeconds, $rawVideoHeights) {
                $url = trim((string) ($rawVideoUrls->get($index) ?? ''));

                $isActive = (bool) ($rawActiveFlags->get($index) ?? false);

                return [
                    'url' => $url,
                    'muted' => (bool) ($rawMutedFlags->get($index) ?? false),
                    'active' => $url !== '' ? $isActive : false,
                    'fullscreen' => $url !== '' ? (bool) ($rawFullscreenFlags->get($index) ?? false) : false,
                    'durationSeconds' => $url !== '' ? (int) ($rawDurationSeconds->get($index) ?? 0) : 0,
                    'heightPx' => $url !== '' ? (int) ($rawVideoHeights->get($index) ?? 0) : 0,
                ];
            })
            ->values();

        $request->merge([
            'video_urls' => $rawVideoUrls->all(),
            'video_duration_seconds' => $rawDurationSeconds->all(),
            'video_heights' => $rawVideoHeights->all(),
            'videoUrl' => $playlist
                ->filter(fn ($item) => !empty($item['url']))
                ->pluck('url')
                ->implode("\n"),
            'videoPlaylist' => $playlist->all(),
        ]);

        $validated = $request->validate([
            'videoUrl' => ['nullable', 'string', 'max:10000'],
            'videoPlaylist' => ['nullable', 'array', 'max:10'],
            'videoPlaylist.*.url' => ['nullable', 'url', 'max:1000'],
            'videoPlaylist.*.muted' => ['required', 'boolean'],
            'videoPlaylist.*.active' => ['required', 'boolean'],
            'videoPlaylist.*.fullscreen' => ['required', 'boolean'],
            'videoPlaylist.*.durationSeconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'videoPlaylist.*.heightPx' => ['required', 'integer', 'min:0', 'max:2000'],
            'video_urls' => ['nullable', 'array', 'max:10'],
            'video_urls.*' => ['nullable', 'url', 'max:1000'],
            'video_duration_seconds' => ['nullable', 'array', 'max:10'],
            'video_duration_seconds.*' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'video_heights' => ['nullable', 'array', 'max:10'],
            'video_heights.*' => ['nullable', 'integer', 'min:0', 'max:2000'],
            'videoMuted' => ['nullable', 'boolean'],
            'showVideoPanel' => ['nullable', 'boolean'],
            'appBackgroundColor' => ['required', 'string', 'max:9'],
            'productsPanelBackgroundColor' => ['required', 'string', 'max:9'],
            'listBorderColor' => ['required', 'string', 'max:9'],
            'videoBackgroundColor' => ['required', 'string', 'max:9'],
            'rowBackgroundColor' => ['required', 'string', 'max:9'],
            'borderColor' => ['required', 'string', 'max:7'],
            'priceColor' => ['required', 'string', 'max:7'],
            'useGradient' => ['nullable', 'boolean'],
            'gradientStartColor' => ['nullable', 'string', 'max:7'],
            'gradientEndColor' => ['nullable', 'string', 'max:7'],
            'backgroundImageUrl' => ['nullable', 'url', 'max:1000'],
            'showBorder' => ['nullable', 'boolean'],
            'isRowBorderTransparent' => ['nullable', 'boolean'],
            'showTitle' => ['nullable', 'boolean'],
            'showBackgroundImage' => ['nullable', 'boolean'],
            'isProductsPanelTransparent' => ['nullable', 'boolean'],
            'isListBorderTransparent' => ['nullable', 'boolean'],
            'imageWidth' => ['nullable', 'integer', 'min:20', 'max:400'],
            'imageHeight' => ['nullable', 'integer', 'min:20', 'max:400'],
            'listFontSize' => ['nullable', 'integer', 'min:10', 'max:60'],
            'groupLabelFontSize' => ['nullable', 'integer', 'min:10', 'max:60'],
            'groupLabelColor' => ['required', 'string', 'max:9'],
            'isPaginationEnabled' => ['nullable', 'boolean'],
            'pageSize' => ['nullable', 'integer', 'min:1', 'max:100'],
            'paginationInterval' => ['nullable', 'integer', 'min:1', 'max:120'],
        ]);

        $validated['useGradient'] = (bool) ($validated['useGradient'] ?? false);
        $validated['showBorder'] = (bool) ($validated['showBorder'] ?? false);
        $validated['isRowBorderTransparent'] = (bool) ($validated['isRowBorderTransparent'] ?? false);
        $validated['showTitle'] = (bool) ($validated['showTitle'] ?? true);
        $validated['showBackgroundImage'] = (bool) ($validated['showBackgroundImage'] ?? false);
        $validated['isProductsPanelTransparent'] = (bool) ($validated['isProductsPanelTransparent'] ?? false);
        $validated['isListBorderTransparent'] = (bool) ($validated['isListBorderTransparent'] ?? false);
        $validated['showVideoPanel'] = (bool) ($validated['showVideoPanel'] ?? true);
        $validated['videoMuted'] = (bool) ($validated['videoMuted'] ?? false);
        $validated['isPaginationEnabled'] = (bool) ($validated['isPaginationEnabled'] ?? false);
        $validated['productsPanelBackgroundColor'] = (string) ($validated['productsPanelBackgroundColor'] ?? '#0f172a');
        $validated['listBorderColor'] = (string) ($validated['listBorderColor'] ?? '#334155');
        $validated['videoBackgroundColor'] = (string) ($validated['videoBackgroundColor'] ?? '#000000');
        $validated['imageWidth'] = (int) ($validated['imageWidth'] ?? 56);
        $validated['imageHeight'] = (int) ($validated['imageHeight'] ?? 56);
        $validated['listFontSize'] = (int) ($validated['listFontSize'] ?? 16);
        $validated['groupLabelFontSize'] = (int) ($validated['groupLabelFontSize'] ?? 14);
        $validated['pageSize'] = (int) ($validated['pageSize'] ?? 10);
        $validated['paginationInterval'] = (int) ($validated['paginationInterval'] ?? 5);
        unset($validated['video_urls']);
        unset($validated['video_duration_seconds']);
        unset($validated['video_heights']);

        if (! $validated['useGradient']) {
            $validated['gradientStartColor'] = $validated['rowBackgroundColor'];
            $validated['gradientEndColor'] = $validated['rowBackgroundColor'];
        }

        if (! $validated['showBackgroundImage']) {
            $validated['backgroundImageUrl'] = null;
        }

        Configuracao::updateOrCreate(
            ['empresa_id' => $empresaId],
            $validated
        );

        return redirect()
            ->back()
            ->with('success', 'Configuração da Tela Web atualizada com sucesso.');
    }

    private function resolveEmpresaId(): int
    {
        $user = Auth::user();

        if (! $user->empresa_id) {
            abort(403, 'Usuário sem empresa vinculada.');
        }

        return (int) $user->empresa_id;
    }
}
