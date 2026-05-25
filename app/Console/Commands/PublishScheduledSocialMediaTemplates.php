<?php

namespace App\Console\Commands;

use App\Models\SocialMediaTemplate;
use App\Services\InstagramGraphService;
use Illuminate\Console\Command;
use Throwable;

class PublishScheduledSocialMediaTemplates extends Command
{
    protected $signature = 'social-media:publish-scheduled';

    protected $description = 'Publica templates agendados de rede social que estiverem dentro da janela de agendamento';

    public function handle(InstagramGraphService $instagramService): int
    {
        SocialMediaTemplate::query()
            ->whereNotNull('scheduled_end_at')
            ->where('scheduled_end_at', '<', now())
            ->get()
            ->each(function (SocialMediaTemplate $template) {
                $payload = [];

                if (($template->publish_to_instagram ?? true) && in_array($template->instagram_publish_status, ['draft', 'scheduled', 'failed'], true)) {
                    $payload['instagram_publish_status'] = 'expired';
                }

                if (($template->publish_to_facebook ?? false) && in_array($template->facebook_publish_status, ['draft', 'scheduled', 'failed'], true)) {
                    $payload['facebook_publish_status'] = 'expired';
                }

                if ($payload !== []) {
                    $template->update($payload);
                }
            });

        $templates = SocialMediaTemplate::query()
            ->whereNotNull('scheduled_start_at')
            ->where('scheduled_start_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('scheduled_end_at')
                    ->orWhere('scheduled_end_at', '>=', now());
            })
            ->where(function ($query) {
                $query
                    ->where(function ($instagramQuery) {
                        $instagramQuery
                            ->where('publish_to_instagram', true)
                            ->where('instagram_auto_publish', true)
                            ->whereIn('instagram_publish_status', ['draft', 'scheduled', 'failed']);
                    })
                    ->orWhere(function ($facebookQuery) {
                        $facebookQuery
                            ->where('publish_to_facebook', true)
                            ->where('facebook_auto_publish', true)
                            ->whereIn('facebook_publish_status', ['draft', 'scheduled', 'failed']);
                    })
                    ->orWhere(function ($legacyInstagramQuery) {
                        $legacyInstagramQuery
                            ->whereNull('publish_to_instagram')
                            ->where('instagram_auto_publish', true)
                            ->whereIn('instagram_publish_status', ['draft', 'scheduled', 'failed']);
                    });
            })
            ->get();

        foreach ($templates as $template) {
            $channels = [];

            if (($template->publish_to_instagram ?? true) && (bool) $template->instagram_auto_publish && in_array($template->instagram_publish_status, ['draft', 'scheduled', 'failed'], true)) {
                $channels[] = 'instagram';
            }

            if (($template->publish_to_facebook ?? false) && (bool) $template->facebook_auto_publish && in_array($template->facebook_publish_status, ['draft', 'scheduled', 'failed'], true)) {
                $channels[] = 'facebook';
            }

            if ($channels === []) {
                continue;
            }

            try {
                $instagramService->publishTemplateByChannels($template, $channels);
                $this->info('Template '.$template->id.' publicado em '.implode(', ', $channels).'.');
            } catch (Throwable $exception) {
                $this->error('Template '.$template->id.' falhou: '.$exception->getMessage());
            }
        }

        return self::SUCCESS;
    }
}