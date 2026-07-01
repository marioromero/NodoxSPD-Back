<?php

/*
 * Límites de tasa configurables para el Trust Widget.
 * Defensa multicapa: visitante, empresa e IP se evalúan simultáneamente.
 * Valores overrideable desde .env (RATE_LIMIT_WIDGET_*).
 */

return [

    'widget' => [
        'visitor' => env('RATE_LIMIT_WIDGET_VISITOR', 10),
        'company' => env('RATE_LIMIT_WIDGET_COMPANY', 60),
        'ip' => env('RATE_LIMIT_WIDGET_IP', 5),
    ],

];
