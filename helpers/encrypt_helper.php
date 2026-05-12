<?php

define('APP_KEY', 'P1QBd3;dn@£YYs526CZC9./&PuMg0b#e.nWb"l');

/**
 * Encrypt ID
 */
function encryptId($id)
{
    $key = hash('sha256', APP_KEY);

    $iv = random_bytes(16);

    $encrypted = openssl_encrypt(
        $id,
        "AES-256-CBC",
        $key,
        0,
        $iv
    );

    return base64_encode($iv . $encrypted);
}

/**
 * Decrypt ID
 */
function decryptId($data)
{
    $key = hash('sha256', APP_KEY);

    $data = base64_decode($data);

    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);

    return openssl_decrypt(
        $encrypted,
        "AES-256-CBC",
        $key,
        0,
        $iv
    );
}