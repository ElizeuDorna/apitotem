<?php

use App\Models\EmpresaFinanceiroConfig;

return [
    'self_service' => [
        'trial_days' => 7,
        'plans' => [
            'mensal' => [
                'name' => 'Plano Mensal',
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_30_DIAS,
                'valor_unitario' => 29.90,
            ],
            'trimestral' => [
                'name' => 'Plano Trimestral',
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_90_DIAS,
                'valor_unitario' => 27.90,
            ],
            'semestral' => [
                'name' => 'Plano Semestral',
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_180_DIAS,
                'valor_unitario' => 24.90,
            ],
            'anual' => [
                'name' => 'Plano Anual',
                'intervalo_cobranca_dias' => EmpresaFinanceiroConfig::INTERVALO_1_ANO,
                'valor_unitario' => 19.90,
            ],
        ],
    ],
];