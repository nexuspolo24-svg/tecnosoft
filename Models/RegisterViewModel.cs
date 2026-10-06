using System.ComponentModel.DataAnnotations;

namespace TecnoSoftSolutions.Models;

public sealed class RegisterViewModel
{
    [Required(ErrorMessage = "Escribe tu nombre.")]
    [StringLength(100, MinimumLength = 2, ErrorMessage = "El nombre debe tener entre 2 y 100 caracteres.")]
    [Display(Name = "Nombre completo")]
    public string FullName { get; set; } = "";

    [Required(ErrorMessage = "Escribe tu correo.")]
    [EmailAddress(ErrorMessage = "Escribe un correo válido.")]
    [StringLength(320)]
    [Display(Name = "Correo electrónico")]
    public string Email { get; set; } = "";

    [Required(ErrorMessage = "Escribe una contraseña.")]
    [StringLength(128, MinimumLength = 10, ErrorMessage = "Usa al menos 10 caracteres.")]
    [DataType(DataType.Password)]
    [Display(Name = "Contraseña")]
    public string Password { get; set; } = "";

    [Required(ErrorMessage = "Confirma tu contraseña.")]
    [Compare(nameof(Password), ErrorMessage = "Las contraseñas no coinciden.")]
    [DataType(DataType.Password)]
    [Display(Name = "Confirmar contraseña")]
    public string ConfirmPassword { get; set; } = "";
}
