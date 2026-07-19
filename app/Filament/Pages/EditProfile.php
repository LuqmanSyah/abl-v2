<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Html;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;

class EditProfile extends BaseEditProfile
{
    protected Width | string | null $maxContentWidth = Width::Full;

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
        $data['notification_preferences'] ??= [
            'inapp' => true,
            'webpush' => true,
            'email' => true,
            'wa' => false,
        ];

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
                        Html::make(<<<HTML
                            <div class="flex justify-center w-full py-3">
                                <div class="w-20 h-20 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                                    <svg class="w-10 h-10 text-white" aria-hidden="true" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 4a4 4 0 1 0 0 8 4 4 0 0 0 0-8Zm-7 14a7 7 0 0 1 14 0H5Z"/>
                                    </svg>
                                </div>
                            </div>
                        HTML)->columnSpan(2),
                        $this->getNameFormComponent()
                            ->columnSpan(1),
                        $this->getEmailFormComponent()
                            ->columnSpan(1),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->columnSpan(1)
                            ->helperText('Nomor untuk notifikasi WhatsApp urgensi.'),
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
                Section::make('Preferensi Notifikasi')
                    ->description('WA hanya untuk notifikasi urgensi: Trip Baru, Absen Hari Ini, Absensi Perlu Pemeriksaan.')
                    ->columns(2)
                    ->schema([
                        Toggle::make('notification_preferences.inapp')
                            ->label('In-app (database)')
                            ->default(true),
                        Toggle::make('notification_preferences.webpush')
                            ->label('Web Push')
                            ->default(true),
                        Toggle::make('notification_preferences.email')
                            ->label('Email')
                            ->default(true),
                        Toggle::make('notification_preferences.wa')
                            ->label('WhatsApp')
                            ->default(false),
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
