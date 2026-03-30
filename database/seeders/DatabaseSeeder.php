<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create superadmin
        User::create([
            'name'     => 'Super Admin',
            'email'    => env('ADMIN_EMAIL', 'admin@eventbomb.com'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'secret123')),
            'role'     => 'superadmin',
        ]);

        // Create a demo event
        Event::create([
            'name'              => 'Championship Night 2025',
            'slug'              => env('EVENT_SLUG', 'live-event-2025'),
            'subtitle'          => 'The Ultimate Fan Experience',
            'description'       => 'Join us for an unforgettable night of sport, competition, and community.',
            'primary_color'     => '#FF3D00',
            'secondary_color'   => '#0D0D1A',
            'accent_color'      => '#FFD700',
            'is_active'         => true,
            'module_fotobomb'   => true,
            'module_lottery'    => true,
            'module_voting'     => true,
            'module_membership' => true,
            'voting_options'    => json_encode([
                ['name' => 'Alex Johnson', 'image' => null],
                ['name' => 'Maria Santos',  'image' => null],
                ['name' => 'Chris Kim',     'image' => null],
                ['name' => 'Priya Patel',   'image' => null],
            ]),
        ]);
    }
}