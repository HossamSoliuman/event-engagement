<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\LotteryEntry;
use App\Models\Membership;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::create([
            'name'     => 'Super Admin',
            'email'    => env('ADMIN_EMAIL', 'admin@eventbomb.com'),
            'password' => Hash::make(env('ADMIN_PASSWORD', 'secret123')),
            'role'     => 'superadmin',
        ]);

        User::create([
            'name'     => 'Event Moderator',
            'email'    => 'mod@eventbomb.com',
            'password' => Hash::make('secret123'),
            'role'     => 'moderator',
        ]);

        $event = Event::create([
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
            'fotobomb_desc'     => 'Snap a photo and we might put it on the big screen!',
            'lottery_desc'      => 'Enter for a chance to win tonight\'s grand prize!',
            'voting_desc'       => 'Vote for your favourite athlete. Results shown live!',
            'membership_desc'   => 'Join the community and get exclusive member perks.',
            'vidiwall_show_uploader'       => true,
            'vidiwall_slideshow_mode'      => false,
            'vidiwall_slideshow_interval'  => 8,
            'voting_options'    => json_encode([
                ['name' => 'Alex Johnson', 'slug' => 'alex-johnson', 'image' => null, 'position' => 'Forward'],
                ['name' => 'Maria Santos', 'slug' => 'maria-santos', 'image' => null, 'position' => 'Midfielder'],
                ['name' => 'Chris Kim',    'slug' => 'chris-kim',    'image' => null, 'position' => 'Defender'],
                ['name' => 'Priya Patel',  'slug' => 'priya-patel',  'image' => null, 'position' => 'Goalkeeper'],
            ]),
        ]);

        try { $event->generateQrCode(); } catch (\Exception $e) {}

        // Demo lottery entries
        foreach (['Ahmed Hassan','Sara Ali','Mohamed Kamal','Nour El-Din','Yasmin Fathy',
                  'Omar Sherif','Lina Mostafa','Khaled Ibrahim','Rania Tarek','Amir Salah'] as $n) {
            LotteryEntry::create([
                'event_id'    => $event->id,
                'name'        => $n,
                'phone'       => '+201' . rand(000000000, 999999999),
                'email'       => Str::slug($n) . '@demo.com',
                'entry_token' => Str::random(32),
            ]);
        }

        // Demo votes
        foreach ([
            ['Alex Johnson','alex-johnson',12],
            ['Maria Santos','maria-santos',8],
            ['Chris Kim','chris-kim',5],
            ['Priya Patel','priya-patel',4],
        ] as [$cname,$cslug,$count]) {
            for ($v = 0; $v < $count; $v++) {
                Vote::create([
                    'event_id'       => $event->id,
                    'candidate_name' => $cname,
                    'candidate_slug' => $cslug,
                    'voter_session'  => Str::random(40),
                    'voter_ip'       => '192.168.'.rand(1,254).'.'.rand(1,254),
                ]);
            }
        }

        // Demo memberships
        foreach ([
            ['Ahmed Nour','ahmed.nour@email.com','Al Ahly'],
            ['Sara Kamal','sara.k@email.com','Zamalek'],
            ['Mike Ross','mike.r@email.com',null],
        ] as [$mn,$me,$mt]) {
            Membership::create([
                'event_id'          => $event->id,
                'name'              => $mn,
                'email'             => $me,
                'phone'             => '+2010'.rand(10000000,99999999),
                'team_preference'   => $mt,
                'newsletter_opt_in' => true,
                'membership_number' => 'EB-'.strtoupper(Str::random(6)),
            ]);
        }
    }
}
