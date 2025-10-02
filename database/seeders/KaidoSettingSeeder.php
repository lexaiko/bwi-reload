<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KaidoSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'group' => 'KaidoSetting',
                'name' => 'site_name',
                'locked' => 0,
                'payload' => '"Laravel BWI"',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'KaidoSetting',
                'name' => 'site_active',
                'locked' => 0,
                'payload' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'KaidoSetting',
                'name' => 'registration_enabled',
                'locked' => 0,
                'payload' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'KaidoSetting',
                'name' => 'login_enabled',
                'locked' => 0,
                'payload' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'group' => 'KaidoSetting',
                'name' => 'password_reset_enabled',
                'locked' => 0,
                'payload' => 'true',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('settings')->insert($settings);

        $this->command->info('KaidoSetting settings seeded successfully!');
    }
}
