<?php

namespace Database\Seeders;

use App\Models\Module;
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

        Module::create([
            'name' => 'Bank Transactions',
            'description' => 'Bank Transactions API Service',
            'endpoint' => 'money',
        ]);

        Module::create([
            'name' => 'Routines',
            'description' => 'Follow routines to automate your tasks',
            'endpoint' => 'routines',
        ]);

        Module::create([
            'name' => 'Notes',
            'description' => 'Take notes and keep track of your tasks',
            'endpoint' => 'notes',
        ]);
    }
}
