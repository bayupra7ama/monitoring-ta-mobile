<?php

namespace App\Filament\Resources\DosenResource\Pages;

use view;
use Filament\Forms;

use App\Models\User;
use Filament\Actions;
use Filament\Forms\Form;
use Filament\Pages\Actions\Action;
use Filament\Pages\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\DosenResource;

class ViewDosen extends ViewRecord
{
    protected static string $resource = DosenResource::class;

    public function getRelations(): array
    {
        return [
            \App\Filament\Resources\DosenResource\RelationManagers\MahasiswaRelationManager::class,
        ];
    }

    public function getHeaderActions(): array
{
    return [
        Action::make('tambahMahasiswaBimbingan')
            ->label('Tambah Mahasiswa Bimbingan')
            ->form([
                Forms\Components\Select::make('mahasiswa_id')
                    ->label('Pilih Mahasiswa')
                    ->options(User::where('role', 'mahasiswa')->whereNull('dosen_id')->pluck('name', 'id'))
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data) {
                $mahasiswa = User::find($data['mahasiswa_id']);
                $mahasiswa->update(['dosen_id' => $this->record->id]); // ← set pembimbing
                Notification::make()
                    ->title('Berhasil')
                    ->body("Mahasiswa {$mahasiswa->name} telah ditambahkan ke bimbingan.")
                    ->success()
                    ->send();
            }),
    ];
}
}
