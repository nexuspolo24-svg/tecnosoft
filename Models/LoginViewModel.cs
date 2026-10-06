using System.ComponentModel.DataAnnotations;

namespace TecnoSoftSolutions.Models;

public sealed class LoginViewModel
{
    [Required(ErrorMessage = "Escribe tu correo.")]
    [EmailAddress(ErrorMessage = "Escribe un correo válido.")]
    [Display(Name = "Correo electrónico")]
    public string Email { get; set; } = "";

    [Required(ErrorMessage = "Escribe tu contraseña.")]
    [DataType(DataType.Password)]
    [Display(Name = "Contraseña")]
    public string Password { get; set; } = "";

    public string? ReturnUrl { get; set; }
}
