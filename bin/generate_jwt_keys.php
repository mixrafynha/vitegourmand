<?php
$passphrase = getenv('JWT_PASSPHRASE') ?: '';

$dir = __DIR__ . '/../config/jwt';
@mkdir($dir, 0777, true);

$config = [
    'private_key_bits' => 4096,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
];

$key = openssl_pkey_new($config);
if ($key === false) {
    throw new RuntimeException('openssl_pkey_new falhou: ' . openssl_error_string());
}

$privatePem = '';
if (!openssl_pkey_export($key, $privatePem, $passphrase)) {
    throw new RuntimeException('openssl_pkey_export falhou: ' . openssl_error_string());
}

$details = openssl_pkey_get_details($key);
if ($details === false || empty($details['key'])) {
    throw new RuntimeException('openssl_pkey_get_details falhou: ' . openssl_error_string());
}

file_put_contents($dir . '/private.pem', $privatePem);
file_put_contents($dir . '/public.pem', $details['key']);

echo "OK: gerou private.pem e public.pem em $dir\n";
