<?php

declare(strict_types=1);

namespace App\Commands;

use App\Exceptions\ValidationException;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Exceptions\DatabaseException;
use RuntimeException;

/**
 * `spark user:create` — the only supported way to provision staff.
 *
 * Never echoes or logs the plaintext password or the stored hash.
 */
class UserCreate extends BaseCommand
{
    protected $group       = 'auth';
    protected $name        = 'user:create';
    protected $description = 'Create a staff user (cashier or admin).';
    protected $usage       = 'user:create';
    protected $arguments   = [];
    protected $options     = [];

    public function run(array $params)
    {
        try {
            $username = $this->askUsername();
            $password = $this->askPassword();
            $role     = $this->askRole();

            $id = service('auth')->createUser($username, $password, $role);
        } catch (ValidationException $exception) {
            foreach ($exception->getErrors() as $message) {
                CLI::error($message);
            }

            return EXIT_ERROR;
        } catch (DatabaseException $exception) {
            CLI::error('Could not create the user: the database rejected the record.');

            return EXIT_ERROR;
        } catch (RuntimeException $exception) {
            CLI::error($exception->getMessage());

            return EXIT_ERROR;
        }

        CLI::write("User #{$id} created with role {$role}.");

        return EXIT_SUCCESS;
    }

    protected function askUsername(): string
    {
        return trim(CLI::prompt('Username'));
    }

    protected function askPassword(): string
    {
        do {
            $password = $this->readHidden('Password');
            $confirmation = $this->readHidden('Confirm password');

            if ($password !== $confirmation) {
                CLI::error(lang('Auth.passwordMismatch'));
            }
        } while ($password !== $confirmation);

        return $password;
    }

    protected function askRole(): string
    {
        return CLI::prompt('Role', ['cashier', 'admin']);
    }

    /**
     * Reads without echoing and never passes the value as an argument,
     * so it cannot land in shell history or process listings.
     */
    private function readHidden(string $label): string
    {
        if (! function_exists('stream_isatty') || ! stream_isatty(STDIN)) {
            throw new RuntimeException('A TTY is required so the password is not echoed.');
        }

        $stty = shell_exec('stty -g');

        if (! is_string($stty) || $stty === '') {
            throw new RuntimeException('Could not disable terminal echo.');
        }

        fwrite(STDOUT, $label . ': ');
        shell_exec('stty -echo');

        try {
            $value = fgets(STDIN);
        } finally {
            shell_exec('stty ' . escapeshellarg(trim($stty)));
            fwrite(STDOUT, PHP_EOL);
        }

        return trim((string) $value);
    }
}
