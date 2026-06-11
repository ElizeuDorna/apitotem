<x-guest-layout>
    <div class="space-y-6">
        <div class="text-center">
            <h1 class="text-2xl font-semibold text-slate-900">Criar conta da empresa</h1>
            <p class="mt-2 text-sm text-slate-600">Escolha o plano e ative sua empresa com cobrança automática configurada.</p>
        </div>

        <x-auth-session-status class="mb-4" :status="session('status')" />

        @if ($errors->any())
            <div class="rounded-md bg-red-100 p-3 text-sm text-red-800">
                <ul class="space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (empty($plans))
            <div class="rounded-md bg-amber-100 p-4 text-sm text-amber-900">
                Nenhum plano de auto cadastro está configurado no momento.
            </div>
        @else
            <form method="POST" action="{{ route('self-service.register.store') }}" class="space-y-5">
                @csrf

                <div class="space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Dados da empresa</h2>
                    <div>
                        <x-input-label for="company_name" :value="__('Nome fantasia')" />
                        <x-text-input id="company_name" class="mt-1 block w-full" type="text" name="company_name" :value="old('company_name')" required autofocus />
                        <x-input-error :messages="$errors->get('company_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="company_social_name" :value="__('Razão social')" />
                        <x-text-input id="company_social_name" class="mt-1 block w-full" type="text" name="company_social_name" :value="old('company_social_name')" required />
                        <x-input-error :messages="$errors->get('company_social_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="company_document" :value="__('CNPJ')" />
                        <x-text-input id="company_document" class="mt-1 block w-full cpf-cnpj-mask" type="text" name="company_document" :value="old('company_document')" required maxlength="18" inputmode="numeric" />
                        <x-input-error :messages="$errors->get('company_document')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="company_email" :value="__('Email da empresa')" />
                        <x-text-input id="company_email" class="mt-1 block w-full" type="email" name="company_email" :value="old('company_email')" required />
                        <x-input-error :messages="$errors->get('company_email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="company_phone" :value="__('Telefone')" />
                        <x-text-input id="company_phone" class="mt-1 block w-full" type="text" name="company_phone" :value="old('company_phone')" required />
                        <x-input-error :messages="$errors->get('company_phone')" class="mt-2" />
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Responsável pela conta</h2>
                    <div>
                        <x-input-label for="owner_name" :value="__('Nome completo')" />
                        <x-text-input id="owner_name" class="mt-1 block w-full" type="text" name="owner_name" :value="old('owner_name')" required />
                        <x-input-error :messages="$errors->get('owner_name')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="owner_email" :value="__('Email de acesso')" />
                        <x-text-input id="owner_email" class="mt-1 block w-full" type="email" name="owner_email" :value="old('owner_email')" required />
                        <x-input-error :messages="$errors->get('owner_email')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="owner_document" :value="__('CPF do responsável')" />
                        <x-text-input id="owner_document" class="mt-1 block w-full cpf-cnpj-mask" type="text" name="owner_document" :value="old('owner_document')" required maxlength="18" inputmode="numeric" />
                        <x-input-error :messages="$errors->get('owner_document')" class="mt-2" />
                    </div>
                </div>

                <div class="space-y-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Plano</h2>
                    <div class="space-y-3">
                        @foreach ($plans as $plan)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-4">
                                <input type="radio" name="plan_code" value="{{ $plan['code'] }}" class="mt-1" @checked(old('plan_code', $loop->first ? $plan['code'] : null) === $plan['code'])>
                                <span class="block">
                                    <span class="block text-sm font-semibold text-slate-900">{{ $plan['name'] }}</span>
                                    <span class="block text-sm text-slate-600">{{ $plan['intervalo_label'] }} · R$ {{ number_format($plan['valor_unitario'], 2, ',', '.') }} por dispositivo</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('plan_code')" class="mt-2" />
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <x-input-label for="password" :value="__('Senha')" />
                        <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="password_confirmation" :value="__('Confirmar senha')" />
                        <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required />
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4">
                    <a href="{{ route('login') }}" class="text-sm text-slate-600 underline">Já tenho acesso</a>
                    <x-primary-button>
                        {{ __('Criar conta') }}
                    </x-primary-button>
                </div>
            </form>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const formatCpfCnpj = (value) => {
                const digits = value.replace(/\D/g, '').slice(0, 14);

                if (digits.length <= 11) {
                    return digits
                        .replace(/(\d{3})(\d)/, '$1.$2')
                        .replace(/(\d{3})(\d)/, '$1.$2')
                        .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                }

                return digits
                    .replace(/^(\d{2})(\d)/, '$1.$2')
                    .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
                    .replace(/\.(\d{3})(\d)/, '.$1/$2')
                    .replace(/(\d{4})(\d)/, '$1-$2');
            };

            document.querySelectorAll('.cpf-cnpj-mask').forEach((input) => {
                input.value = formatCpfCnpj(input.value);
                input.addEventListener('input', (event) => {
                    event.target.value = formatCpfCnpj(event.target.value);
                });
            });

            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function() {
                    ['company_document', 'owner_document'].forEach((id) => {
                        const input = document.getElementById(id);
                        if (input) {
                            input.value = input.value.replace(/\D/g, '');
                        }
                    });
                });
            }
        });
    </script>
</x-guest-layout>