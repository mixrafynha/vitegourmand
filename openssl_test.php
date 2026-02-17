<<<<<<< HEAD
<?php

$k = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);

var_dump($k !== false);

if (!$k) {
    while ($e = openssl_error_string()) {
        echo $e, PHP_EOL;
    }
}
=======
<?php

$k = openssl_pkey_new([
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
]);

var_dump($k !== false);

if (!$k) {
    while ($e = openssl_error_string()) {
        echo $e, PHP_EOL;
    }
}
>>>>>>> 5cd11777e1a252cec458138ad3d8d45ce116223f
