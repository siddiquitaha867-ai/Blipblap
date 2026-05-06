<?php

return [
    'base_url' => env('ESIM_GO_BASE_URL', 'https://api.esim-go.com/v2.5'),
    'api_key' => env('ESIM_GO_API_KEY'),
    'timeout' => (int) env('ESIM_GO_TIMEOUT', 25),
    'retry_times' => (int) env('ESIM_GO_RETRY_TIMES', 2),
    'retry_sleep' => (int) env('ESIM_GO_RETRY_SLEEP', 300),
];
