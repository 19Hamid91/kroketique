<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages\CreateOrder;
use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Filament\Resources\OrderResource\Pages\ListOrders;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('customer_id')
                    ->label('Customer')
                    ->placeholder('Select a customer')
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('order_date')
                    ->label('Order Date')
                    ->default(now())
                    ->required(),
                TextInput::make('total_price')
                    ->label('Total Price')
                    ->placeholder('Total Price')
                    ->required()
                    ->readOnly()
                    ->numeric()
                    ->prefix('Rp')
                    ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 0),
                Select::make('status')
                    ->placeholder('Select a status')
                    ->default('Pending')
                    ->options([
                        'Pending' => 'Pending',
                        'Paid' => 'Paid',
                        'Delivered' => 'Delivered',
                        'Rejected' => 'Rejected',
                    ]),
                Repeater::make('products')
                    ->schema([
                        Select::make('product_id')
                            ->label('Product')
                            ->required()
                            ->options(Product::where('is_available', true)->pluck('name', 'id'))
                            ->reactive()
                            ->live()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('price')
                                    ->required()
                                    ->label('Price')
                                    ->reactive()
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(','),
                                Textarea::make('description')
                                    ->required()
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                                Toggle::make('is_available'),
                                Toggle::make('is_popular'),
                                FileUpload::make('image')
                                    ->nullable()
                                    ->image()
                            ])
                            ->createOptionUsing(function ($data) {
                                return Product::create($data)->id;
                            })
                            ->afterStateUpdated(function (callable $set, callable $get, $state) {
                                $product = Product::find($state);
                                if ($product) {
                                    $set('price_product', (float) $product->price);
                                } else {
                                    $set('price_product', 0);
                                }

                                self::calculateTotal($set, $get);
                            }),
                        TextInput::make('price_product')
                            ->label('Price')
                            ->placeholder('Price of product')
                            ->numeric()
                            ->required()
                            ->readOnly()
                            ->prefix('Rp')
                            ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 0),
                        TextInput::make('quantity')
                            ->label('Quantity')
                            ->required()
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->live()
                            ->afterStateUpdated(function (callable $set, callable $get) {
                                self::calculateTotal($set, $get);
                            }),
                        TextInput::make('total_price_product')
                            ->label('Total')
                            ->placeholder('Total price of product')
                            ->numeric()
                            ->required()
                            ->readOnly()
                            ->prefix('Rp')
                            ->currencyMask(thousandSeparator: ',',decimalSeparator: '.',precision: 0),
                    ])
                    ->columnSpan(2)
                    ->live()
                    ->reactive()
                    ->columns(4)
                    ->afterStateUpdated(function (callable $set, callable $get) {
                        self::updateTotalPrice($set, $get);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->searchable(),
                TextColumn::make('order_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->currency('IDR'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'danger',
                        'Paid' => 'warning',
                        'Delivered' => 'success',
                        'Rejected' => 'grey',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Pending' => 'Pending',
                        'Paid' => 'Paid',
                        'Delivered' => 'Delivered',
                        'Rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                EditAction::make(),
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
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }

    private static function calculateTotal(callable $set, callable $get)
    {
        $price = $get('price_product');
        $quantity = $get('quantity');

        $total = ((float) $price ?? 0) * ((float) $quantity ?? 0);
        $set('total_price_product', $total);
    }

    private static function updateTotalPrice(callable $set, callable $get)
    {
        $products = $get('products');
        $total = collect($products)->sum(function ($product) {
            return isset($product['total_price_product']) ? (float) $product['total_price_product'] : 0;
        });

        $set('total_price', $total);
    }
}
