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
                'slug' => 'starter-pack',
                'description' => 'Perfect for trying out Phoenix AI',
                'credits' => 1000,
                'price_cents' => 499, // $4.99
                'currency' => 'USD',
                'discount_percentage' => 0,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                'features' => [
                    '1,000 AI Credits',
                    'Access to all AI assistants',
                    'Basic chat features',
                    '24/7 support'
                ],
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Best value for regular users',
                'credits' => 5000,
                'price_cents' => 1999, // $19.99
                'currency' => 'USD',
                'discount_percentage' => 20,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                'features' => [
                    '5,000 AI Credits',
                    '20% bonus credits',
                    'Priority support',
                    'Advanced AI features',
                    'Image generation',
                    'Voice messages'
                ],
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'description' => 'For teams and heavy users',
                'credits' => 15000,
                'price_cents' => 4999, // $49.99
                'currency' => 'USD',
                'discount_percentage' => 30,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                'features' => [
                    '15,000 AI Credits',
                    '30% bonus credits',
                    'Premium support',
                    'Custom AI assistants',
                    'API access',
                    'Analytics dashboard',
                    'Team collaboration'
                ],
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited power for enterprises',
                'credits' => 50000,
                'price_cents' => 12999, // $129.99
                'currency' => 'USD',
                'discount_percentage' => 40,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
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
                'stripe_price_id' => null,
                'paypal_plan_id' => null,
            ],
        ];

        foreach ($packages as $packageData) {
            CreditPackage::updateOrCreate(
                ['slug' => $packageData['slug']],
                $packageData
            );
        }
    }
}