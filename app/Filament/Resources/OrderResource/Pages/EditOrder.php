<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $order = $this->record;

        $data['products'] = $order->products->map(function ($orderProduct) {
            return [
                'product_id' => $orderProduct->pivot->product_id,
                'price_product' => $orderProduct->pivot->price,
                'quantity' => $orderProduct->pivot->quantity,
                'total_price_product' => $orderProduct->pivot->total_price,
            ];
        })->toArray();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        DB::transaction(function () use ($record, $data) {
            $record->update([
                'customer_id' => $data['customer_id'],
                'order_date' => $data['order_date'],
                'total_price' => $data['total_price'],
                'status' => $data['status'],
            ]);

            $record->products()->sync(
                collect($data['products'])->mapWithKeys(function ($product) {
                    return [
                        $product['product_id'] => [
                            'price' => $product['price_product'],
                            'quantity' => $product['quantity'],
                            'total_price' => $product['total_price_product'],
                        ],
                    ];
                })->toArray()
            );

        });
        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
