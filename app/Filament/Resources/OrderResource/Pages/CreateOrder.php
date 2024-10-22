<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderProduct;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $order = static::getModel()::create($data);
    
            foreach ($data['products'] as $productData) {
                if (isset($productData['product_id'])) {
                    $productData['order_id'] = $order->id;
                    $productData['price'] = $productData['price_product'];
                    $productData['total_price'] = $productData['total_price_product'];
    
                    OrderProduct::create($productData);
                }
            }
    
            return $order;
        });
    }
}
