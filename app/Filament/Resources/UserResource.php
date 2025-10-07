<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Manajemen User';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),

                Forms\Components\TextInput::make('nim_nidn')
                    ->label('NIM / NIDN')
                    ->required()
                    ->maxLength(50),

                Forms\Components\TextInput::make('jurusan')
                    ->required()
                    ->maxLength(100),

                Forms\Components\TextInput::make('prodi')
                    ->required()
                    ->maxLength(100),

                Forms\Components\Select::make('role')
                    ->required()
                    ->reactive()
                    ->options([
                        'admin' => 'Admin',
                        'dosen' => 'Dosen',
                        'mahasiswa' => 'Mahasiswa',
                    ]),

                Forms\Components\Select::make('dosen_id')
                    ->label('Pembimbing')
                    ->options(fn() => \App\Models\User::where('role', 'dosen')->pluck('name', 'id'))
                    ->searchable()
                    ->nullable()
                    ->visible(fn(Forms\Get $get) => $get('role') === 'mahasiswa'),

                Forms\Components\FileUpload::make('photo')
                    ->label('Foto Profil')
                    ->image()
                    ->directory('profile-photos')
                    ->imageEditor()
                    ->maxSize(2048)
                    ->nullable(),
                // ...
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->label('Password')
                    ->dehydrateStateUsing(fn($state) => filled($state) ? \Illuminate\Support\Facades\Hash::make($state) : null)
                    // Gunakan Hash::make() alih-alih bcrypt() (lebih modern di Laravel)

                    // JANGAN SIMPAN KETIKA KOSONG SAAT EDIT
                    ->dehydrated(fn($state) => filled($state))
                    // ^ Hanya masukkan kolom 'password' ke dalam query UPDATE jika field diisi (state tidak kosong)

                    ->required(fn($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord)
                    ->maxLength(255),
                // ...
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('name')->searchable(),
                \Filament\Tables\Columns\TextColumn::make('email'),
                \Filament\Tables\Columns\BadgeColumn::make('role')->colors([
                    'primary' => 'admin',
                    'info' => 'dosen',
                    'success' => 'mahasiswa',
                ]),
                \Filament\Tables\Columns\TextColumn::make('dosen.name')->label('Pembimbing')->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'dosen' => 'Dosen',
                        'mahasiswa' => 'Mahasiswa',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\UserResource\Pages\ListUsers::route('/'),
            'create' => \App\Filament\Resources\UserResource\Pages\CreateUser::route('/create'),
            'edit' => \App\Filament\Resources\UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
