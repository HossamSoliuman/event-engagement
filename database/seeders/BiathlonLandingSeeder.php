<?php

namespace Database\Seeders;

use App\Models\Event;
use Illuminate\Database\Seeder;

/**
 * Recreates the live "La Vita IBU Biathlon World Cup 2027" landing page locally
 * so the landing designer can be worked on against a realistic 6-tile event.
 *
 * The referenced images live in storage/app/public and are pulled from the
 * production site; run `php artisan storage:link` if they do not resolve.
 */
class BiathlonLandingSeeder extends Seeder
{
    private const SLUG = 'la-vita-ibu-biathlon-wold-cup-2027-z4bl';

    public function run(): void
    {
        $logo = 'logos/FZlvsIUth5cqYFRY4Kt8W3Cz2fUfA6vOSmhzvGRb.jpg';

        $tiles = [
            'fotobomb' => [
                'label' => 'SEND US YOUR PHOTO or VIDEO',
                'sublabel' => 'presented by Benediktiner',
                'bg_color' => '#1b22c2',
                'image_path' => 'tiles/fotobomb/AwzjK94RDR109Zr6clOjI4Eq9NqgHQEaaHMCWagw.png',
                'link_url' => '',
                'link_external' => false,
            ],
            'voting' => [
                'label' => 'Who is your ATHLETE OF THE DAY?',
                'sublabel' => 'presented by HÖRMANN',
                'bg_color' => '#ffce57',
                'image_path' => 'tiles/voting/5XpPUmY9YCqyXnAshhlOMvS3Za5P644TwDyJswIp.svg',
                'link_url' => '',
                'link_external' => false,
            ],
            'lottery' => [
                'label' => 'Win a holiday weekend in Ostersund',
                'sublabel' => 'presented by VISITOSTERSUND',
                'bg_color' => '#ff17e3',
                'image_path' => 'tiles/lottery/WdLQOCapYbKi7Dmf1PNkpkhdFIATswKeuL4yT7z1.webp',
                'link_url' => 'https://visitostersund.se/en/',
                'link_external' => true,
            ],
            'membership' => [
                'label' => 'Be an IBU Family Member',
                'sublabel' => '#biathlonworld',
                'bg_color' => '#adffbc',
                'image_path' => 'tiles/membership/vlnEQM4H0qQUyV3ZM3D7KDTv9SWdUkrzYn2TgB2Z.svg',
                'link_url' => 'https://biathlonworld.com',
                'link_external' => true,
            ],
            'quiz' => [
                'label' => 'Are you the ultimate Biathlon Fan?',
                'sublabel' => 'presented by LA VITA',
                'bg_color' => '#f7f9db',
                'image_path' => 'tiles/quiz/Q5j85O0la7S8Vzo7m4017PXntxTbxdBReQlqrZ4g.jpg',
                'link_url' => '',
                'link_external' => false,
            ],
            'fanclash' => [
                'label' => 'Team A or Team B?',
                'sublabel' => 'presented by Jämtkraft',
                'bg_color' => '#476fd2',
                'image_path' => 'tiles/fanclash/HIYkv8CWsM4Ggpvgbj4oORX9lohDGO5YgxkAcEOg.jpg',
                'link_url' => '',
                'link_external' => false,
            ],
        ];

        $attributes = [
            'name' => 'La Vita IBU Biathlon Wold Cup 2027',
            'subtitle' => 'Fan Experience',
            'is_active' => true,
            'logo_path' => $logo,
            'primary_color' => '#e4e7eb',
            'secondary_color' => '#000000',
            'accent_color' => '#ff0004',
            'font_heading' => 'Arial',
            'font_body' => 'DM Sans',
            'landing_style' => 'clean',
            'landing_wordmark' => 'FAN EXPERIENCE',
            'landing_hero_title' => 'Click in and get BUSTED',
            'landing_hero_sub' => 'Check everything and win goodes and prices!',
            'landing_design' => Event::landingDesignDefaults(),
            'vidiwall_overlay_text' => '#biathlonworld',
            'module_fotobomb' => true,
            'module_voting' => true,
            'module_lottery' => true,
            'module_membership' => true,
            'module_quiz' => true,
            'module_fanclash' => true,
        ];

        foreach ($tiles as $module => $config) {
            $attributes["tile_{$module}_config"] = array_merge(Event::tileConfigDefaults(), $config);
        }

        $event = Event::withTrashed()->updateOrCreate(['slug' => self::SLUG], $attributes);
        $event->restore();

        $this->command?->info("Landing page ready: {$event->getGuestUrl()}");
    }
}
