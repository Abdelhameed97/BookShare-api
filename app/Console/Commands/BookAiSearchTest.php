<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BookAiSearchService;

class BookAiSearchTest extends Command
{
    protected $signature = 'bookai:test-search {query}';
    protected $description = 'Test AI book search from CLI (بديل rag_query.py)';

    public function handle(BookAiSearchService $service)
    {
        $query = $this->argument('query');
        $results = $service->search($query);
        $this->info(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
