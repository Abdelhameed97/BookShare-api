<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BookAiSearchService;
use App\Models\Book;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookAiSearchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_returns_results_for_existing_book()
    {
        // إضافة مستخدم وتصنيف للاختبار
        $user = \App\Models\User::factory()->create();
        $category = \App\Models\Category::create([
            'name' => 'Business',
            'type' => 'Tech',
        ]);
        // إضافة كتاب للاختبار
        Book::create([
            'title' => 'odoo',
            'description' => 'ERP system',
            'author' => 'Odoo Team',
            'price' => 100,
            'user_id' => $user->id,
            'category_id' => $category->id,
            'condition' => 'new',
        ]);

        $service = new BookAiSearchService();
        $result = $service->query('odoo');

        $this->assertArrayHasKey('results', $result);
        $this->assertGreaterThan(0, count($result['results']));
    }

    public function test_search_returns_ai_message_for_unknown_book()
    {
        $service = new BookAiSearchService();
        $result = $service->query('كتاب غير موجود');

        $this->assertArrayHasKey('message', $result);
        $this->assertNotEmpty($result['message']);
    }
}
