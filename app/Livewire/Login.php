<?php

namespace App\Livewire;

use App\Http\Middleware\EnsureDashboardAuth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Inloggen')]
class Login extends Component
{
    public string $password = '';

    public function authenticate(): void
    {
        $this->validate(
            ['password' => 'required'],
            ['password.required' => 'Vul je wachtwoord in.'],
        );

        $expected = (string) config('dashboard.password');

        if ($expected === '' || ! hash_equals($expected, $this->password)) {
            $this->addError('password', 'Onjuist wachtwoord.');

            return;
        }

        session()->regenerate();
        session()->put(EnsureDashboardAuth::SESSION_KEY, true);

        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.login');
    }
}
