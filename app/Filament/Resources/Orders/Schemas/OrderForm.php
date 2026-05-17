<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Models\Customer;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;


class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('date_order')
                    ->required()
                    ->columnSpanFull()
                    ->dehydrated()
                    ->timezone('Asia/Makassar')
                    ->default(now())
                    ->disabled()
                    ->hiddenLabel()
                    ->prefix('Date :'),
                ToggleButtons::make('status')
                    ->label('Order Status')
                    ->columnSpanFull()
                    ->grouped()
                    ->default('new')
                    ->options([
                        'new' => 'new',
                        'procesing' => 'procesing',
                        'canceled' => 'canceled',
                        'completed' => 'completed',
                    ])
                    ->colors([
                        'new' => 'info',
                        'procesing' => 'warning',
                        'canceled' => 'danger',
                        'completed' => 'success'
                    ]),
                Grid::make()
                    ->columns(3)
                    ->columnSpanFull()
                    ->schema([

                        Group::make([
                            Section::make()
                                ->description('Customer Information')
                                ->columns(3)
                                ->schema([
                                    Select::make('customer_id')
                                        ->label('Name')
                                        ->relationship('customer', 'name')
                                        ->required()

                                        ->live()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            $customer = Customer::find($state);
                                            $set('phone', $customer?->phone);
                                            $set('address', $customer?->address);
                                        }),
                                    TextInput::make('phone')
                                        ->readOnly()
                                        ->placeholder('-'),

                                    TextInput::make('address')
                                        ->readOnly()
                                        ->placeholder('-'),
                                ]),

                            Section::make()
                                ->description('Detail Order')

                                ->schema([
                                    Repeater::make('OrderDetail')
                                        ->relationship()
                                        ->hiddenLabel()
                                        ->live() // Memastikan perubahan di dalam repeater langsung terbaca ke form utama
                                        ->columns(4)
                                        ->Schema([
                                            Select::make('product_id')
                                                ->required()
                                                ->relationship('product', 'name')
                                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                                ->live()
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    $product = Product::find($state);
                                                    $price = $product?->price ?? 0;
                                                    $qty = $get('qty') ?? 1;

                                                    $set('price', $price);
                                                    $set('qty', $qty);
                                                    $set('subtotal', $price * $qty);

                                                    $items = $get('../../OrderDetail') ?? [];
                                                    $total_price = collect($items)->sum(fn($item) => $item['subtotal'] ?? 0);
                                                    $set('../../total_price', $total_price);
                                                    // Panggil helper kalkulasi total
                                                    static::updateTotals($set, $get);
                                                    //fungsi agar saat tambah qty diskon dan total payment berubah
                                                    // $discount = $get('../../discount') ?? 0;
                                                    // $discount_amount = $total_price * $discount / 100;
                                                    // $set('../../discount_amount', $discount_amount);
                                                    // $set('../../total_payment', $total_price - $discount_amount);
                                                }),

                                            TextInput::make('price')
                                                ->numeric()
                                                ->readOnly()
                                                ->mask(RawJs::make('$money($input)'))
                                                ->stripCharacters(',')
                                                ->formatStateUsing(fn($state, Get $get) => $state ?? Product::find($get('product_id'))?->price ?? 0),
                                            TextInput::make('qty')
                                                ->live(onBlur: true)
                                                ->numeric()
                                                ->minValue(1)
                                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                    $price = (int) $get('price') ?? 0;
                                                    $set('subtotal', $price * (int)$state);

                                                    // Panggil helper kalkulasi total
                                                    static::updateTotals($set, $get);
                                                    // $items = $get('../../OrderDetail') ?? [];
                                                    // $total_price = collect($items)->sum(fn($item) => $item['subtotal'] ?? 0);
                                                    // $set('../../total_price', $total_price);
                                                    // //fungsi agar saat tambah qty diskon dan total payment berubah
                                                    // $discount = $get('../../discount') ?? 0;
                                                    // $discount_amount = $total_price * $discount / 100;
                                                    // $set('../../discount_amount', $discount_amount);
                                                    // $set('../../total_payment', $total_price - $discount_amount);
                                                }),
                                            TextInput::make('subtotal')
                                                ->numeric()
                                                ->disabled()
                                                ->prefix('IDR ')
                                                ->dehydrated(),
                                        ])
                                        ->addAction(fn(Action $action) => $action
                                            ->label('Add Product')
                                            ->color('primary')
                                            ->icon(Heroicon::OutlinedPlus)),
                                ]),
                        ])->columnSpan(2),
                        Section::make()
                            ->description('Payment Information')
                            ->columns(4)
                            ->schema([

                                TextInput::make('total_price')
                                    // ->live()
                                    ->columnSpanFull()
                                    ->default(0)
                                    ->required()
                                    ->numeric()
                                    ->prefix('IDR ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->readOnly(),

                                TextInput::make('discount')
                                    ->columnSpan(2)
                                    ->reactive()
                                    ->dehydrated()
                                    ->suffix('%')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        // Panggil helper kalkulasi total
                                        static::updateTotals($set, $get);
                                        // $discount = floatval($state) ?? 0;
                                        // $totalprice = $get('total_price') ?? 0;
                                        // $discount_amount = $totalprice * $discount / 100;
                                        // $set('discount_amount', $discount_amount);
                                        // $set('total_payment', $totalprice - $discount_amount);
                                    }),
                                TextInput::make('discount_amount')
                                    ->columnSpan(2)
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0)
                                    ->prefix('IDR ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(','),
                                TextInput::make('total_payment')
                                    ->columnSpanFull()
                                    ->disabled()
                                    ->dehydrated()
                                    ->default(0)
                                    ->prefix('IDR ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(','),
                                TextInput::make('cash')
                                    ->reactive()
                                    ->columnSpanFull()
                                    ->live(onBlur: true)
                                    ->numeric()
                                    // ->default(50000)
                                    ->prefix('IDR ')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->datalist([
                                        10000,
                                        20000,
                                        50000,
                                        100000,
                                        200000,
                                    ])
                                    ->live(onBlur: true) // Update otomatis saat kursor pindah
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $total_payment = $get('total_payment') ?? 0;
                                        $refund = $state - $total_payment;
                                        $set('refund', $refund);
                                    }),
                                // Select::make('cash')
                                //     ->label('Cash')
                                //     ->placeholder('Pilih atau ketik nominal...')
                                //     ->columnSpanFull()
                                //     ->options([
                                //         10000 => '10.000',
                                //         20000 => '20.000',
                                //         50000 => '50.000',
                                //         100000 => '100.000',
                                //         200000 => '200.000',
                                //     ])
                                //     ->searchable()
                                //     ->createOptionForm([
                                //         TextInput::make('custom_cash')
                                //             ->numeric()
                                //             ->label('Nominal Lainnya')
                                //             ->required()
                                //     ])
                                //     // Atau gunakan allowHtml jika ingin tampilan lebih cantik
                                //     ->prefix('IDR')
                                //     ->live() // Penting agar nilai Refund langsung terhitung otomatis
                                //     ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                //         $total_payment = $get('total_payment') ?? 0;
                                //         $refund = $state - $total_payment;
                                //         $set('refund', $refund);
                                //     }),
                                TextInput::make('refund')
                                    ->columnSpanFull()
                                    ->live(onBlur: true)
                                    ->disabled()
                                    ->prefix('IDR ')
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')

                            ])

                    ]),
            ]);
    }

    protected static function updateTotals(Set $set, Get $get): void
    {
        // 1. Hitung Total Price berdasarkan seluruh item di Repeater
        // Di v5, saat berada di dalam scope repeater, panggil root form menggunakan '../../'
        $items = $get('OrderDetail') ?? $get('../../OrderDetail') ?? [];

        $totalPrice = collect($items)->sum(function ($item) {
            return (int) ($item['subtotal'] ?? 0);
        });

        // 2. Ambil nilai diskon persen
        $discountPercent = floatval($get('discount') ?? $get('../../discount') ?? 0);

        // 3. Hitung nominal diskon & total yang harus dibayar
        $discountAmount = $totalPrice * $discountPercent / 100;
        $totalPayment = $totalPrice - $discountAmount;

        // 4. Set nilainya ke form (menggunakan target root/tanpa prefix jika dipicu dari luar repeater)
        $isInsideRepeater = $get('product_id') !== null || $get('qty') !== null;
        $prefix = $isInsideRepeater ? '../../' : '';

        $set($prefix . 'total_price', $totalPrice);
        $set($prefix . 'discount_amount', $discountAmount);
        $set($prefix . 'total_payment', $totalPayment);

        // 5. Update kembalian (refund) jika uang cash sudah diisi sebelumnya
        $cash = (int) str_replace(',', '', $get($prefix . 'cash') ?? 0);
        if ($cash > 0) {
            $set($prefix . 'refund', $cash - $totalPayment);
        }
    }
}
