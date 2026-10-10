<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function 未認証ユーザーは管理画面にアクセスできない(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function 正しい情報でログインできる(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
    }

    /** @test */
    public function 誤ったパスワードではログインできない(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /** @test */
    public function 管理画面にお問い合わせ一覧が表示される(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $response->assertViewHas('contacts');
    }

    /** @test */
    public function 管理画面の一覧は7件ごとにページネーションされる(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(10)->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(200);
        $this->assertCount(7, $response->viewData('contacts')->items());
    }

    /** @test */
    public function お問い合わせ詳細が表示される(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->get("/admin/contacts/{$contact->id}");

        $response->assertStatus(200);
        $response->assertViewHas('contact');
    }

    /** @test */
    public function お問い合わせを削除できる(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/contacts/{$contact->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    /** @test */
    public function タグを追加できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', ['name' => '新しいタグ']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => '新しいタグ']);
    }

    /** @test */
    public function 重複したタグ名は登録できない(): void
    {
        $user = User::factory()->create();
        Tag::factory()->create(['name' => '緊急']);

        $response = $this->actingAs($user)->post('/admin/tags', ['name' => '緊急']);

        $response->assertSessionHasErrors(['name' => 'そのタグ名は既に使用されています']);
    }

    /** @test */
    public function タグ名を更新できる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => '変更前']);

        $response = $this->actingAs($user)->put("/admin/tags/{$tag->id}", ['name' => '変更後']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => '変更後']);
    }

    /** @test */
    public function タグを削除できる(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->delete("/admin/tags/{$tag->id}");

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}
