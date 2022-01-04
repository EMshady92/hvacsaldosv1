<?php

namespace App\Http\Livewire;

use App\Models\Cancellation;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class StatusOrder extends Component
{

    public $order, $status, $buyer, $isOpen, $message, $comments;
    protected $listeners = ['changeModal'];
    protected $rules = [
        'message' => 'required',
    ];

    public function mount()
    {
        $this->isOpen = 0;
        $this->status = $this->order->status;
        $this->buyer = User::where('users.id', $this->order->user_id)->first();
        $company_info = DB::table('companies')
            ->where('user_id', $this->order->user_id)
            ->first();
        $this->buyer->company_info = $company_info;
        if($this->status == 5){
            $this->comments = Cancellation::where('order_id', $this->order->id)->first()->comments;
        }
    }

    public function update()
    {
        $this->order->status = $this->status;
        $product = Product::where('id', json_decode($this->order->content)->id)->first();
        // producto pagado
        if ($this->order->status == 3) {
            $notification = 'Se ha registrado el pago de tu compra. <a class="block underline text-blue-900" href="/products' . json_decode($this->order->content)->options->slug . '">Ver producto</a>';
            $this->createNotification($notification, $this->order->user_id, json_decode($this->order->content)->id, false, 1);
            $notification = 'Se ha realizado el pago por tu producto, realiza el envío y termina tu venta. <a class="block underline text-blue-900" href="/products' . json_decode($this->order->content)->options->slug . '">Ver producto</a>';
            $this->createNotification($notification, $this->order->seller_id, json_decode($this->order->content)->id, false, 1);

            event(new \App\Events\NavNotification());

            // notificaciones moviles
            $titulos['es'] = 'Orden pagada!';
            $contenido['es'] = 'Tu orden ha sido pagada, realiza el envío y termina tu venta.';
            $users_ids = [strval($this->order->seller_id)];
            app(NotificationController::class)->triggerNotification($titulos,$contenido, $product, $users_ids, null);
        }
        // producto enviado
        if ($this->order->status == 6) {
            $notification = 'Tu pedido va en camino. <a class="block underline text-blue-900" href="/orders' . json_decode($this->order->content)->id . '">Ver producto</a>';
            $this->createNotification($notification, $this->order->user_id, json_decode($this->order->content)->id, false, 1);
            $notification = 'Envío registrado correctamente, nos estaremos comunicando contigo cuando sea entregado para realizar tu pago. <a class="block underline text-blue-900" href="/adin-orders-' . json_decode($this->order->content)->id . '">Ver producto</a>';
            $this->createNotification($notification, $this->order->seller_id, json_decode($this->order->content)->id, false, 1);

            event(new \App\Events\NavNotification());

            // notificaciones moviles
            $titulos['es'] = 'Pedido en camino!';
            $contenido['es'] = 'Tu pedido se encuentra en camino.';
            $users_ids = [strval($this->order->user_id)];
            app(NotificationController::class)->triggerNotification($titulos,$contenido, $product, $users_ids, null);
        }
        // producto entregado
        if ($this->order->status == 4) {
            $notification = 'Orden entregada!. <a class="block underline text-blue-900" href="/orders/' . json_decode($this->order->content)->id . '">Ver orden</a>';
            $this->createNotification($notification, $this->order->user_id, json_decode($this->order->content)->id, false, 1);
            $notification = 'Se ha entregado tu pedido satisfactoriamente!. <a class="block underline text-blue-900" href="/admin/orders/' . json_decode($this->order->content)->id . '">Ver orden</a>';
            $this->createNotification($notification, $this->order->seller_id, json_decode($this->order->content)->id, false, 1);

            event(new \App\Events\NavNotification());

            // notificaciones moviles
            $titulos['es'] = 'Orden entregada!';
            $contenido['es'] = 'Tu orden ha sido entregada correctamente.';
            $users_ids = [strval($this->order->seller_id)];
            app(NotificationController::class)->triggerNotification($titulos,$contenido, $product, $users_ids, null);
        }
        

        $this->order->save();
        $this->emit('updated');
    }

    public function cancel()
    {
        $rules = $this->rules;
        $this->validate($rules);
        $notification_user = 'Orden cancelada - ' . json_decode($this->order->content)->name . '<a class="block underline text-blue-900" href="/orders/' . $this->order->id . '">Ver producto</a>';
        $notification_admin = 'Orden cancelada - ' . json_decode($this->order->content)->name . '<a class="block underline text-blue-900" href="/admin/orders/' . $this->order->id . '">Ver producto</a>';;
        $this->order->status = $this->status;
        $this->order->save();
        $this->createCancellation($this->order->id);
        $this->returnStock($this->order);
        $this->createNotification($notification_user, $this->order->user_id, json_decode($this->order->content)->id, false, null);
        $users = User::whereHas(
            'roles',
            function ($q) {
                $q->where('name', 'admin')->orWhere('name', 'user');
            }
        )
            ->where('country_id', Auth::user()->country_id)
            ->get();
        foreach ($users as $user) {
            $this->createNotification($notification_admin, $user->id, json_decode($this->order->content)->id, true, null);
        }
        event(new \App\Events\NavNotification());
    }

    public function createNotification($notification, $user_id, $product_id, $isAdmin, $icon)
    {
        Notification::create([
            'notification' => $notification,
            'user_id' => $user_id,
            'admin' => $isAdmin,
            'product_id' => $product_id,
            'icon' => $icon
        ]);
    }

    public function createCancellation($order_id){
        Cancellation::create([
            'comments' => $this->message,
            'user_id' => Auth::user()->id,
            'order_id' => $order_id
        ]);
        $this->comments = $this->message;
    }

    public function returnStock(Order $order){
        $product_id = json_decode($order->content)->id;
        $qty = json_decode($order->content)->qty;
        Product::where('id', $product_id)
                ->update([
                    'quantity' => DB::raw('quantity+'.$qty)
                ]);
    }

    public function render()
    {

        $items = json_decode($this->order->content);
        $envio = json_decode($this->order->envio);

        return view('livewire.status-order', compact('items', 'envio'));
    }

    public function changeModal()
    {
        $this->isOpen = !$this->isOpen;
    }
}
