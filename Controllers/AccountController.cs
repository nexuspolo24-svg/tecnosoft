using System.Security.Claims;
using Microsoft.AspNetCore.Authentication;
using Microsoft.AspNetCore.Authentication.Cookies;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Data.SqlClient;
using TecnoSoftSolutions.Data;
using TecnoSoftSolutions.Models;
using TecnoSoftSolutions.Security;

namespace TecnoSoftSolutions.Controllers;

public sealed class AccountController(UserRepository users, ILogger<AccountController> logger) : Controller
{
    [HttpGet]
    public IActionResult Login(string? returnUrl = null)
    {
        if (User.Identity?.IsAuthenticated == true) return LocalRedirect("/");
        return View(new LoginViewModel { ReturnUrl = returnUrl });
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> Login(LoginViewModel model, CancellationToken cancellationToken)
    {
        if (!ModelState.IsValid) return View(model);

        try
        {
            var user = await users.FindByEmailAsync(model.Email, cancellationToken);
            if (user is null || !PasswordService.Verify(model.Password, user.PasswordHash,
                    user.PasswordSalt, user.PasswordIterations))
            {
                ModelState.AddModelError("", "Correo o contraseña incorrectos.");
                return View(model);
            }

            await SignInAsync(user.Id, user.FullName, user.Email);
            return LocalRedirect(Url.IsLocalUrl(model.ReturnUrl) ? model.ReturnUrl! : "/");
        }
        catch (SqlException ex)
        {
            logger.LogError(ex, "Error de SQL Server durante el inicio de sesión");
            ModelState.AddModelError("", "No se pudo conectar con la base de datos. Intenta de nuevo más tarde.");
            return View(model);
        }
    }

    [HttpGet]
    public IActionResult Register()
    {
        if (User.Identity?.IsAuthenticated == true) return LocalRedirect("/");
        return View(new RegisterViewModel());
    }

    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> Register(RegisterViewModel model, CancellationToken cancellationToken)
    {
        if (!ModelState.IsValid) return View(model);

        try
        {
            var (hash, salt) = PasswordService.Hash(model.Password);
            var created = await users.CreateAsync(model.FullName, model.Email, hash, salt,
                PasswordService.Iterations, cancellationToken);
            if (!created)
            {
                ModelState.AddModelError(nameof(model.Email), "Ya existe una cuenta con este correo.");
                return View(model);
            }

            var user = await users.FindByEmailAsync(model.Email, cancellationToken);
            if (user is null) throw new InvalidOperationException("La cuenta recién creada no está disponible.");
            await SignInAsync(user.Id, user.FullName, user.Email);
            return LocalRedirect("/");
        }
        catch (SqlException ex)
        {
            logger.LogError(ex, "Error de SQL Server durante el registro");
            ModelState.AddModelError("", "No se pudo conectar con la base de datos. Intenta de nuevo más tarde.");
            return View(model);
        }
    }

    [Authorize]
    [HttpPost]
    [ValidateAntiForgeryToken]
    public async Task<IActionResult> Logout()
    {
        await HttpContext.SignOutAsync(CookieAuthenticationDefaults.AuthenticationScheme);
        return LocalRedirect("/");
    }

    private async Task SignInAsync(int id, string fullName, string email)
    {
        var claims = new[]
        {
            new Claim(ClaimTypes.NameIdentifier, id.ToString()),
            new Claim(ClaimTypes.Name, fullName),
            new Claim(ClaimTypes.Email, email)
        };
        var identity = new ClaimsIdentity(claims, CookieAuthenticationDefaults.AuthenticationScheme);
        await HttpContext.SignInAsync(CookieAuthenticationDefaults.AuthenticationScheme,
            new ClaimsPrincipal(identity));
    }
}
