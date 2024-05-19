<?php

namespace App\Services\Tenants;

use App\Models\Tenants\Product;
use App\Models\Tenants\Purchasing;
use App\Models\Tenants\Setting;
use App\Models\Tenants\Stock;

class StockService
{
    public function reduceStock(Product $product, $qty): void
    {
        if (Setting::get('selling_method', 'fifo') == 'normal') {
            $lastStock = $product->stocks()->where('stock', '>', 0)->orderBy('date', 'asc')->first();
        } else {
            $lastStock = $product->stockLatestIn()->first();
        }
        if ($lastStock) {
            if ($lastStock->stock < $qty) {
                $qty = $qty - $lastStock->stock;
                $lastStock->stock = 0;
                $lastStock->save();
                $this->reduceStock($product, $qty);
            } else {
                $lastStock->stock = $lastStock->stock - $qty;
                $lastStock->save();
            }
        } else {
            $product->stock = $product->stock - $qty;
            $product->save();
        }
    }

    public function create($data, ?Purchasing $purchasing = null): Stock
    {
        $data['date'] = $data['date'] ?? now();
        $stock = new Stock();
        $data['init_stock'] = $data['stock'];
        $stock->fill($data);
        $stock->product()->associate(Product::find($data['product_id']));
        if ($purchasing) {
            $stock->purchasing()->associate($purchasing);
        }
        $stock->save();

        return $stock;
    }

    public function update(Stock $stock, array $data, ?Purchasing $purchasing = null)
    {
        $data['init_stock'] = $data['stock'];
        $stock->fill($data);
        $stock->product()->associate(Product::find($data['product_id']));
        if ($purchasing) {
            $stock->purchasing()->associate($purchasing);
        }
        $stock->save();
    }
}