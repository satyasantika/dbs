<?php

namespace App\Filament\Mahasiswa\Pages;

use Filament\Forms\Components\Component;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile;
use Illuminate\Contracts\Support\Htmlable;

class MahasiswaEditProfile extends EditProfile
{
    public static function getLabel(): string
    {
        return 'Ganti Password';
    }

    public function getTitle(): string | Htmlable
    {
        return static::getLabel();
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()->required();
    }

    /**
     * @return array<int|string, Form>
     */
    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }
}
