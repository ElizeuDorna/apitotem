<div class="space-y-8">
    @if (($statusMessage ?? null) || session('status'))
        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ $statusMessage ?? session('status') }}
        </div>
    @endif

    @if (($errorMessage ?? null) || session('error'))
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errorMessage ?? session('error') }}
        </div>
    @endif

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Integracao Meta WhatsApp</h3>
                <p class="mt-1 text-sm text-slate-600">Este modulo fica separado do Instagram/Facebook. Aqui voce informa manualmente os dados do WhatsApp Business da empresa.</p>
            </div>
            @if ($integration)
                <div class="rounded-full px-3 py-1 text-xs font-semibold {{ $integration->status === 'connected' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700' }}">
                    {{ strtoupper($integration->status) }}
                </div>
            @endif
        </div>

        <form wire:submit="saveIntegration" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Business Account ID</label>
                <input type="text" wire:model="metaBusinessAccountId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
                @error('meta_business_account_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Phone Number ID</label>
                <input type="text" wire:model="metaPhoneNumberId" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
                @error('meta_phone_number_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Numero exibido</label>
                <input type="text" wire:model="displayPhoneNumber" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Expiracao do token</label>
                <input type="datetime-local" wire:model="accessTokenExpiresAt" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-slate-800">Access Token</label>
                <textarea rows="3" wire:model="accessToken" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
                @error('access_token')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded-md border border-emerald-600 bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Salvar integracao</button>
                @if ($integration)
                    <button type="button" wire:click="disconnectIntegration" wire:confirm="Desconectar esta integracao WhatsApp?" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Desconectar</button>
                @endif
                <p class="text-xs text-slate-500">Webhook esperado: {{ route('whatsapp.webhook.verify') }}</p>
            </div>
        </form>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Contatos WhatsApp</h3>
                <p class="mt-1 text-sm text-slate-600">Somente contatos com opt-in ativo entram nos disparos.</p>
            </div>
            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $contacts->count() }} contato(s)</div>
        </div>

        <form wire:submit="saveContact" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Nome</label>
                <input type="text" wire:model="contactName" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Numero WhatsApp</label>
                <input type="text" wire:model="contactWhatsappNumber" placeholder="+55 65999999999" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
                @error('whatsapp_number')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Origem do opt-in</label>
                <input type="text" wire:model="contactOptInSource" placeholder="site, balcao, importacao" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Data do opt-in</label>
                <input type="datetime-local" wire:model="contactOptInWhatsappAt" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Status</label>
                <select wire:model="contactStatus" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
                    <option value="active">Ativo</option>
                    <option value="inactive">Inativo</option>
                </select>
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                    <input type="checkbox" wire:model="contactOptInWhatsapp" class="rounded border-slate-300 text-emerald-600 shadow-sm">
                    Opt-in autorizado para WhatsApp
                </label>
            </div>
            <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $editingContactId ? 'Atualizar contato' : 'Salvar contato' }}</button>
                @if ($editingContactId)
                    <button type="button" wire:click="$refresh" class="inline-flex items-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancelar</button>
                @endif
            </div>
        </form>

        <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Contato</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Numero</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Opt-in</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Ultima interacao</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($contacts as $contact)
                        <tr>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">
                                <div class="font-semibold text-slate-900">{{ $contact->name }}</div>
                                <div class="text-xs text-slate-500">{{ strtoupper($contact->status) }}</div>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">{{ $contact->whatsapp_number_e164 }}</td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">{{ $contact->opt_in_whatsapp ? 'Sim' : 'Nao' }} @if ($contact->opt_in_source)<div class="text-xs text-slate-500">{{ $contact->opt_in_source }}</div>@endif</td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">{{ $contact->last_whatsapp_inbound_at?->format('d/m/Y H:i') ?? 'Sem retorno' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" wire:click="editContact({{ $contact->id }})" class="font-medium text-indigo-600 hover:text-indigo-800">Editar</button>
                                    <button type="button" wire:click="deleteContact({{ $contact->id }})" wire:confirm="Excluir este contato?" class="font-medium text-rose-600 hover:text-rose-800">Excluir</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">Nenhum contato WhatsApp cadastrado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-5 flex items-start justify-between gap-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">Campanhas WhatsApp</h3>
                <p class="mt-1 text-sm text-slate-600">Campanhas livres usam a janela de 24 horas. Fora dela, o sistema exige template aprovado na Meta.</p>
            </div>
            <div class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{{ $campaigns->count() }} campanha(s)</div>
        </div>

        <form wire:submit="saveCampaign" class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Nome da campanha</label>
                <input type="text" wire:model="campaignName" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Tipo de mensagem</label>
                <select wire:model.live="campaignMessageType" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
                    <option value="freeform">Mensagem livre</option>
                    <option value="template">Template aprovado</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-semibold text-slate-800">Mensagem</label>
                <textarea rows="4" wire:model="campaignBodyText" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm"></textarea>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Imagem opcional</label>
                <input type="url" wire:model="campaignMediaUrl" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm" placeholder="https://cdn.exemplo.com/oferta.jpg">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-800">Agendar para</label>
                <input type="datetime-local" wire:model="campaignScheduledAt" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm">
            </div>
            @if ($campaignMessageType === 'template')
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-800">Nome do template Meta</label>
                    <input type="text" wire:model="campaignTemplateName" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm" placeholder="hello_world">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-slate-800">Idioma do template</label>
                    <input type="text" wire:model="campaignTemplateLanguageCode" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm" placeholder="pt_BR">
                </div>
            @endif
            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-semibold text-slate-800">Destinatarios</label>
                <div class="grid grid-cols-1 gap-2 rounded-xl border border-slate-200 p-4 md:grid-cols-2">
                    @forelse ($contacts as $contact)
                        <label class="inline-flex items-start gap-3 rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="selectedContactIds" value="{{ $contact->id }}" class="mt-1 rounded border-slate-300 text-indigo-600 shadow-sm">
                            <span>
                                <span class="block font-semibold text-slate-900">{{ $contact->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $contact->whatsapp_number_e164 }} | Opt-in: {{ $contact->opt_in_whatsapp ? 'sim' : 'nao' }}</span>
                                <span class="block text-xs text-slate-500">Janela 24h: {{ $contact->last_whatsapp_inbound_at && $contact->last_whatsapp_inbound_at->greaterThanOrEqualTo(now()->subDay()) ? 'aberta' : 'fechada' }}</span>
                            </span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-500">Cadastre contatos antes de montar a campanha.</p>
                    @endforelse
                </div>
            </div>
            <div class="md:col-span-2 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center rounded-md border border-indigo-600 bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">{{ $editingCampaignId ? 'Atualizar campanha' : 'Salvar campanha' }}</button>
                <p class="text-xs text-slate-500">Mensagens livres so saem para quem falou com a empresa nas ultimas 24 horas.</p>
            </div>
        </form>

        <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campanha</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Agendamento</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Destinatarios</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Acoes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse ($campaigns as $campaign)
                        <tr>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">
                                <div class="font-semibold text-slate-900">{{ $campaign->name }}</div>
                                @if ($campaign->body_text)
                                    <p class="mt-1 max-w-md text-xs text-slate-500">{{ $campaign->body_text }}</p>
                                @endif
                                @if ($campaign->last_error)
                                    <p class="mt-1 text-xs text-rose-600">{{ $campaign->last_error }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">{{ $campaign->message_type === 'template' ? 'Template Meta' : 'Livre 24h' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">{{ $campaign->scheduled_at?->format('d/m/Y H:i') ?? 'Sem agendamento' }}</td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">{{ strtoupper($campaign->status) }}</td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">
                                <div>{{ $campaign->recipients->count() }} total</div>
                                <div class="text-xs text-slate-500">Enviados: {{ $campaign->recipients->whereIn('status', ['sent', 'delivered', 'read'])->count() }} | Falhas: {{ $campaign->recipients->where('status', 'failed')->count() }}</div>
                            </td>
                            <td class="px-4 py-3 align-top text-sm text-slate-700">
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" wire:click="editCampaign({{ $campaign->id }})" class="font-medium text-indigo-600 hover:text-indigo-800">Editar</button>
                                    <button type="button" wire:click="dispatchCampaignNow({{ $campaign->id }})" class="font-medium text-emerald-600 hover:text-emerald-800">Disparar agora</button>
                                    <button type="button" wire:click="deleteCampaign({{ $campaign->id }})" wire:confirm="Excluir esta campanha?" class="font-medium text-rose-600 hover:text-rose-800">Excluir</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-slate-500">Nenhuma campanha WhatsApp cadastrada.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>
