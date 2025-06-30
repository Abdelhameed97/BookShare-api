<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\BookAiSearchService;

class BookAiSearchController extends Controller
{
    protected $aiSearchService;

    public function __construct(BookAiSearchService $aiSearchService)
    {
        $this->aiSearchService = $aiSearchService;
    }

    public function search(Request $request)
    {
        $query = $request->input('query') ?? $request->input('question');
        $sessionId = $request->input('session_id');
        $results = $this->aiSearchService->query($query, $sessionId);
        return response()->json($results);
    }

    public function history($sessionId)
    {
        $messages = $this->aiSearchService->getHistory($sessionId);
        return response()->json(['messages' => $messages]);
    }
}
