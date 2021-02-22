<?php

return [

    'debug' => [
        'trace_as_string' => env('HADES_DEBUG_TRACE_AS_STRING', false),
        'trace_args' => env('HADES_DEBUG_TRACE_ARGS', false),
        'trace_size_limit' => env('HADES_DEBUG_TRACE_SIZE_LIMIT', 20),
    ],

];
