<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function お問い合わせフォーム入力ページが表示される(): void
    {
        Category::factory()->count(3)->create();
        Tag::factory()->count(2)->create();

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
    }

    /** @test */
    public function 未入力の場合はバリデーションエラーになる(): void
    {
        $response = $this->post('/contacts/confirm', []);

        $response->assertSessionHasErrors([
            'first_name' => '姓を入力してください',
            'last_name' => '名を入力してください',
            'gender' => '性別を選択してください',
            'email' => 'メールアドレスを入力してください',
            'tel' => '電話番号を入力してください',
            'address' => '住所を入力してください',
            'category_id' => 'お問い合わせの種類を選択してください',
            'detail' => 'お問い合わせ内容を入力してください',
        ]);
    }

    /** @test */
    public function メール形式が不正な場合はバリデーションエラーになる(): void
    {
        $category = Category::factory()->create();

        $response = $this->post('/contacts/confirm', $this->validInput($category->id, ['email' => 'invalid']));

        $response->assertSessionHasErrors(['email' => 'メールアドレスはメール形式で入力してください']);
    }

    /** @test */
    public function 電話番号が桁数不足の場合はバリデーションエラーになる(): void
    {
        $category = Category::factory()->create();

        $response = $this->post('/contacts/confirm', $this->validInput($category->id, ['tel' => '090']));

        $response->assertSessionHasErrors(['tel' => '電話番号はハイフンなしの10〜11桁で入力してください']);
    }

    /** @test */
    public function 正しい入力で確認ページが表示される(): void
    {
        $category = Category::factory()->create();

        $response = $this->post('/contacts/confirm', $this->validInput($category->id));

        $response->assertStatus(200);
        $response->assertViewHas('validated');
        $response->assertViewHas('category');
    }

    /** @test */
    public function お問い合わせを送信するとデータベースに保存されサンクスページへ遷移する(): void
    {
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->post('/contacts', $this->validInput($category->id, ['tag_ids' => [$tag->id]]));

        $response->assertRedirect('/thanks');
        $this->assertDatabaseHas('contacts', [
            'email' => 'test@example.com',
            'category_id' => $category->id,
        ]);
        $this->assertDatabaseHas('contact_tag', ['tag_id' => $tag->id]);
    }

    /** @test */
    public function サンクスページが表示される(): void
    {
        $response = $this->get('/thanks');

        $response->assertStatus(200);
    }

    /**
     * テスト用の正しい入力値を返す
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validInput(int $categoryId, array $overrides = []): array
    {
        return array_merge([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'test@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-2-3',
            'building' => 'テストマンション101',
            'category_id' => $categoryId,
            'detail' => 'お問い合わせ内容です。',
        ], $overrides);
    }
}
