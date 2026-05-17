<?php

namespace App\Filament\Resources\Orders\RelationManagers;

// use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
// use Filament\Actions\CreateAction;
// use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
// use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
// use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class OrderDetailRelationManager extends RelationManager
{
    protected static string $relationship = 'OrderDetail';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                ImageColumn::make('product.image')
                    ->label('Image'),
                TextColumn::make('product.name')
                    ->label('Nama Produk'),
                TextColumn::make('product.price')
                    ->label('Harga Produk'),
                TextColumn::make('product.stock')
                    ->label('Stok Produk'),
                TextColumn::make('qty')
                    ->label('Jumlah'),
                TextColumn::make('subtotal')
                    ->label('Sub Total'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // CreateAction::make(),
                // AssociateAction::make(),
            ])
            ->recordActions([
                // EditAction::make(),
                // DissociateAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DissociateBulkAction::make(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
