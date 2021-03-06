<?php

namespace App\Http\Controllers;

use Allanvb\LaravelSemysms\Facades\SemySMS;
use App\Classes\Utilits;
use App\Parts\Models\Fastoran\Kitchen;
use App\Parts\Models\Fastoran\MenuCategory;
use App\Parts\Models\Fastoran\Order;
use App\Parts\Models\Fastoran\OrderDetail;

use App\Parts\Models\Fastoran\RestMenu;
use App\Parts\Models\Fastoran\Restoran;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Jenssegers\Agent\Facades\Agent;
use Telegram\Bot\Laravel\Facades\Telegram;

class RestController extends Controller
{
    use Utilits;

    public function __construct()
    {

    }

    public function mobile(Request $request)
    {


        $products = RestMenu::with(["restoran"])->get();

        $random_menus = $products
            ->shuffle()
            ->take(16);

        return view("mobile.pages.index", compact("random_menus"));

    }

    public function desktop(Request $request)
    {
        if (Agent::isMobile())
            return redirect()->route("mobile.index");

        return view('desktop');
    }

    public function searchFood(Request $request)
    {
        $food_name = $request->get("food_name") ?? null;
        $rest_name = $request->get("rest_name") ?? null;

        $products = null;

        if (is_null($rest_name) && !is_null($food_name))
            $products = RestMenu::query()
                ->where('food_name', 'LIKE', "%{$food_name}%")->paginate(100);

        if (!is_null($rest_name)) {
            $rest = (Restoran::with(["menus"])->where('name', 'LIKE', "%{$rest_name}%")->first());
            $products = is_null($rest) ? null : $rest->menus()->paginate(100);
        }

        if (is_null($products) || count($products) == 0)
            $products = RestMenu::paginate(100);


        return view('product-list', compact('products'))
            ->with('i', ($request->get('page', 1) - 1) * 100);
    }

    public function getMainPage(Request $request)
    {

        $sliderIndex = random_int(1, 3);

        $products = RestMenu::with(["restoran"])->get();

        $random_menu = $products
            ->shuffle()
            ->take(12);

        $kitchens_count = 10;//Kitchen::all()->count();
        $restorans_count = 10;//Restoran::all()->count();
        $menus_count = 400;//RestMenu::all()->count();
        $user_count = 30;//User::all()->count();

        $categories = MenuCategory::with(["menus"])->get();


        return view("main", compact("random_menu", "categories"))
            ->with("sliderIndex", $sliderIndex)
            ->with("kitchens_count", $kitchens_count)
            ->with("restorans_count", $restorans_count)
            ->with("menus_count", $menus_count)
            ->with("user_count", $user_count)
            ->with("products", $products);


    }


    public function getAllMenu(Request $request)
    {
        $products = RestMenu::paginate(20);

        return view('product-list', compact('products'))
            ->with('i', ($request->get('page', 1) - 1) * 20);
    }

    public function getRestList(Request $request)
    {
        $restorans = Restoran::with(["kitchens", "menus"])
            ->where("moderation", true)
            ->get();

        if ($request->ajax())
            return response()
                ->json([
                    'restorans' => $restorans,
                    'kitchens' => Kitchen::where("is_active", 1)->get()
                ]);

        return view("rest-list", compact("restorans"));
    }

    public function getRestListByKitchen(Request $request, $kitchenId)
    {
        $kitchen = Kitchen::with(["restorans"])
            ->where("id", $kitchenId)
            ->first();

        if (is_null($kitchen))
            return redirect()->route("main");

        $restorans = is_null($kitchen) ? null : $kitchen->restorans()->get();

        if ($request->ajax())
            return response()
                ->json([
                    'restorans' => $restorans
                ]);

        return view("rest-list", compact("restorans", "kitchen"));
    }

    public function getRestByDomain(Request $request, $domain)
    {

        $restoran = Restoran::with(["kitchens", "categories", "categories.menus"])->where("url", $domain)
            ->first();

        if (is_null($restoran))
            return redirect()->route("main");

        $products = RestMenu::where("rest_id", $restoran->id)->paginate(200);

        if ($request->ajax())
            return response()
                ->json([
                    'restoran' => $restoran
                ]);

        return view("rest", compact("restoran", "products"))
            ->with('i', ($request->get('page', 1) - 1) * 200);
    }

    public function getMenuByRestoran(Request $request, $id)
    {
        $restoran = Restoran::with(["menus"])
            ->where("id", $id)
            ->first();

        if (is_null($restoran))
            return redirect()->route("main");

        $categories = MenuCategory::all();


        return response()
            ->json([
                'restoran' => $restoran,
                "categories" => $categories
            ]);
    }


    public function sendWish(Request $request)

    {
        $request->validate([
            'phone' => "required",
            'email' => "nullable|email",
            "from" => "string|required",
            "message" => "required"
        ]);

        $phone = $request->get("phone") ?? '';
        $email = $request->get("email") ?? '';
        $from = $request->get("from") ?? '';
        $message = $request->get("message") ?? '';


        $tmp_message = sprintf("*Заявка на перезвон:*\nТелефон: %s\nПочта: %s\nФ.И.О.: %s\nСообщение: %s",
            $phone,
            $email,
            $from,
            $message
        );

        $this->sendMessageToTelegramChannel(env("TELEGRAM_FASTORAN_ADMIN_CHANNEL"), $tmp_message);
        if ($request->ajax())
            return response()
                ->json([
                    "message" => "success",
                    "status" => 200
                ]);

        return redirect()
            ->back()
            ->with("message", "Сообщение отправлено!");

    }

    public function sendRequest(Request $request)
    {
        $name = $request->get("name") ?? '';
        $phone = $request->get("phone") ?? '';
        $message = $request->get("message") ?? '';

        Telegram::sendMessage([
            'chat_id' => env("CHANNEL_ID"),
            'parse_mode' => 'Markdown',
            'text' => sprintf("*Заявка на обратный звонок*\n_%s_\n_%s_\n%s", $name, $phone, $message),
            'disable_notification' => 'false'
        ]);

        return response()
            ->json([
                "message" => "success",
                "status" => 200
            ]);
    }


}
