<?php

namespace App\Jobs;

use App\Models\CartItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeleteProductJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


    protected $cartItemId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $cartItemId)
    {
        $this->cartItemId = $cartItemId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $cartItem = CartItem::find($this->cartItemId);
        if ($cartItem) {
            $cartItem->delete();
        }
    }
}
