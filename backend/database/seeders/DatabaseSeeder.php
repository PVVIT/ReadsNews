<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Categories mẫu
        DB::table('categories')->insertOrIgnore([
            ['id' => 1, 'name' => 'Tin mới nhất', 'slug' => 'tin-moi-nhat', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Thế giới',     'slug' => 'the-gioi',     'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'name' => 'Kinh doanh',   'slug' => 'kinh-doanh',   'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'name' => 'Thể thao',     'slug' => 'the-thao',     'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'name' => 'Công nghệ',    'slug' => 'cong-nghe',    'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Sources mẫu
        DB::table('sources')->insertOrIgnore([
            [
                'id' => 1,
                'name' => 'VnExpress',
                'slug' => 'vnexpress-tin-moi',
                'website_url' => 'https://vnexpress.net',
                'rss_url' => 'https://vnexpress.net/rss/tin-moi-nhat.rss',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Tuổi Trẻ',
                'slug' => 'tuoitre-tin-moi',
                'website_url' => 'https://tuoitre.vn',
                'rss_url' => 'https://tuoitre.vn/rss/tin-moi-nhat.rss',
                'category_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // 3. Voices mẫu
        DB::table('voices')->insertOrIgnore([
            ['id' => 1, 'provider' => 'web_speech', 'code' => 'default',          'name' => 'Giọng trình duyệt',   'language' => 'vi-VN', 'gender' => 'neutral', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'provider' => 'google',     'code' => 'vi-VN-Wavenet-A',  'name' => 'Google Nữ (WaveNet)', 'language' => 'vi-VN', 'gender' => 'female',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'provider' => 'google',     'code' => 'vi-VN-Wavenet-B',  'name' => 'Google Nam (WaveNet)','language' => 'vi-VN', 'gender' => 'male',    'created_at' => now(), 'updated_at' => now()],
            ['id' => 4, 'provider' => 'google',     'code' => 'vi-VN-Standard-A', 'name' => 'Google Nữ (Standard)','language' => 'vi-VN', 'gender' => 'female',  'created_at' => now(), 'updated_at' => now()],
            ['id' => 5, 'provider' => 'google',     'code' => 'vi-VN-Standard-B', 'name' => 'Google Nam (Standard)','language' => 'vi-VN', 'gender' => 'male',    'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
