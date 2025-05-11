<?php

namespace Database\Seeders;

use App\Models\ApiService;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ApiService::create([
            'name' => 'GoCardless',
            'description' => 'GoCardless API Service',
            'icon' => 'https://cdn.brandfetch.io/idNfPDHpG3/w/400/h/400/theme/dark/icon.png?c=1dxbfHSJFAPEGdCLU4o5B',
            'url' => 'https://bankaccountdata.gocardless.com',
        ]);
    }
}
