<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlansSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Adhésion Particulier',
                'description' => 'Adhésion pour les particuliers',
                'price_chf' => 50.00,
                'price_eur' => 50.00,
                'duration_months' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'Petite commune / entreprise',
                'description' => 'Pour les petites communes et entreprises',
                'price_chf' => 75.00,
                'price_eur' => 75.00,
                'duration_months' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'Moyenne commune / entreprise',
                'description' => 'Pour les moyennes communes et entreprises',
                'price_chf' => 150.00,
                'price_eur' => 150.00,
                'duration_months' => 12,
                'is_active' => true,
            ],
            [
                'name' => 'Grande commune / entreprise',
                'description' => 'Pour les grandes communes et entreprises',
                'price_chf' => 300.00,
                'price_eur' => 300.00,
                'duration_months' => 12,
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}