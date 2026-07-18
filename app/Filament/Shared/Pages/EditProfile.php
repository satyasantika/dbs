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
use Illuminate\Validation\Rule;

class EditProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.shared.pages.edit-profile';

    protected static ?string $slug = 'edit-profil';

    // Panel-wide default is Full (untuk halaman tabel/resource) — form
    // singkat seperti ini lebih enak dibaca dibatasi lebar, bukan
    // melebar selayar penuh di desktop.
    protected ?string $maxContentWidth = 'xl';

    /**
     * @var array<string, mixed>
     */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->authUser()->only(['name', 'email']));
    }

    public function form(Form $form): Form
    {
        $user = $this->authUser();

        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Lengkap')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->rule(fn () => Rule::unique('users', 'email')->ignore($user->id)),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $this->authUser()->update($this->form->getState());

        Notification::make()
            ->success()
            ->title('Profil berhasil diperbarui.')
            ->send();
    }

    protected function authUser(): User
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        return $user;
    }
}
