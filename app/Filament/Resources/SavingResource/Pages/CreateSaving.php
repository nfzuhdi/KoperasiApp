<?php

namespace App\Filament\Resources\SavingResource\Pages;

use App\Filament\Resources\SavingResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\SavingProduct;

class CreateSaving extends CreateRecord
{
    protected static string $resource = SavingResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $savingProduct = SavingProduct::find($data['saving_product_id']);
        
        $data['status'] = $savingProduct->contract_type ? 'pending' : 'active';
        $data['created_by'] = auth()->id();
        $data['account_number'] = 'TEMP'; // Will be generated after creation
        
        return $data;
    }

    protected function afterCreate(): void 
    {
        $saving = $this->record;
        
        // Generate account number after creation
        $saving->update([
            'account_number' => 'SAV' . str_pad($saving->id, 8, '0', STR_PAD_LEFT)
        ]);
    }
}

