<?php

return [
    'app_name' => getenv('APP_NAME') ?: 'Php Investment Platform',
    'app_env' => getenv('APP_ENV') ?: 'local',
    'app_debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),
    'app_url' => getenv('APP_URL') ?: 'http://localhost:8000',
    'timezone' => 'Asia/Jakarta',
    'default_currency' => 'IDR',
    'recaptcha_site_key' => getenv('RECAPTCHA_SITE_KEY') ?: '',
    'recaptcha_secret_key' => getenv('RECAPTCHA_SECRET_KEY') ?: '',
    'tawk_id' => getenv('TAWK_ID') ?: '',
    'jwt_secret' => getenv('JWT_SECRET') ?: 'change-this-secret-key',
];
