<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            ['name' => '緊急'],
            ['name' => '要折返し'],
            ['name' => '対応済み'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }

    }
}
