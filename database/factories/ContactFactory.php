<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => fake()->randomElement([1, 2, 3]),
            'email' => fake()->unique()->safeEmail(),
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-2-3',
            'building' => null,
            'detail' => 'お問い合わせ内容です。',
        ];
    }
}
