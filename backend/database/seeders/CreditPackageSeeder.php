<?php

namespace Database\Seeders;

use App\Models\CreditPackage;
use Illuminate\Database\Seeder;

class CreditPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter Pack',
                'description' => 'Perfect for trying out Phoenix AI',
                'credits' => 1000,
                'price_cents' => 499, // $4.99
                'currency' => 'USD',
                'tier' => 1,
                'discount_percentage' => 0,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                'purchase_count' => 0,
                'features' => [
                    '1,000 AI Credits',
                    'Access to all AI assistants',
                    'Basic chat features',
                    '24/7 support'
                ],
                'ai_access' => null,
                'sale_ends_at' => null,
            ],
            [
                'name' => 'Professional',
                'description' => 'Best value for regular users',
                'credits' => 5000,
                'price_cents' => 1999, // $19.99
                'currency' => 'USD',
                'tier' => 2,
                'discount_percentage' => 20,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                'purchase_count' => 0,
                'features' => [
                    '5,000 AI Credits',
                    '20% bonus credits',
                    'Priority support',
                    'Advanced AI features',
                    'Image generation',
                    'Voice messages'
                ],
                'ai_access' => null,
                'sale_ends_at' => null,
            ],
            [
                'name' => 'Business',
                'description' => 'For teams and heavy users',
                'credits' => 15000,
                'price_cents' => 4999, // $49.99
                'currency' => 'USD',
                'tier' => 3,
                'discount_percentage' => 30,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                'purchase_count' => 0,
                'features' => [
                    '15,000 AI Credits',
                    '30% bonus credits',
                    'Premium support',
                    'Custom AI assistants',
                    'API access',
                    'Analytics dashboard',
                    'Team collaboration'
                ],
                'ai_access' => null,
                'sale_ends_at' => null,
            ],
            [
                'name' => 'Enterprise',
                'description' => 'Unlimited power for enterprises',
                'credits' => 50000,
                'price_cents' => 12999, // $129.99
                'currency' => 'USD',
                'tier' => 4,
                'discount_percentage' => 40,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
                'purchase_count' => 0,
                'features' => [
                    '50,000 AI Credits',
                    '40% bonus credits',
                    'White-label options',
                    'Dedicated support',
                    'Custom integrations',
                    'Advanced analytics',
                    'SLA guarantee',
                    'Multi-tenant support'
                ],
                'ai_access' => null,
                'sale_ends_at' => null,
            ],
        ];

        foreach ($packages as $packageData) {
            // Remove slug from the data since the table doesn't have this column
            unset($packageData['slug']);
            
            CreditPackage::updateOrCreate(
                ['name' => $packageData['name']],
                $packageData
            );
        }
    }
}