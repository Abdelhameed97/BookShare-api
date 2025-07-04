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
        // استخراج السعر من السؤال إذا وُجد رقم
        preg_match('/(\d+)/', $question, $matches);
        $price = isset($matches[1]) ? $matches[1] : null;
        // استخراج كلمة بحث نصية (مثلاً اسم الكتاب أو المؤلف)
        $text = trim(preg_replace('/\d+/', '', $question));
        $text = $text !== '' ? $text : null;

        // ترجمة الكلمة إذا كانت بالعربي
        $translated = $text;
        if ($text && $this->isArabic($text)) {
            $translated = $this->translateToEnglish($text) ?: $text;
        }

        $results = Book::where(function($q) use ($text, $translated, $price) {
            if ($text && $price) {
                $q->where(function($qq) use ($text, $translated) {
                    $qq->where('title', 'like', "%$text%")
                       ->orWhere('title', 'like', "%$translated%")
                       ->orWhere('description', 'like', "%$text%")
                       ->orWhere('description', 'like', "%$translated%")
                       ->orWhere('author', 'like', "%$text%")
                       ->orWhere('author', 'like', "%$translated%")
                       ->orWhereHas('category', function($q2) use ($text, $translated) {
                           $q2->where('name', 'like', "%$text%")
                              ->orWhere('name', 'like', "%$translated%")
                              ->orWhere('type', 'like', "%$text%")
                              ->orWhere('type', 'like', "%$translated%") ;
                       });
                })
                ->where(function($qq) use ($price) {
                    $qq->where('price', $price)
                       ->orWhere('rental_price', $price);
                });
            } elseif ($text) {
                $q->where('title', 'like', "%$text%")
                  ->orWhere('title', 'like', "%$translated%")
                  ->orWhere('description', 'like', "%$text%")
                  ->orWhere('description', 'like', "%$translated%")
                  ->orWhere('author', 'like', "%$text%")
                  ->orWhere('author', 'like', "%$translated%")
                  ->orWhereHas('category', function($q2) use ($text, $translated) {
                      $q2->where('name', 'like', "%$text%")
                         ->orWhere('name', 'like', "%$translated%")
                         ->orWhere('type', 'like', "%$text%")
                         ->orWhere('type', 'like', "%$translated%") ;
                  });
            } elseif ($price) {
                $q->where('price', $price)
                  ->orWhere('rental_price', $price);
            }
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

    // ترجمة نص للعربية إلى الإنجليزية باستخدام Google Translate API إذا كان متاحًا
    private function translateToEnglish($text): ?string
    {
        $apiKey = config('services.google.api_key');
        if (!$apiKey) return null;
        try {
            $response = \Http::post('https://translation.googleapis.com/language/translate/v2', [
                'q' => $text,
                'target' => 'en',
                'format' => 'text',
                'key' => $apiKey,
            ]);
            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['data']['translations'][0]['translatedText'])) {
                    return $result['data']['translations'][0]['translatedText'];
                }
            }
        } catch (\Throwable $e) {
            \Log::error('Google Translate API: Exception', ['exception' => $e->getMessage()]);
        }
        return null;
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
        
        // Project/Platform info
        if (
            stripos($message, 'عن المشروع') !== false ||
            stripos($message, 'عن المنصة') !== false ||
            stripos($message, 'عن كتابي') !== false ||
            stripos($message, 'bookshare') !== false ||
            stripos($message, 'final project') !== false ||
            stripos($message, 'project') !== false ||
            stripos($message, 'platform') !== false
        ) {
            return $isArabic
                ? 'مشروع "BookShare" هو منصة رقمية متكاملة لبيع، تأجير، وتبادل الكتب المستعملة، مع دعم التبرع، التوصيل، ونظام تقييم المستخدمين. تستهدف المنصة الطلاب، القراء، أصحاب المكتبات، المؤلفين، ودور النشر. تعالج مشاكل ارتفاع أسعار الكتب وصعوبة إيجادها، وتوفر بحثًا متقدمًا، رسائل مباشرة، محفظة إلكترونية، عروض وخصومات، دعم فني، حماية بيانات، دعم ذوي الهمم، ونظام إبلاغ وتقييم سريع. تشمل الميزات: التحقق بالهوية، الدفع الإلكتروني، تتبع الطلبات، منتدى مجتمعي، ألعاب تعليمية، دعم القصص المصورة، التبرع للمدارس، ونظام نقاط وجوائز. المنصة تلتزم بسياسات خصوصية صارمة، وتوفر تجربة آمنة وسهلة للجميع، مع نموذج عمل يعتمد على عمولة المبيعات والإيجار، الإعلانات، والاشتراكات المميزة.'
                : 'BookShare is a comprehensive digital platform for selling, renting, exchanging, and donating used books, with delivery, user rating, and ID verification. It targets students, readers, library owners, authors, and publishers. BookShare solves problems of high book prices and scarcity, offering advanced search, direct messaging, e-wallet, offers, customer support, data protection, accessibility, and a fast reporting/review system. Features include ID and phone verification, secure payments, order tracking, community forums, educational games, support for comics, book donation to schools, and a gamified points/rewards system. The platform follows strict privacy policies, provides a safe and easy experience for all, and its business model is based on sales/rental commissions, ads, and premium subscriptions.';
        }
        
        // FAQ and About page detection
        if (
            stripos($message, 'about') !== false ||
            stripos($message, 'عن الموقع') !== false ||
            stripos($message, 'عن الشركة') !== false ||
            stripos($message, 'about us') !== false ||
            stripos($message, 'about company') !== false ||
            stripos($message, 'موقع') !== false ||
            stripos($message, 'website') !== false ||
            stripos($message, 'الخدمات') !== false ||
            stripos($message, 'مساعدة') !== false ||
            stripos($message, 'كيف يساعدني') !== false ||
            stripos($message, 'what can this site do') !== false ||
            stripos($message, 'services') !== false ||
            stripos($message, 'help') !== false
        ) {
            return $isArabic
                ? "الأسئلة الشائعة حول BookShare:\n\n1. ما هو موقع BookShare؟\nBookShare هو منصة رقمية لبيع، تأجير، وتبادل الكتب المستعملة، مع إمكانية التبرع بالكتب ودعم التوصيل ونظام تقييم المستخدمين.\n\n2. ما هي الخدمات التي يقدمها الموقع؟\n- بيع وشراء الكتب المستعملة\n- تأجير وتبادل الكتب\n- التبرع بالكتب للمدارس أو الجمعيات\n- بحث متقدم وتصنيفات متنوعة\n- محفظة إلكترونية ودفع آمن\n- عروض وخصومات\n- دعم فني مباشر\n- منتدى مجتمعي ومراجعات\n\n3. كيف يمكن أن يساعدني الموقع؟\n- إيجاد كتب بأسعار مناسبة في جميع المجالات\n- التواصل مع بائعين أو مستأجرين قريبين منك\n- الاستفادة من العروض والخصومات\n- بيع كتبك القديمة بسهولة\n- التبرع بالكتب لمن يحتاجها\n- الحصول على توصيات ذكية للكتب\n\nللمزيد من التفاصيل تصفح أقسام الموقع أو تواصل مع الدعم الفني."
                : "Frequently Asked Questions about BookShare:\n\n1. What is BookShare?\nBookShare is a digital platform for selling, renting, exchanging, and donating used books, with delivery, user rating, and secure payments.\n\n2. What services does the site offer?\n- Buy and sell used books\n- Rent and exchange books\n- Donate books to schools or charities\n- Advanced search and categories\n- E-wallet and secure payments\n- Offers and discounts\n- Direct customer support\n- Community forum and reviews\n\n3. How can this site help me?\n- Find affordable books in all fields\n- Connect with sellers or renters near you\n- Benefit from offers and discounts\n- Easily sell your old books\n- Donate books to those in need\n- Get smart book recommendations\n\nFor more details, browse the site sections or contact support.";
        }
        
        // Greetings
        if (
            stripos($message, 'hello') !== false ||
            stripos($message, 'مرحبا') !== false ||
            stripos($message, 'اهلا') !== false ||
            stripos($message, 'أهلاً') !== false ||
            stripos($message, 'هاي') !== false ||
            stripos($message, 'hi') !== false ||
            stripos($message, 'hey') !== false ||
            stripos($message, 'السلام عليكم') !== false ||
            stripos($message, 'صباح الخير') !== false ||
            stripos($message, 'مساء الخير') !== false ||
            stripos($message, 'ايه الاخبار') !== false ||
            stripos($message, 'how are you') !== false ||
            stripos($message, 'good morning') !== false ||
            stripos($message, 'good evening') !== false ||
            stripos($message, 'whats up') !== false ||
            stripos($message, 'what\'s up') !== false
        ) {
            return $isArabic
                ? 'مرحباً بك في BookShare! كيف يمكنني مساعدتك اليوم؟ 😊'
                : 'Welcome to BookShare! How can I help you today? 😊';
        }
        
        // Payment methods
        if (stripos($message, 'طرق الدفع') !== false || stripos($message, 'payment method') !== false) {
            return $isArabic
                ? 'نقبل الدفع نقداً عند الاستلام، أو عبر بطاقات الائتمان، أو المحافظ الإلكترونية.'
                : 'We accept cash on delivery, credit cards, and e-wallets.';
        }
        
        // Delivery
        if (stripos($message, 'التوصيل') !== false || stripos($message, 'الشحن') !== false || stripos($message, 'delivery') !== false || stripos($message, 'shipping') !== false) {
            return $isArabic
                ? 'نقوم بالتوصيل إلى جميع أنحاء جمهورية مصر العربية خلال 2-5 أيام عمل.'
                : 'We deliver all over Egypt within 2-5 business days.';
        }
        
        // Return policy
        if (stripos($message, 'سياسة الإرجاع') !== false || stripos($message, 'إرجاع') !== false || stripos($message, 'return policy') !== false || stripos($message, 'return') !== false) {
            return $isArabic
                ? 'يمكنك إرجاع الكتب خلال 14 يومًا من الاستلام إذا كانت بحالة جيدة.'
                : 'You can return books within 14 days of receipt if they are in good condition.';
        }
        
        // Support
        if (stripos($message, 'الدعم') !== false || stripos($message, 'مساعدة') !== false || stripos($message, 'support') !== false || stripos($message, 'help') !== false) {
            return $isArabic
                ? 'للدعم أو الاستفسارات، تواصل معنا عبر البريد الإلكتروني أو الهاتف الموجود في صفحة التواصل.'
                : 'For support or inquiries, contact us via email or phone listed on the contact page.';
        }
        
        // Prices
        if (stripos($message, 'الأسعار') !== false || stripos($message, 'السعر') !== false || stripos($message, 'price') !== false || stripos($message, 'prices') !== false) {
            return $isArabic
                ? 'نقدم أفضل الأسعار التنافسية لجميع الكتب في كل المجالات.'
                : 'We offer the best competitive prices for all books in every field.';
        }
        
        // Register / Sign up
        if (stripos($message, 'تسجيل') !== false || stripos($message, 'إنشاء حساب') !== false || stripos($message, 'register') !== false || stripos($message, 'sign up') !== false) {
            return $isArabic
                ? 'يمكنك إنشاء حساب جديد بسهولة من خلال الضغط على زر "تسجيل" في أعلى الصفحة.'
                : 'You can easily create a new account by clicking the "Register" button at the top of the page.';
        }
        
        // Categories
        if (stripos($message, 'الفئات') !== false || stripos($message, 'التصنيفات') !== false || stripos($message, 'categories') !== false || stripos($message, 'category') !== false) {
            return $isArabic
                ? 'نوفر مجموعة واسعة من الفئات: الأدب، الأطفال، التعليم، العلوم، التاريخ، التنمية الذاتية، وغيرها.'
                : 'We offer a wide range of categories: literature, children, education, science, history, self-development, and more.';
        }

        // How to rent a book
        if (preg_match('/(كيف\s*أ?س?ت?[أا]?ج?ر?\s*كتاب\??|است[أا]?جر\s*كتاب\??|استئجار\s*كتاب\??|rent\s*a?\s*book\??)/iu', $message)) {
            return $isArabic
                ? 'للاستئجار: ابحث عن الكتاب المطلوب واضغط على "استئجار"، ثم اتبع التعليمات لإتمام العملية. يمكنك التواصل مع المالك مباشرة من صفحة الكتاب.'
                : 'To rent a book: search for the desired book and click "Rent", then follow the instructions. You can contact the owner directly from the book page.';
        }

        // How to sell a book
        if (preg_match('/(كيف\s*أ?ب?ي?ع?\s*كتاب\??|بيع\s*كتاب\??|sell\s*a?\s*book\??)/iu', $message)) {
            return $isArabic
                ? 'لإضافة كتاب للبيع: اضغط على "إضافة كتاب"، املأ التفاصيل (العنوان، الوصف، السعر، الحالة)، أضف صورًا واضحة، ثم انشر الإعلان. يمكنك تحديد إذا كان للبيع أو التأجير.'
                : 'To sell a book: Click "Add Book", fill in details (title, description, price, condition), add clear photos, then publish. You can choose to sell or rent it.';
        }

        // How to donate a book
        if (preg_match('/(تبرع|كيف\s*أ?ت?ب?رع?\s*ب?كتاب\??|donate\s*a?\s*book\??)/iu', $message)) {
            return $isArabic
                ? 'للتبرع بكتاب: اختر "تبرع" عند إضافة كتاب، وسيتم توجيهه للمدارس أو الجمعيات الخيرية. يمكنك أيضًا التبرع مباشرة عبر قسم التبرعات.'
                : 'To donate a book: Select "Donate" when adding a book, and it will be directed to schools or charities. You can also donate directly via the Donations section.';
        }

        // Book condition
        if (preg_match('/(حالة\s*الكتاب|كيف\s*أعرف\s*حالة\s*الكتاب|book\s*condition)/iu', $message)) {
            return $isArabic
                ? 'يجب على البائع وصف حالة الكتاب بدقة (مثل: جديد، جيد جداً، به بعض العلامات) مع صور واضحة. يمكنك التواصل مع البائع لطلب المزيد من التفاصيل.'
                : 'Sellers must accurately describe the book condition (e.g., new, very good, some markings) with clear photos. You can contact the seller for more details.';
        }

        // Delivery options
        if (preg_match('/(خيارات\s*التوصيل|كيف\s*يتم\s*التوصيل|delivery\s*options)/iu', $message)) {
            return $isArabic
                ? 'خيارات التوصيل:\n1- الاستلام من البائع مباشرة\n2- التوصيل عبر شركات الشحن (تتكلفة إضافية)\n3- توصيل مجاني لبعض العروض'
                : 'Delivery options:\n1- Pickup from seller\n2- Shipping via courier (additional cost)\n3- Free delivery for some offers';
        }

        // Contact seller
        if (preg_match('/(كيف\s*أتواصل\s*مع\s*البائع|contact\s*seller)/iu', $message)) {
            return $isArabic
                ? 'يمكنك مراسلة البائع مباشرة عبر زر "مراسلة البائع" في صفحة الكتاب. سيتم إرسال رسالتك إلى بريده الإلكتروني ورقم الهاتف.'
                : 'You can message the seller directly via the "Contact Seller" button on the book page. Your message will be sent to their email and phone.';
        }

        // Book not received
        if (preg_match('/(لم\s*أستلم\s*الكتاب|book\s*not\s*received)/iu', $message)) {
            return $isArabic
                ? 'إذا لم تستلم الكتاب خلال المدة المتفق عليها:\n1- تواصل مع البائع\n2- إذا لم يتم الحل، رفع شكوى للدعم الفني\n3- سنقوم بالتحقيق وإعاد المبلغ إذا لزم الأمر'
                : 'If you don\'t receive the book by the agreed time:\n1- Contact the seller\n2- If unresolved, file a complaint with support\n3- We will investigate and refund if needed';
        }

        // Wrong book received
        if (preg_match('/(استلمت\s*كتاب\s*خاطئ|wrong\s*book)/iu', $message)) {
            return $isArabic
                ? 'إذا استلمت كتابًا غير المطلوب:\n1- أبلغ الدعم الفني خلال 3 أيام\n2- أرفق صورًا للكتاب المستلم\n3- سنساعدك في استبداله أو استرداد المبلغ'
                : 'If you receive the wrong book:\n1- Report to support within 3 days\n2- Attach photos of the received book\n3- We\'ll help replace it or refund you';
        }

        // How to add to wishlist
        if (preg_match('/(كيف\s*أضيف\s*لقائمة\s*الرغبات|wishlist)/iu', $message)) {
            return $isArabic
                ? 'لإضافة كتاب لقائمة الرغبات:\n1- اذهب لصفحة الكتاب\n2- اضغط على أيقونة القلب\n3- ستتلقى إشعارًا إذا أصبح متاحًا للبيع أو التأجير'
                : 'To add to wishlist:\n1- Go to book page\n2- Click the heart icon\n3- You\'ll be notified if it becomes available for sale/rent';
        }

        // How to review a book
        if (preg_match('/(كيف\s*أقيم\s*كتاب|book\s*review)/iu', $message)) {
            return $isArabic
                ? 'لتقييم كتاب بعد الشراء أو التأجير:\n1- اذهب لصفحة "طلباتي"\n2- اختر الكتاب\n3- اضغط على "إضافة تقييم"\n4- اكتب رأيك وضيف نجمة من 1-5'
                : 'To review a book after purchase/rental:\n1- Go to "My Orders"\n2- Select the book\n3- Click "Add Review"\n4- Write your opinion and rate 1-5 stars';
        }

        // Default fallback
        return $isArabic 
            ? 'عذرًا، لم أفهم سؤالك. يمكنك تجربة الخطوات التالية لأي كتاب:
1- اذهب إلى صفحة الكتاب المطلوب.
2- أضفه إلى السلة (cart).
3- انتقل إلى السلة وأكمل الطلب (order).
يمكنك أيضًا طرح أسئلة مثل:
- كيف أبيع كتاب؟
- كيف أستأجر كتاب؟
- ما هي طرق الدفع؟\n- كيف أتواصل مع البائع؟'
            : 'Sorry, I didn\'t understand your question. Try these steps for any book:
1- Go to the book page you want.
2- Add it to your cart.
3- Go to your cart and place an order.
You can also ask things like:
- How to sell a book?\n- How to rent a book?\n- What payment methods do you accept?\n- How to contact seller?';
    }
}