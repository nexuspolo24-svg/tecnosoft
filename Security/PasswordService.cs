using System.Security.Cryptography;

namespace TecnoSoftSolutions.Security;

public static class PasswordService
{
    public const int Iterations = 310_000;

    public static (byte[] Hash, byte[] Salt) Hash(string password)
    {
        var salt = RandomNumberGenerator.GetBytes(16);
        var hash = Rfc2898DeriveBytes.Pbkdf2(password, salt, Iterations, HashAlgorithmName.SHA256, 32);
        return (hash, salt);
    }

    public static bool Verify(string password, byte[] expectedHash, byte[] salt, int iterations)
    {
        var actualHash = Rfc2898DeriveBytes.Pbkdf2(password, salt, iterations, HashAlgorithmName.SHA256, 32);
        return CryptographicOperations.FixedTimeEquals(actualHash, expectedHash);
    }
}
