<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    
    public function getTabs(): array
    {
        return [
            'All' => Tab::make(),
            'Pending' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Pending'))
                ->icon('heroicon-s-clock')
                ->iconPosition(IconPosition::After),
            'Paid' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Paid'))
                ->icon('heroicon-s-check-badge')
                ->iconPosition(IconPosition::After),
            'Delivered' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Delivered'))
                ->icon('heroicon-s-truck')
                ->iconPosition(IconPosition::After),
            'Rejected' => Tab::make()
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'Rejected'))
                ->icon('heroicon-s-x-circle')
                ->iconPosition(IconPosition::After),
        ];
    }
}
