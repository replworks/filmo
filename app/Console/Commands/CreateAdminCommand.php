<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAdminCommand extends Command
{
    protected $signature = 'filmo:create-admin
        {email : The administrator email address}
        {--name= : The administrator display name}
        {--password= : The administrator password; omit to prompt}';

    protected $description = 'Create a new administrator or grant administrator access to an existing user';

    public function handle(): int
    {
        $email = strtolower(trim((string) $this->argument('email')));

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email']],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = User::query()->where('email', $email)->first();
        $isNew = $user === null;

        $name = $this->stringOption('name');
        $password = $this->stringOption('password');

        if ($isNew) {
            if ($name === null && $this->input->isInteractive()) {
                $name = $this->ask('Name');
                $name = is_string($name) ? trim($name) : null;
            }

            if ($password === null && $this->input->isInteractive()) {
                $password = $this->secret('Password');
            }

            if ($name === null || $password === null) {
                $this->error('Name and password are required for a new administrator. Use --name and --password, or run the command interactively.');

                return self::FAILURE;
            }

            $user = new User(['email' => $email]);
        }

        $values = ['name' => $name, 'password' => $password];
        $rules = [
            'name' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8'],
        ];

        if ($isNew) {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['password'] = ['required', 'string', 'min:8'];
        }

        $validator = Validator::make($values, $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if ($name !== null) {
            $user->name = $name;
        }

        if ($password !== null) {
            $user->password = $password;
        }

        $user->email = $email;
        $user->is_admin = true;
        $user->save();

        $this->info(($isNew ? 'Administrator created' : 'Administrator access granted').': '.$email);

        return self::SUCCESS;
    }

    private function stringOption(string $name): ?string
    {
        $value = $this->option($name);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
