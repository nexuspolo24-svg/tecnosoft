'use strict';
(function(root) {
  const emailMessage = 'Escribe un correo completo, por ejemplo nombre@gmail.com.';
  function email(value) {
    if (value.length > 190 || /\s/.test(value) || value.split('@').length !== 2) return emailMessage;
    const [local, domain] = value.split('@');
    const localPattern = /^[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+)*$/;
    const domainPattern = /^(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,63}$/;
    return local.length > 64 || !localPattern.test(local) || !domainPattern.test(domain) ? emailMessage : '';
  }
  function name(value) {
    const letters = value.match(/\p{L}/gu) || [];
    return [...value].length > 100 || letters.length < 2 || /[\r\n]/.test(value) || !/^\p{L}[\p{L}\p{M}]*(?:[ '’-]\p{L}[\p{L}\p{M}]*)*$/u.test(value)
      ? 'El nombre debe tener al menos dos letras. Usa solo letras, tildes, espacios, apóstrofos o guiones.' : '';
  }
  function phone(value) {
    return !/\s/.test(value) && /^(?:505)?[0-9]{8}$/.test(value)
      ? '' : 'Escribe 8 dígitos o 505 seguido de 8 dígitos. No uses letras, espacios ni símbolos.';
  }
  function text(value) {
    return /[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/.test(value) ? 'El campo contiene caracteres de control no permitidos.' : '';
  }
  function password(value, register) {
    if (value.includes('\0')) return 'La contraseña contiene caracteres no permitidos.';
    if (new TextEncoder().encode(value).length > 72) return 'La contraseña admite como máximo 72 bytes; las tildes y algunos símbolos ocupan más de un byte.';
    if (register && [...value].length < 10) return 'Usa una contraseña de al menos 10 caracteres.';
    return value === '' ? 'Escribe tu contraseña.' : '';
  }
  const validators = {email, name, phone, text, password};
  if (typeof module !== 'undefined' && module.exports) module.exports = validators;
  else root.TecnoSoftValidation = validators;
})(globalThis);
