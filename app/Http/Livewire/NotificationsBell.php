<?php

namespace App\Http\Livewire;

use App\Models\Notification;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class NotificationsBell extends Component
{
    protected $listeners = ['newBellNotification'];

    public function newBellNotification(){
        $this->render();
    }
    public function render()
    {
        $notifications = Notification::where('notifications.user_id', Auth::user()->id)
                                    ->where('read', false)
                                    ->orderBy('notifications.created_at', 'desc')
                                    ->limit(10)
                                    ->get();
        foreach ($notifications as $notification) {
            if($notification->product_id){
                $product = Product::where('id', $notification->product_id)->first();
                $notification->image_url = $product->images()->first()->url;
                $notification->product_name = $product->name;
                $notification->product_slug = $product->slug;
            }else{
                $notification->image_url = null;
                $notification->product_name = null;
                $notification->product_slug = null;
            }
        }
        return view('livewire.notifications-bell', compact('notifications'));
    }
}
