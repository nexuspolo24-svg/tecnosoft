<?php
declare(strict_types=1);
if(!defined('TECNOSOFT_INTERNAL')){http_response_code(404);exit;}

function tsEmailError(string $value): string {
    if (strlen($value) > 190 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        return 'Escribe un correo completo, por ejemplo nombre@gmail.com.';
    }
    [$local, $domain] = explode('@', $value, 2);
    if (strlen($local) > 64 || !preg_match("/^[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+)*$/D", $local)
        || !preg_match('/^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,63}$/D', $domain)) {
        return 'Escribe un correo completo, por ejemplo nombre@gmail.com.';
    }
    return '';
}

function tsNameError(string $value): string {
    if (mb_strlen($value) > 100 || preg_match_all('/\p{L}/u', $value) < 2
        || !preg_match("/^\p{L}[\p{L}\p{M}]*(?:[ '’-]\p{L}[\p{L}\p{M}]*)*$/uD", $value)) {
        return 'El nombre debe tener al menos dos letras. Usa solo letras, tildes, espacios, apóstrofos o guiones.';
    }
    return '';
}

function tsPhoneError(string $value): string {
    return preg_match('/^(?:505)?[0-9]{8}$/D', $value)
        ? '' : 'Escribe 8 dígitos o 505 seguido de 8 dígitos. No uses letras, espacios ni símbolos.';
}

function tsTextError(string $value): string {
    return !mb_check_encoding($value, 'UTF-8') || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value)
        ? 'El campo contiene caracteres de control no permitidos.' : '';
}

function tsPasswordError(string $value, bool $register): string {
    if (!mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
        return 'La contraseña contiene caracteres no permitidos.';
    }
    if (strlen($value) > 72) return 'La contraseña admite como máximo 72 bytes; las tildes y algunos símbolos ocupan más de un byte.';
    if ($register && mb_strlen($value) < 10) return 'Usa una contraseña de al menos 10 caracteres.';
    return $value === '' ? 'Escribe tu contraseña.' : '';
}
