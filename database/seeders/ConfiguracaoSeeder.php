<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Configuracao;

class ConfiguracaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Configuracao::updateOrCreate(
            ['id' => 1],
            [
                'apiUrl' => 'http://localhost:8000/api/produtos',
                'apiRefreshInterval' => 60,
                'priceColor' => '#000000',
                'offerColor' => '#FF0000',
                'rowBackgroundColor' => '#FFFFFFFF',
                'borderColor' => '#000000',
                'appBackgroundColor' => '#FFFFFF',
                'mainBorderColor' => '#000000',
                'gradientStartColor' => '#FFFFFF',
                'gradientEndColor' => '#FFFFFF',
                'useGradient' => false,
                'gradientStop1' => 0.0,
                'gradientStop2' => 1.0,
                'showBorder' => true,
                'isMainBorderEnabled' => false,
                'showImage' => true,
                'imageSize' => 64,
                'isPaginationEnabled' => false,
                'pageSize' => 10,
                'paginationInterval' => 5,
            ]
        );
    }
}
