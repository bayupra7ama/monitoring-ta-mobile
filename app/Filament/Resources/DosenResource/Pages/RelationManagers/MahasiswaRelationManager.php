<?php
namespace App\Filament\Resources\DosenResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;

class MahasiswaRelationManager extends RelationManager
{
    protected static string $relationship = 'mahasiswa'; // ⬅️ relasi di model User

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nama Mahasiswa')->searchable(),
                TextColumn::make('nim_nidn')->label('NIM'),
                TextColumn::make('jurusan'),
                TextColumn::make('prodi'),
            ]);
    }
}
