<?php

return [
    'embedding_model' => env('BOOKAI_EMBEDDING_MODEL', 'openai'),
    'faiss_index_path' => storage_path('app/bookai_index.faiss'),
    'metadata_path' => storage_path('app/bookai_metadata.json'),
];
