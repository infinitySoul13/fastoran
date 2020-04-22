<?php

namespace App\Http\Controllers\Fastoran;

use App\Classes\Utilits;
use App\Enums\OrderStatusEnum;
use App\Enums\OrderTypeEnum;
use App\Enums\UserTypeEnum;
use App\Events\SendSmsEvent;
use App\Http\Controllers\Controller;
use App\Parts\Models\Fastoran\DeliveryQuest;
use App\Parts\Models\Fastoran\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Parts\Models\Fastoran\OrderDetail;
use App\Parts\Models\Fastoran\RestMenu;
use App\Parts\Models\Fastoran\Restoran;
use App\User;
use Illuminate\Http\Request;


class OrderController extends Controller
{
    use Utilits;

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = $this->getUser();

            if (is_null($user))
                return response()
                    ->json([
                        "message" => "User not found",
                        "orders" => [],
                        "status" => 404
                    ]);

            return response()
                ->json([
                    "message" => "Success",
                    'orders' => Order::where("user_id", $user->id)->get(),
                    "status" => 200
                ]);
        }

        $orders = Order::orderBy('id', 'DESC')
            ->paginate(15);

        return view('admin.orders.index', compact('orders'))
            ->with('i', ($request->get('page', 1) - 1) * 15);
    }

    public function checkValidCode(Request $request)
    {
        $phone = $request->get("phone") ?? '';
        $code = $request->get("code") ?? '';


        $phone = $this->preparePhone($phone);

        $user = User::where("phone", $phone)->first();

        if (is_null($user))
            return response()
                ->json([
                    "message" => "Номер телефона не найден!",
                    "is_valid" => false,
                ]);

        if ($user->auth_code != $code)
            return response()
                ->json([
                    "message" => "Вам на телефон отправлен СМС код подветрждения!",
                    "is_valid" => false,
                ]);

        return response()
            ->json([
                "message" => "Номер успешно подтвержден!",
                "is_valid" => true,
            ]);


    }

    public function resendSmsVerify(Request $request)
    {
        $phone = $this->preparePhone($request->get("phone"));
        $user = User::where("phone", $phone)->first();


        if (!is_null($user)) {
            event(new SendSmsEvent($user->phone, "Ваш пароль для доступа к ресурсу https://fastoran.com: " . $user->auth_code));
            return response()
                ->json([
                    "message" => "СМС успешно отправлено",
                ]);
        }

        $this->doHttpRequest(is_null($user) ?
            env('APP_URL') . 'api/v1/auth/signup_phone' :
            env('APP_URL') . 'api/v1/auth/sms', [
            [
                'phone' => $phone,
            ],
        ]);

        return response()
            ->json([
                "message" => "На ваш номер отправлен СМС с кодом",
            ]);

    }

    public function sendSmsVerify(Request $request)
    {

        $phone = $this->preparePhone($request->get("phone"));

        $user = User::where("phone", $phone)->first();

        return $this->doHttpRequest(is_null($user) ?
            env('APP_URL') . 'api/v1/auth/signup_phone' :
            env('APP_URL') . 'api/v1/auth/sms', [
                'phone' => $phone,

            ]
        );


    }

    public function show($id)
    {
        return response()
            ->json([
                "order" => Order::with(["restoran"])
                    ->whereDate('created_at', Carbon::today())
                    ->where("id", $id)
                    ->orderBy("id", "DESC")
                    ->first()
            ]);
    }

    public function store(Request $request)
    {
        $phone = $this->preparePhone($request->get("phone") ?? $request->get("receiver_phone"));

        $user = User::where("phone", $phone)->first();//$this->getUser();

        if (is_null($user))

            $this->doHttpRequest(env('APP_URL') . 'api/v1/auth/signup_phone', [
                'phone' => $phone,
                'name' => $request->name ?? $request->receiver_name ?? ''
            ]);

        /*      if (!is_null($client)) {
                  $message = "Заказ с Андройд устройства (временно в ручном режиме):\nПерезвоните на *$phone* для уточнения заказа!";
                  $this->sendMessageToTelegramChannel(env("TELEGRAM_FASTORAN_ADMIN_CHANNEL"), $message);
                  return response()
                      ->json([
                          "message" => "Сообщение с Андройд успешно получено",
                          "status" => 200
                      ]);
              }*/


        $user = User::where("phone", $phone)->first();

        if (is_null($user))
            return response()
                ->json([
                    "message" => "Что-то пошло не так! Проверьте данные!"
                ], 200);


        $order = Order::create($request->all());

        $tmp_custom_details = "";

        if (!is_null($order->custom_details))
            if (count($order->custom_details) > 0) {
                $tmp_custom_details = "*Дополнительно к заказу:*\n";
                $sum = 0;
                foreach ($order->custom_details as $key => $custom_detail) {
                    $detail = (object)$custom_detail;
                    $sum += $detail->price;
                    $tmp_custom_details .= ($key + 1) . "# " . $detail->name . " (" . $detail->price . " руб.)\n";
                }

                $tmp_custom_details .= "Предполагаемая сумма:* $sum руб.*\n";
            }

        $coords = (object)$this->getCoordsByAddress($request->get("receiver_address"));
        $order->latitude = $coords->latitude;
        $order->longitude = $coords->longitude;
        $order->user_id = $user->id;
        $order->save();

        $order_details = $request->get("order_details");

        $delivery_order_tmp = "";

        foreach ($order_details as $od) {

            $emptyProductId = true;
            if (isset($od["product_id"])) {
                $emptyProductId = false;
                $detail = OrderDetail::create([
                    "product_details" => RestMenu::find($od["product_id"]),
                    'price' => $od["price"],
                    'count' => $od["count"],
                    'order_id' => $order->id,
                ]);

            }

            if ($emptyProductId) {
                $detail = OrderDetail::create($od);
                $detail->order_id = $order->id;
                $detail->save();

            }

            $local_tmp = sprintf("#%s %s (%s) %s шт. %s руб.\n",
                $detail->product_details["id"],
                $detail->product_details["food_name"],
                $detail->more_info ?? '-',
                $detail->count,
                $detail->product_details["food_price"]
            );

            $delivery_order_tmp .= $local_tmp;
        }

        $rest = Restoran::find($order->rest_id);

        if (is_null($rest->latitude) || is_null($rest->longitude)) {
            $coords = (object)$this->getCoordsByAddress($rest->address);
            $rest->latitude = $coords->latitude;
            $rest->longitude = $coords->longitude;
            $rest->save();
        }
        $range = ($this->calculateTheDistance(
                $order->latitude ?? 0,
                $order->longitude ?? 0,
                $rest->latitude ?? 0,
                $rest->longitude ?? 0) / 1000);


        $price2 = $range <= 2 ? 50 : ceil(env("BASE_DELIVERY_PRICE") + (($range + 2) * env("BASE_DELIVERY_PRICE_PER_KM")));


        if (!is_null($order->custom_details))
            if (count($order->custom_details) > 0)
                $price2 += 50;

        $message = sprintf("*Заявка #%s*\nРесторан:_%s_\nФ.И.О.:_%s_\nТелефон:_%s_\nЗаказ:\n%s\nЗаметка к заказу:\n%s\n\n%s\nАдрес доставки:%s\nПолная цена доставки:*%s руб.*(Дистанция:%.2fкм)\nЦена основного заказа:*%s руб.*",
            $order->id,
            $rest->name ?? "Заведение без имени (ошибка)",
            $order->receiver_name ?? $user->name ?? 'Без имени',
            $order->receiver_phone ?? $user->phone ?? 'Без номера телефона (ошибка)',
            $delivery_order_tmp,
            $order->receiver_order_note ?? "Не указана",
            $tmp_custom_details ?? "Нет дополнительных позиций",
            $order->receiver_address ?? "Не задан",
            $price2,
            $range,
            $order->summary_price
        );

        $order->delivery_price = $price2;
        $order->delivery_range = floatval(sprintf("%.2f", ($range <= 2 ? $range : ($range + 2))));
        $order->save();

        $orderId = $this->prepareNumber($order->id);

        event(new SendSmsEvent($user->phone, "Ваш #$order->id заказ в обработке!"));

        $this->sendToTelegram($rest->telegram_channel, $message, [
            [
                ["text" => "Подтвердить заказ!", "url" => "https://t.me/delivery_service_dn_bot?start=001$orderId"],
                ["text" => "Отменить заказ!", "url" => "https://t.me/delivery_service_dn_bot?start=002$orderId"]
            ]
        ]);

        return response()
            ->json([
                "message" => $message,
                "order_id" => $order->id,
                "status" => 200
            ]);
    }

    public function sendDeliverymanQuest(Request $request)
    {
        $request->validate([
            'quest_type' => 'required',
            'description' => 'required',
            'point_a.name' => 'required',
            'point_a.phone' => 'required',
            'point_a.city' => 'required',
            'point_a.street' => 'required',
            'point_a.home_number' => 'required',
            'point_b.name' => 'required',
            'point_b.phone' => 'required',
            'point_b.city' => 'required',
            'point_b.street' => 'required',
            'point_b.home_number' => 'required',

        ]);


        $quest = DeliveryQuest::create($request->all());

        $point_a = (object)$quest->point_a;
        $point_b = (object)$quest->point_b;

        $tmp_users = [
            [
                "name" => $point_a->name, "phone" => $point_a->phone
            ],
            [
                "name" => $point_b->name, "phone" => $point_b->phone
            ]
        ];

        foreach ($tmp_users as $u) {
            $u = (object)$u;
            $phone = $this->preparePhone($u->phone);

            $user = User::where("phone", $phone)->first();

            if (is_null($user))
                $this->doHttpRequest(
                    env('APP_URL') . 'api/v1/auth/signup_phone', [
                        'phone' => $phone,
                    ]
                );
        }

        $res = $this->doHttpRequest(
            env('APP_URL') . 'api/v1/custom_range', [
                'address_a' => sprintf("Украина, г. %s, %s, %s",
                    $point_a->city,
                    $point_a->street,
                    $point_a->home_number
                ),
                'address_b' => sprintf("Украина, г. %s, %s, %s",
                    $point_b->city,
                    $point_b->street,
                    $point_b->home_number
                ),
            ]
        );

        $res = json_decode($res->getContent());

        $order = Order::create([
            'rest_id' => null,
            'user_id' => (User::where("phone", $this->preparePhone($point_a->phone))->first())->id,
            'status' => OrderStatusEnum::InProcessing,
            'delivery_price' => intval($res->price),
            'delivery_range' => floatval($res->range),
            'delivery_note' => 'Заказ курьера',
            'receiver_name' => $point_b->name,
            'receiver_phone' => $point_b->phone,
            'order_type' => OrderTypeEnum::DeliverymanQuest,
            'receiver_address' => sprintf("Украина, г. %s, %s, %s",
                $point_b->city,
                $point_b->street,
                $point_b->home_number
            ),
        ]);

        $quest->order_id = $order->id;
        $quest->range = floatval($res->range);
        $quest->price = intval($res->price);
        $quest->save();

        $prices = [0, 50, 200, 500, 50];

        $work_price = $prices[$quest->quest_type >= count($prices) ? 0 : $quest->quest_type];

        $message = sprintf("*Заявка на услуги курьера #%s*\n*Точка А*\nФ.И.О.:%s\nАдрес:%s\nТелефон:%s\nДополнительная информация:%s\n*Точка Б*\nФ.И.О.:%s\nАдрес:%s\nТелефон:%s\nДополнительная информация:%s\n\n*Заказ:*\n%s\n\nСтоимост услуг курьера: *%s руб. (расстояние %s км + %s руб. за работу) *",
            $order->id,
            $point_a->name,
            $point_a->city . " " . $point_a->street . " " . $point_a->home_number,
            $point_a->phone,
            $point_a->more_info,
            $point_b->name,
            $point_b->city . " " . $point_b->street . " " . $point_b->home_number,
            $point_b->phone,
            $point_b->more_info,
            $quest->description,
            $quest->price + $prices[$quest->quest_type >= count($prices) ? 0 : $quest->quest_type],
            $quest->range,
            $work_price
        );

        $orderId = $this->prepareNumber($order->id);

        event(new SendSmsEvent($user->phone, "Ваш #$order->id заказ в обработке!"));

        $this->sendMessageToTelegramChannel(env("TELEGRAM_FASTORAN_ADMIN_CHANNEL"), $message, [
            [
                ["text" => "Подтвердить заказ!", "url" => "https://t.me/delivery_service_dn_bot?start=001$orderId"],
                ["text" => "Отменить заказ!", "url" => "https://t.me/delivery_service_dn_bot?start=002$orderId"]
            ]
        ]);
        return response()
            ->json([
                "message" => $message,
                "status" => 200
            ]);

    }

    public function sendCustomOrder(Request $request)
    {
        $phone = $this->preparePhone($request->get("phone"));
        $name = $request->get("name") ?? '';
        $address = $request->get("address") ?? '';
        $more_info = $request->get("more_info") ?? '';

        $user = $this->getUser();

        if (is_null($user))
            $this->doHttpRequest(env('APP_URL') . 'api/v1/auth/signup_phone', [
                'phone' => $phone,
                'name' => $name

            ]);

        $user = User::where("phone", $phone)->first();

        $coords = (object)$this->getCoordsByAddress($address);

        $order_details = $request->get("order_details");

        $order = Order::create([
            'rest_id' => null,
            'user_id' => $user->id,
            'latitude' => $coords->latitude,
            'longitude' => $coords->longitude,
            'status' => OrderStatusEnum::InProcessing,
            'delivery_price' => 100,
            'delivery_range' => 0,
            'delivery_note' => 'Пользовательский заказ',
            'receiver_name' => $name,
            'receiver_phone' => $phone,
            'receiver_delivery_time' => '',
            'receiver_address' => $address,
            'custom_details' => $order_details,
            'order_type' => OrderTypeEnum::UsersCustomOrder
        ]);


        $sum = 0;
        $delivery_order_tmp = "";
        foreach ($order_details as $key => $od) {

            $local_tmp = sprintf("#%s %s (%s руб.)\n",
                ($key + 1),
                $od["name"],
                $od["price"]
            );

            $sum += $od["price"];
            $delivery_order_tmp .= $local_tmp;
        }

        $message = sprintf("*Заявка на пользовательский заказ*\nФ.И.О.:%s\nАдрес:%s\nТелефон:%s\nДополнительная информация:%s\nЗаказ:\n%s\nПриблизительаня цена заказа: *%s руб.*",
            $name,
            $address,
            $phone,
            $more_info,
            $delivery_order_tmp,
            $sum
        );

        $orderId = $this->prepareNumber($order->id);

        $this->sendMessageToTelegramChannel(env("TELEGRAM_FASTORAN_ADMIN_CHANNEL"), $message, [
            [
                ["text" => "Подтвердить заказ!", "url" => "https://t.me/delivery_service_dn_bot?start=001$orderId"],
                ["text" => "Отменить заказ!", "url" => "https://t.me/delivery_service_dn_bot?start=002$orderId"]
            ]
        ]);

        return response()
            ->json([
                "message" => $message,
                "status" => 200
            ]);
    }

    public function getOrderHistory(Request $request)
    {
        $user = $this->getUser();

        if (is_null($user))
            return response()
                ->json([
                    "message" => "Пользователь не найден",
                    "orders" => [],
                    "status" => 404
                ]);

        $orders = Order::with(["details", "restoran"])
            ->whereDate('created_at', Carbon::today())
            ->where("user_id", $user->id)
            ->orderBy("id", "DESC")
            ->get();

        return $request->ajax() ? response()
            ->json([
                "message" => "История успешно загружена",
                "orders" => $orders,
                "status" => 200
            ]) :
            view("fastoran.profile")
                ->with("orders", $orders);
    }

    public function acceptOrder(Request $request, $orderId)
    {

        $order = Order::with(["restoran"])
            ->where("id", $orderId)
            ->first();

        $user = $this->getUser();

        $validator = Validator::make(
            [
                "user" => $user,
                "order" => $order,
            ],
            [
                'user' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value->user_type !== UserTypeEnum::Deliveryman) {
                            $fail('Пользователь не является доставщиком');
                        }
                    },
                ],
                'order' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if (!is_null($value->deliveryman_id)) {
                            $fail('Заказ уже взят доставщиком!');
                        }
                    },
                ]

            ],
            [
                'user.required' => 'Пользователь не найден',
                'order.required' => 'Заказ не найден',
            ]);

        if ($validator->fails())
            return response()
                ->json(
                    $validator->errors()->toArray(), 500
                );

        $order->status = OrderStatusEnum::InDeliveryProcess;
        $order->deliveryman_id = $user->id;
        $order->save();

        $message = sprintf("Заказ *#%s* (%s) взят доставщиком *#%s (%s)*",
            $order->id,
            $order->receiver_phone,
            $user->id,
            $user->phone ?? "Без номера"
        );

        //event(new SendSmsEvent($user->phone, "Ваш #$order->id заказ готовится!"));
        $this->sendToTelegram($order->restoran->telegram_channel, $message);

        return response()
            ->json([
                "message" => $message
            ], 200);
    }

    public function declineOrder(Request $request, $orderId)
    {
        $order = Order::with(["restoran"])
            ->where("id", $orderId)
            ->first();

        $user = $this->getUser();


        $validator = Validator::make(
            [
                "user" => $user,
                "order" => $order,
            ],
            [
                'user' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value->user_type !== UserTypeEnum::Deliveryman) {
                            $fail('Пользователь не является доставщиком');
                        }
                    },
                ],
                'order' => [
                    'required',
                    function ($attribute, $value, $fail) use ($user) {
                        if (is_null($user)) {
                            $fail("Ошибка валидации пользователя");
                            return;
                        }

                        if ($value->deliveryman_id !== $user->id) {
                            $fail(sprintf("Заказ #%s не принадлежит доставщику #%s",
                                $value->id,
                                $user->id
                            ));
                        }
                    },
                ]

            ],
            [
                'user.required' => 'Пользователь не найден',
                'order.required' => 'Заказ не найден',
            ]);

        if ($validator->fails())
            return response()
                ->json(
                    $validator->errors()->toArray(), 500
                );


        $order->status = OrderStatusEnum::DeclineByAdmin;
        $order->deliveryman_id = null;
        $order->save();

        $message = sprintf("Доставщик *#%s* отказался от заказа *#%s*",
            $user->id,
            $order->id
        );


        $this->sendToTelegram($order->restoran->telegram_channel, $message);

        return response()
            ->json([
                "message" => $message
            ], 200);
    }

    public function declineOrderAdmin(Request $request, $orderId)
    {
        $comment = $request->get("comment") ?? "Позиция отсутствует";

        $order = Order::with(["restoran", "user"])
            ->where("id", $orderId)
            ->first();

        $user = $this->getUser();

        $validator = Validator::make(
            [
                "user" => $user,
                "order" => $order,
            ],
            [
                'user' => 'required',
                'order' => 'required'
            ],
            [
                'user.required' => 'Доставщик не найден',
                'order.required' => 'Заказ не найден',
            ]);

        if ($validator->fails())
            return response()
                ->json(
                    $validator->errors()->toArray(), 500
                );

        $order->deliveryman_id = null;
        $order->status = OrderStatusEnum::DeclineByAdmin;
        $order->save();

        $message = sprintf("Заказ *#%s* отклонен!\nКоментарий:%s\nПерезвоните клиенту: %s!",
            $order->id,
            $comment,
            $order->user->phone ?? "Не найден номер телефона"
        );

        $this->sendToTelegram($order->restoran->telegram_channel, $message);

        return response()
            ->json([
                "message" => "Success"
            ], 200);
    }

    public function getDeliverymanOrders(Request $request)
    {
        $user = $this->getUser();

        $orders = Order::with(["details", "restoran", "details.product", "user"])
            ->where("deliveryman_id", $user->id)
            ->get();

        return response()
            ->json([
                "orders" => $orders
            ]);
    }

    public function getOrderById($orderId)
    {
        return Order::with(["restoran", "user", "details", "details.product", "deliveryman"])
            ->where("id", $orderId)
            ->first();
    }

    public function getCustomRange(Request $request)
    {
        $request->validate([
            'address_a' => 'required',
            'address_b' => 'required'
        ]);

        $point1 = $request->get("address_a") ?? '';
        $point2 = $request->get("address_b") ?? '';

        $coords1 = (object)$this->getCoordsByAddress($point1);
        $coords2 = (object)$this->getCoordsByAddress($point2);

        $range = ($this->calculateTheDistance(
                $coords1->latitude,
                $coords1->longitude,
                $coords2->latitude,
                $coords2->longitude) / 1000);

        $price = $range <= 2 ? 50 : ceil(env("BASE_DELIVERY_PRICE") + (($range + 2) * env("BASE_DELIVERY_PRICE_PER_KM")));


        return response()
            ->json([
                "range" => floatval(sprintf("%.2f", ($range <= 2 ? $range : ($range + 2)))),
                "price" => $price
            ]);
    }

    public function getRange(Request $request, $restId)
    {
        $request->validate([
            'address' => 'required'
        ]);

        $rest = Restoran::find($restId);

        if (is_null($rest->latitude) || is_null($rest->longitude) || $rest->latitude === 0 || $rest->longitude === 0) {
            $coords = (object)$this->getCoordsByAddress("Украина, ".$rest->address);
            $rest->latitude = $coords->latitude;
            $rest->longitude = $coords->longitude;
            $rest->save();
        }

        $coords = (object)$this->getCoordsByAddress($request->get("address"));

        $range = ($this->calculateTheDistance(
                $coords->latitude,
                $coords->longitude,
                $rest->latitude,
                $rest->longitude) / 1000);

        $price = $range <= 2 ? 50 : ceil(env("BASE_DELIVERY_PRICE") + (($range + 2) * env("BASE_DELIVERY_PRICE_PER_KM")));

        return response()
            ->json([
                "range" => floatval(sprintf("%.2f", ($range <= 2 ? $range : ($range + 2)))),
                "price" => $price,
                "latitude" => $coords->latitude,
                "longitude" => $coords->longitude
            ]);


    }

    public function setDeliveredStatus(Request $request, $orderId)
    {

        $order = Order::with(["restoran", "user"])
            ->where("id", $orderId)
            ->first();

        $user = $this->getUser();

        $validator = Validator::make(
            [
                "user" => $user,
                "order" => $order,
            ],
            [
                'user' => [
                    'required',
                    function ($attribute, $value, $fail) {
                        if ($value->user_type !== UserTypeEnum::Deliveryman) {
                            $fail('Пользователь не является доставщиком');
                        }
                    },
                ],
                'order' => [
                    'required',
                    function ($attribute, $value, $fail) use ($user) {
                        if (is_null($user)) {
                            $fail("Ошибка валидации пользователя");
                            return;
                        }

                        if ($value->deliveryman_id !== $user->id) {
                            $fail(sprintf("Заказ #%s не принадлежит доставщику #%s",
                                $value->id,
                                $user->id
                            ));
                        }
                    },
                ]

            ],
            [
                'user.required' => 'Пользователь не найден',
                'order.required' => 'Заказ не найден',
            ]);

        if ($validator->fails())
            return response()
                ->json(
                    $validator->errors()->toArray(), 500
                );


        $order->status = OrderStatusEnum::Delivered;
        $order->save();

        $message = sprintf("Доставщик *#%s* успешно доставил заказ *#%s*",
            $user->id,
            $order->id
        );


        event(new SendSmsEvent($order->user->phone, "Ваш заказ доставлен! https://fastoran.com"));
        $this->sendToTelegram($order->restoran->telegram_channel, $message);

        return response()
            ->json([
                "message" => $message
            ], 200);
    }

    public function setCommentToOrder(Request $request, $orderId)
    {
        $order = Order::with(["restoran"])
            ->where("id", $orderId)
            ->first();

        $comment = $request->get("comment") ?? '';

        $user = $this->getUser();

        if (strlen(trim($comment)) === 0) {
            $message = sprintf("Администратор *#%s* принял заказ *#%s* без пометки",
                $user->id,
                $order->id
            );

            $this->sendToTelegram($order->restoran->telegram_channel, $message);
            return response()
                ->json([
                    "message" => $message
                ], 200);
        }

        $order->delivery_note = $comment;
        $order->status = OrderStatusEnum::GettingReady;
        $order->save();

        $message = sprintf("Администратор *#%s* установил пометку к заказу *#%s*",
            $user->id,
            $order->id
        );

        $this->sendToTelegram($order->restoran->telegram_channel, $message);

        return response()
            ->json([
                "message" => $message
            ], 200);
    }

    public function setDeliverymanType(Request $request, $type)
    {
        $user = $this->getUser();

        $old_type = $user->deliveryman_type ?? UserTypeEnum::User;
        $user->deliveryman_type = $type ?? UserTypeEnum::User;
        $user->save();

        $deliveryman_status_text = ["Не установлен", "Пеший", "Велосипед", "Мотоцикл", "Машина"];

        $this->sendToTelegram($user->telegram_chat_id, sprintf("Доставщик #%s изменил тип доставки %s на %s",
            $user->id,
            $deliveryman_status_text[$old_type],
            $deliveryman_status_text[$type]
        ));

        return response()
            ->json([
                "message" => $deliveryman_status_text[$type]
            ], 200);
    }
}
