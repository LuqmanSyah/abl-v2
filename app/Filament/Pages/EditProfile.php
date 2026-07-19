<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EditProfile extends BaseEditProfile
{
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
                    ->description('Data diri dan informasi akun Anda.')
                    ->columns(2)
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        TextInput::make('phone')
                            ->label('Telepon')
                            ->tel()
                            ->columnSpanFull()
                            ->helperText('Digunakan untuk notifikasi WhatsApp urgensi (trip baru, absen hari ini).'),
                    ]),
                Section::make('Informasi Kepegawaian')
                    ->description('Data kepegawaian — tidak dapat diubah dari halaman ini.')
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
