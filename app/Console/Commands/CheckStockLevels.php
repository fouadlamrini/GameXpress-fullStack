<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Command;

class CheckStockLevels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the stock levels and notify about low stock';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = Product::whereColumn('stock', '<=', 'critical_stock_threshold')
            ->where('stock_notification_sent', false)
            ->get();

        $users = User::role(['product_manager', 'super_admin'])->get();
        $this->info('found ' . $products->count() . ' products with low stock');
        foreach ($products as $product) {
            $product->stock_notification_sent = true;
            $product->save();

            foreach ($users as $user) {
                $user->notify(new LowStockNotification($product));
            }
            
        }

        $this->info('Stock levels checked and notifications sent successfully.');
    }
}
