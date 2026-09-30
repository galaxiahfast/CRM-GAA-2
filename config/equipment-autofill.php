<?php

return [
    /*
    | El modo local mantiene las fotografías dentro del servidor. OCR.space
    | sólo se usa cuando se selecciona explícitamente y existe una API key.
    */
    'ocr' => [
        'driver' => env('EQUIPMENT_OCR_DRIVER', 'tesseract'),
        'tesseract' => [
            'executable' => env('TESSERACT_BINARY', 'tesseract'),
            'languages' => env('TESSERACT_LANGUAGES', 'eng+spa'),
            'timeout' => (int) env('TESSERACT_TIMEOUT', 25),
        ],
        'ocr_space' => [
            'api_key' => env('OCR_SPACE_API_KEY'),
            'endpoint' => env('OCR_SPACE_ENDPOINT', 'https://api.ocr.space/parse/image'),
            'timeout' => (int) env('OCR_SPACE_TIMEOUT', 25),
        ],
    ],

    /*
    | UPCitemdb es opt-in para no consumir su cuota gratuita ni enviar datos
    | fuera del servidor sin una decisión consciente del administrador.
    */
    'catalog' => [
        'enabled' => (bool) env('EQUIPMENT_CATALOG_ENABLED', false),
        'text_search' => (bool) env('EQUIPMENT_CATALOG_TEXT_SEARCH', false),
        'lookup_endpoint' => env('UPCITEMDB_LOOKUP_ENDPOINT', 'https://api.upcitemdb.com/prod/trial/lookup'),
        'search_endpoint' => env('UPCITEMDB_SEARCH_ENDPOINT', 'https://api.upcitemdb.com/prod/trial/search'),
        'timeout' => (int) env('UPCITEMDB_TIMEOUT', 8),
        'cache_hours' => (int) env('UPCITEMDB_CACHE_HOURS', 168),
    ],
];
