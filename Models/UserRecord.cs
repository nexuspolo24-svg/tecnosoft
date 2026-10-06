namespace TecnoSoftSolutions.Models;

public sealed record UserRecord(int Id, string FullName, string Email,
    byte[] PasswordHash, byte[] PasswordSalt, int PasswordIterations);
