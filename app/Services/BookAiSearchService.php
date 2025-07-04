<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Models\ChatMessage;
use App\Models\Book; // Ensure this model exists
use Illuminate\Support\Str;

class BookAiSearchService
{
    /**
     * Smart search: searches the database first, then uses AI if no results are found.
     * @param string $question
     * @param string|null $sessionId
     * @return array
     */
    public function query(string $question, ?string $sessionId = null): array
    {
        // بحث في الكتب حسب الحقول المعتادة
        $results = Book::where('title', 'like', "%$question%")
            ->orWhere('description', 'like', "%$question%")
            ->orWhere('condition', 'like', "%$question%")
            ->orWhere('price', 'like', "%$question%")
            ->orWhere('rental_price', 'like', "%$question%")
            ->orWhere('educational_level', 'like', "%$question%")
            ->orWhere('genre', 'like', "%$question%")
            ->orWhere('status', 'like', "%$question%")
            ->orWhere('author', 'like', "%$question%") // بحث في المؤلف
            ->orWhere('content', 'like', "%$question%") // بحث في نص الكتاب إذا كان موجود
            // بحث في اسم التصنيف المرتبط
            ->orWhereHas('category', function($q) use ($question) {
                $q->where('name', 'like', "%$question%")
                  ->orWhere('type', 'like', "%$question%") ;
            })
            ->limit(5)
            ->get();

        if ($results->count() > 0) {
            return [
                'results' => $results,
            ];
        }

        // إذا لم توجد نتائج، استخدم الذكاء الاصطناعي
        $answer = $this->aiFallback($question) ?? $this->staticSmartAnswer($question);
        return [
            'message' => $answer,
        ];
    }

    /**
     * Fallback chain: HuggingFace → Gemini → OpenAI
     */
    private function aiFallback(string $message): ?string
    {
        // 1. HuggingFace
        $hfKey = config('services.huggingface.key');
        if ($hfKey) {
            $hfResponse = $this->askHuggingFace($message, $hfKey);
            if ($hfResponse) return $hfResponse;
        }
        // 2. Google Gemini
        $googleKey = config('services.google.api_key');
        if ($googleKey) {
            $googleResponse = $this->askGoogleGemini($message, $googleKey);
            if ($googleResponse) return $googleResponse;
        }
        // 3. OpenAI
        $openaiKey = config('services.openai.key');
        if ($openaiKey) {
            $openaiResponse = $this->askOpenAi($message, $openaiKey);
            if ($openaiResponse) return $openaiResponse;
        }
        return null;
    }

    /**
     * استدعاء نموذج HuggingFace
     */
    private function askHuggingFace(string $message, string $hfToken): ?string
    {
        try {
            $endpoint = 'https://api-inference.huggingface.co/models/tiiuae/falcon-7b-instruct';
            $payload = [ 'inputs' => $message ];
            $response = \Http::withHeaders([
                'Authorization' => 'Bearer ' . $hfToken,
                'Accept' => 'application/json',
            ])->post($endpoint, $payload);
            \Log::info('HF Response', [
                'model' => 'tiiuae/falcon-7b-instruct',
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);
            $result = $response->json();
            if (is_array($result) && isset($result[0]['generated_text']) && $result[0]['generated_text'] !== '') {
                return $result[0]['generated_text'];
            }
            if (is_array($result) && isset($result[0]['text']) && $result[0]['text'] !== '') {
                return $result[0]['text'];
            }
            return null;
        } catch (\Throwable $e) {
            \Log::error('HF API: Exception', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Ask Google Gemini Generative Language API
     */
    private function askGoogleGemini(string $message, string $apiKey): ?string
    {
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post('https://generativelanguage.googleapis.com/v1/models/gemini-1.0-pro:generateContent?key=' . $apiKey, [
                'contents' => [
                    ['parts' => [ ['text' => $message] ]]
                ]
            ]);
            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                    return trim($result['candidates'][0]['content']['parts'][0]['text']);
                }
            }
            \Log::warning('Google Gemini fallback: static smart answer used.', ['body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            \Log::error('Google Gemini API: Exception', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Ask OpenAI ChatGPT
     */
    private function askOpenAi(string $message, string $apiKey): ?string
    {
        // Log the actual API key used for debugging
        \Log::info('OpenAI API Key Used', ['key' => $apiKey]);
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => $message],
                ],
                'max_tokens' => 256,
                'temperature' => 0.7,
            ]);
            \Log::info('OpenAI RAW Response', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);
            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['choices'][0]['message']['content'])) {
                    return trim($result['choices'][0]['message']['content']);
                }
            }
            \Log::warning('OpenAI fallback: static smart answer used.', ['body' => $response->body()]);
            return null;
        } catch (\Throwable $e) {
            \Log::error('OpenAI API: Exception', ['exception' => $e->getMessage()]);
            return null;
        }
    }

    // Detect if the text is Arabic
    private function isArabic($text): bool
    {
        return preg_match('/\p{Arabic}/u', $text);
    }

    /**
     * Static smart fallback answer if AI fails, responds in the same language as the question
     */
    private function staticSmartAnswer(string $message): string
    {
        $isArabic = $this->isArabic($message);
        if (stripos($message, 'كتاب') !== false || stripos($message, 'book') !== false) {
            return $isArabic ? 'يبدو أنك تبحث عن كتاب. يمكنك تحديد اسم الكتاب أو المؤلف؟' : 'It seems you are looking for a book. Can you specify the book title or author?';
        }
        if (stripos($message, 'hello') !== false || stripos($message, 'مرحبا') !== false) {
            return $isArabic ? 'مرحباً بك! كيف يمكنني مساعدتك اليوم؟' : 'Welcome! How can I help you today?';
        }
        if (stripos($message, 'ai') !== false || stripos($message, 'artificial intelligence') !== false || stripos($message, 'ذكاء اصطناعي') !== false) {
            return $isArabic ? 'الذكاء الاصطناعي (AI) هو مجال في علوم الحاسوب يهدف إلى تطوير أنظمة قادرة على محاكاة الذكاء البشري.' : 'Artificial Intelligence (AI) is a field in computer science that aims to develop systems capable of simulating human intelligence.';
        }
        return $isArabic ? 'عذراً، لم أتمكن من فهم سؤالك. حاول أن توضح أكثر.' : 'Sorry, I could not understand your question. Please try to clarify.';
    }
}
