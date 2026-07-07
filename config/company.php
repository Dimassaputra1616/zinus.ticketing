<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Company Email Policy
    |--------------------------------------------------------------------------
    |
    | Normal users should register with a company email domain. Personal
    | emails can be allowed explicitly for owner/admin exceptions.
    |
    */

    'email_domains' => array_values(array_filter(array_map(
        fn (string $domain) => strtolower(trim($domain)),
        explode(',', env('COMPANY_EMAIL_DOMAINS', 'zinus.com,zinus.co.id'))
    ))),

    'external_email_allowlist' => array_values(array_filter(array_map(
        fn (string $email) => strtolower(trim($email)),
        explode(',', env('COMPANY_EXTERNAL_EMAIL_ALLOWLIST', 'dimassputra1616@gmail.com'))
    ))),
];
