<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TokenCreateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'token:create
                            {email? : Email of existing user to create token for}
                            {--name= : Name for the token (default: "API Token")}
                            {--new : Create a new API user instead of using existing}
                            {--user-name= : Name for the new API user (required with --new)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new API token for a user';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $createNew = $this->option('new');
        $email = $this->argument('email');
        $tokenName = $this->option('name') ?? 'API Token';

        if ($createNew) {
            $user = $this->createApiUser();
            if (! $user) {
                return Command::FAILURE;
            }
        } else {
            if (! $email) {
                $email = $this->ask('Enter the email of the user to create a token for');
            }

            $user = User::where('email', $email)->first();

            if (! $user) {
                $this->error("User not found: {$email}");
                $this->newLine();

                if ($this->confirm('Would you like to create a new API user with this email?')) {
                    $user = $this->createApiUser($email);
                    if (! $user) {
                        return Command::FAILURE;
                    }
                } else {
                    return Command::FAILURE;
                }
            }
        }

        // Ask for token name if not provided
        if (! $this->option('name')) {
            $tokenName = $this->ask('Enter a name for this token (e.g., "TinyMCE Integration")', 'API Token');
        }

        // Create the token
        $token = $user->createToken($tokenName);

        $this->newLine();
        $this->info('Token created successfully!');
        $this->newLine();

        $this->table(
            ['Field', 'Value'],
            [
                ['User', $user->name],
                ['Email', $user->email],
                ['Role', $user->role],
                ['Token Name', $tokenName],
                ['Token ID', $token->accessToken->id],
            ]
        );

        $this->newLine();
        $this->warn('IMPORTANT: Copy this token now. It will NOT be shown again!');
        $this->newLine();
        $this->line('<fg=green;options=bold>Token: '.$token->plainTextToken.'</>');
        $this->newLine();

        $this->info('Usage example:');
        $this->line('  curl -H "Authorization: Bearer '.$token->plainTextToken.'" \\');
        $this->line('       -H "Accept: application/json" \\');
        $this->line('       '.config('app.url').'/api/assets');
        $this->newLine();

        return Command::SUCCESS;
    }

    /**
     * Create a new API user.
     */
    protected function createApiUser(?string $email = null): ?User
    {
        $this->info('Creating a new API user...');
        $this->newLine();

        // Get user details
        $name = $this->option('user-name');
        if (! $name) {
            $name = $this->ask('Enter name for the API user (e.g., "TinyMCE Integration")');
        }

        if (! $email) {
            $email = $this->ask('Enter email for the API user');
        }

        // Validate email doesn't exist
        if (User::where('email', $email)->exists()) {
            $this->error("A user with email {$email} already exists.");

            return null;
        }

        // Create user with random password (API users won't log in via web)
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make(Str::random(32)),
            'role' => 'api',
        ]);

        $this->info("Created API user: {$user->name} ({$user->email})");

        return $user;
    }
}
