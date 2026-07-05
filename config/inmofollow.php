<?php

return [
    'default_company_id' => 1,

    'default_company_name' => 'Artigue Negocios Inmobiliarios',

    'allow_company_management' => true,

    'whatsapp_max_sends_per_run' => env('WHATSAPP_MAX_SENDS_PER_RUN', 20),

    'whatsapp_send_delay_ms' => env('WHATSAPP_SEND_DELAY_MS', 500),
];
