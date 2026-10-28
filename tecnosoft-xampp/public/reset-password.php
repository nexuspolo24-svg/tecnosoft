<?php
header('Cache-Control: no-store');
header('Referrer-Policy: no-referrer');
header('X-Content-Type-Options: nosniff');
?>
<!doctype html>
<html lang="es-NI">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Restablecer contraseña · TecnoSoft</title><link rel="icon" href="assets/isotipo.svg" type="image/svg+xml"><link rel="stylesheet" href="assets/style.css"><script src="assets/validation.js" defer></script><script src="assets/reset-password.js" defer></script></head>
<body><main class="section verify-card">
<a href="./" aria-label="TecnoSoft Solutions, inicio"><img src="assets/logo.svg" width="220" alt="TecnoSoft Solutions"></a>
<p class="eyebrow">RECUPERACIÓN DE CUENTA</p><h1>Elige una nueva contraseña.</h1>
<p>Usa al menos 10 caracteres, sin espacios y hasta 72 bytes. Confirma la misma contraseña en ambos campos.</p>
<form id="reset-password-form" hidden>
<label>NUEVA CONTRASEÑA<input id="reset-password" name="password" type="password" autocomplete="new-password" minlength="10" maxlength="72" required aria-describedby="reset-password-error"></label><small id="reset-password-error" class="field-validation" hidden></small>
<label>CONFIRMAR CONTRASEÑA<input id="reset-confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="10" maxlength="72" required aria-describedby="reset-confirm-error"></label><small id="reset-confirm-error" class="field-validation" hidden></small>
<button type="submit" class="button">GUARDAR NUEVA CONTRASEÑA ↗</button>
</form>
<p id="reset-feedback" role="status" aria-live="polite"></p>
<a class="text-link" href="./?login=1">VOLVER A INICIAR SESIÓN ↗</a>
</main></body></html>
