<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お問い合わせ一覧をJSONで取得できる(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertStatus(200);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'first_name', 'last_name', 'gender', 'gender_label', 'email', 'category' => ['id', 'content'], 'tags'],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    /** @test */
    public function お問い合わせ詳細をJSONで取得できる(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->getJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.id', $contact->id);
    }

    /** @test */
    public function 存在しないIDの場合は404を返す(): void
    {
        $response = $this->getJson('/api/v1/contacts/999');

        $response->assertStatus(404);
    }

    /** @test */
    public function お問い合わせを作成すると201が返る(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'api@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-2-3',
            'category_id' => $category->id,
            'detail' => 'APIからの登録です。',
            'tag_ids' => [$tag->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('contacts', ['email' => 'api@example.com']);
    }

    /** @test */
    public function 不正な入力の場合は422で日本語のエラーが返る(): void
    {
        $response = $this->postJson('/api/v1/contacts', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['first_name' => '姓を入力してください']);
    }

    /** @test */
    public function 存在しないカテゴリを指定すると422が返る(): void
    {
        $response = $this->postJson('/api/v1/contacts', [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'api@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-2-3',
            'category_id' => 999,
            'detail' => 'APIからの登録です。',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id' => '選択されたカテゴリーが存在しません']);
    }

    /** @test */
    public function お問い合わせを更新すると200が返る(): void
    {
        $contact = Contact::factory()->create();
        $category = Category::factory()->create();

        $response = $this->putJson("/api/v1/contacts/{$contact->id}", [
            'first_name' => '佐藤',
            'last_name' => '花子',
            'gender' => 2,
            'email' => 'updated@example.com',
            'tel' => '08011112222',
            'address' => '大阪府大阪市1-1-1',
            'category_id' => $category->id,
            'detail' => '更新後の内容です。',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'email' => 'updated@example.com',
        ]);
    }

    /** @test */
    public function お問い合わせを削除すると204が返る(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
