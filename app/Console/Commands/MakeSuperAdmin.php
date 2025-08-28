<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class MakeSuperAdmin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:super-admin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Super Admin user interactively';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Create Super Admin User');

        // Get user input interactively
        $name = (string) $this->ask('Enter name for the super admin');

        $email = null;
        while (! $email) {
            $email = (string) $this->ask('Enter email address');

            // Validate email format
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->error('Please enter a valid email address');

                continue;
            }

            // Check if user with this email already exists
            if (User::where('email', $email)->exists()) {
                $this->error("A user with email {$email} already exists!");
                if (! $this->confirm('Do you want to try a different email?', true)) {
                    return 1;
                }
                $email = null;
            }
        }

        $password = null;
        while (! $password) {
            $password = $this->secret('Enter password (input will be hidden)');

            // Strong password validation
            $strongValidator = Validator::make(['password' => $password], [
                'password' => [
                    Password::min(8)
                        ->letters()          // At least one letter
                        ->mixedCase()        // At least one uppercase and one lowercase letter
                        ->numbers()          // At least one number
                        ->symbols()          // At least one symbol
                        ->uncompromised(),   // Check if it hasn't been compromised in data leaks
                ],
            ]);

            // If password doesn't meet strong requirements, warn the user but allow them to continue
            if ($strongValidator->fails()) {
                $this->warn("\nWARNING: Your password doesn't meet recommended security standards:");
                foreach ($strongValidator->errors()->all() as $error) {
                    $this->warn(' - '.$error);
                }
                $this->warn('A strong password should contain uppercase and lowercase letters, numbers, and symbols.');

                if (! $this->confirm('Do you want to continue with this potentially insecure password?', false)) {
                    $password = null;

                    continue;
                }
            }

            $confirmPassword = $this->secret('Confirm password');
            if ($password !== $confirmPassword) {
                $this->error('Passwords do not match');
                $password = null;
            }
        }

        // Confirm creation
        $this->info("\nAbout to create super admin with the following details:");
        $this->info("Name: $name");
        $this->info("Email: $email");

        if (! $this->confirm('Do you wish to continue?', true)) {
            $this->info('Operation cancelled');

            return 0;
        }

        // Create the user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password, // Model will hash this automatically
            'is_super_admin' => true,
        ]);

        $this->info("\n✓ Super Admin user created successfully!");
        $this->table(
            ['Name', 'Email', 'Super Admin'],
            [[$user->name, $user->email, 'Yes']]
        );

        $this->info('You can now log in with these credentials at the admin panel.');

        return 0;
    }
}
