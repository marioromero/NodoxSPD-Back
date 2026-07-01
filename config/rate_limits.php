<?php

return [

    'widget' => [
        'visitor' => env('RATE_LIMIT_WIDGET_VISITOR', 10),
        'company' => env('RATE_LIMIT_WIDGET_COMPANY', 60),
        'ip' => env('RATE_LIMIT_WIDGET_IP', 5),
    ],

];
