<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditProfile extends BaseEditProfile
{
    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getLabel(): string
    {
        return 'Profil Saya';
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->getUser();
        $data['unit'] ??= [];
        $data['position'] ??= [];
        $data['unit']['name'] ??= $user->unit?->name;
        $data['position']['name'] ??= $user->position?->name;
        $data['role'] ??= $user->role;

        return $data;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Akun')
                    ->description('Data diri dan informasi akun.')
                    ->columns(2)
                    ->schema([
                        $this->getNameFormComponent()
                            ->columnSpan(1),
                        $this->getEmailFormComponent()
                            ->columnSpan(1),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->columnSpan(1),
                    ]),
                Section::make('Informasi Kepegawaian')
                    ->description('Data kepegawaian — tidak dapat diubah dari sini.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('employee_number')
                            ->label('NIP/Nomor pegawai')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('role')
                            ->label('Peran')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => UserRole::tryFrom($state)?->label() ?? '-'),
                        TextInput::make('unit.name')
                            ->label('Unit kerja')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('position.name')
                            ->label('Jabatan')
                            ->disabled()
                            ->dehydrated(false),
                    ]),
                $this->getPasswordSectionComponent(),
            ]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->helperText('Kata sandi minimal 8 karakter, mengandung huruf besar, huruf kecil, dan angka.');
    }

    protected function getPasswordSectionComponent(): Component
    {
        $components = [
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            $this->getCurrentPasswordFormComponent(),
        ];

        return Section::make('Ganti Kata Sandi')
            ->description('Kosongkan jika tidak ingin mengubah kata sandi.')
            ->columns(2)
            ->schema($components);
    }
}
