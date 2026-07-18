<?php

namespace App\Filament\Shared\Pages;

use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;

class ChangePassword extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.shared.pages.change-password';

    protected static ?string $slug = 'ubah-password';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('password')
                    ->label('Password Baru')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->confirmed(),
                TextInput::make('password_confirmation')
                    ->label('Konfirmasi Password Baru')
                    ->password()
                    ->revealable()
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        $this->authUser()->update([
            'password' => Hash::make($state['password']),
        ]);

        $this->form->fill();

        Notification::make()
            ->success()
            ->title('Password berhasil diubah.')
            ->send();
    }

    protected function authUser(): User
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        return $user;
    }
}
