using System.Data;
using Microsoft.Data.SqlClient;
using TecnoSoftSolutions.Models;

namespace TecnoSoftSolutions.Data;

public sealed class UserRepository(IConfiguration configuration)
{
    private readonly string _connectionString = configuration.GetConnectionString("TecnoSoft")
        ?? throw new InvalidOperationException("Falta ConnectionStrings:TecnoSoft.");

    public async Task<UserRecord?> FindByEmailAsync(string email, CancellationToken cancellationToken)
    {
        await using var connection = new SqlConnection(_connectionString);
        await connection.OpenAsync(cancellationToken);
        await using var command = new SqlCommand(
            "SELECT Id, FullName, Email, PasswordHash, PasswordSalt, PasswordIterations FROM dbo.Users WHERE Email = @Email", connection);
        command.Parameters.Add("@Email", SqlDbType.NVarChar, 320).Value = email.Trim().ToLowerInvariant();
        await using var reader = await command.ExecuteReaderAsync(cancellationToken);
        if (!await reader.ReadAsync(cancellationToken)) return null;

        return new UserRecord(
            reader.GetInt32(0), reader.GetString(1), reader.GetString(2),
            (byte[])reader[3], (byte[])reader[4], reader.GetInt32(5));
    }

    public async Task<bool> CreateAsync(string fullName, string email, byte[] hash, byte[] salt,
        int iterations, CancellationToken cancellationToken)
    {
        await using var connection = new SqlConnection(_connectionString);
        await connection.OpenAsync(cancellationToken);
        await using var command = new SqlCommand("""
            INSERT INTO dbo.Users (FullName, Email, PasswordHash, PasswordSalt, PasswordIterations)
            VALUES (@FullName, @Email, @PasswordHash, @PasswordSalt, @PasswordIterations)
            """, connection);
        command.Parameters.Add("@FullName", SqlDbType.NVarChar, 100).Value = fullName.Trim();
        command.Parameters.Add("@Email", SqlDbType.NVarChar, 320).Value = email.Trim().ToLowerInvariant();
        command.Parameters.Add("@PasswordHash", SqlDbType.VarBinary, 32).Value = hash;
        command.Parameters.Add("@PasswordSalt", SqlDbType.VarBinary, 16).Value = salt;
        command.Parameters.Add("@PasswordIterations", SqlDbType.Int).Value = iterations;
        try
        {
            await command.ExecuteNonQueryAsync(cancellationToken);
            return true;
        }
        catch (SqlException ex) when (ex.Number is 2601 or 2627)
        {
            return false;
        }
    }
}
