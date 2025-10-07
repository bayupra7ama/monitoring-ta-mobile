<?php

namespace App\Filament\Resources;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Dosen;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\DosenResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\DosenResource\RelationManagers;
use App\Filament\Resources\DosenResource\RelationManagers\MahasiswaRelationManager;

class DosenResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationLabel = 'Bimbingan';
    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?string $navigationGroup = 'Manajemen'; // Sama grup = 1 section


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('Email')->searchable(),
                Tables\Columns\TextColumn::make('nim_nidn')->label('NIDN'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(), // ⬅️ Tambah ini agar bisa klik lihat detail
                Tables\Actions\EditAction::make(),

            ]);
    }

    public static function getRelations(): array
    {
        return [
            MahasiswaRelationManager::class,
        ];
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDosens::route('/'),
            'create' => Pages\CreateDosen::route('/create'),
            'edit' => Pages\EditDosen::route('/{record}/edit'),
            'view' => Pages\ViewDosen::route('/{record}'), // ⬅️ WAJIB

        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', 'dosen');
    }
}
