# AI Migration to Laravel

تم تحويل جميع وظائف الذكاء الاصطناعي من بايثون إلى Laravel. تم إنشاء الخدمات التالية:

-   `BookAiSearchService`: خدمة البحث الذكي عن الكتب.
-   `BookAiEmbeddingService`: خدمة تحويل النص إلى embedding.
-   `FaissIndexService`: خدمة إدارة الفهرس الذكي.
-   `BookAiSearchController`: كنترولر لواجهة البحث الذكي.
-   أمر بناء الفهرس: `php artisan bookai:build-index`

> يجب تثبيت مكتبات PHP المناسبة للذكاء الاصطناعي (مثل openai-php/embeddings أو rubix/ml أو حلول أخرى حسب الحاجة).
