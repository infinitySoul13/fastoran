<?php


namespace App\Http\Controllers\Admin;


use Allanvb\LaravelSemysms\Facades\SemySMS;
use App\Enums\DeliveryTypeEnum;
use App\Enums\UserTypeEnum;
use App\Http\Controllers\Controller;
//use App\Parts\Models\Fastoran\Kitchen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Classes\Utilits;
use App\User;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use Utilits;

    public function __construct()
    {
         $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('admin.users.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required',
        ]);
        $phone = $this->preparePhone($request->get("phone"));
        $code = random_int(100000, 999999);

        $user = User::where("phone", $phone)->withTrashed()->first();//$this->getUser();

        if (!is_null($user)) {
            if (!is_null($user->deleted_at)) {
                $user->deleted_at = null;
                $user->save();
            }
            return response()->json([
                'status' => 201,
                'message' => 'Пользователь уже был создан ранее!'
            ], 201);
        }

        $user = new User([
            'name' => $request->name ?? '',
            'email' => $request->email ?? $phone . "@fastoran.com",
            'password' => bcrypt($code),
            'phone' => $phone,
            'active' => true,
            'auth_code' => $code,
            'user_type' => $request->user_type,
            'deliveryman_type' => $request->deliveryman_type,
            'bonus' => 0
        ]);

        $user->save();

        return response()
            ->json([
                'status' => 200,
                'user' => $user,
                "message" => "Пользователь успешно добавлен"
            ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $param = $request->get("param");
        $value = $request->get("value");

        $user = User::find($id);
        $user[$param] = $value;
        $user->save();

        return response()
            ->json([
                "message" => "Изменения сохранены",
            ],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();

        return response()
            ->json([
                "message" => "Пользователь успешно удален",
                "status" => 200
            ]);
    }

    public function get()
    {
        $users = User::all();
        $deleted_users = User::onlyTrashed()->get();
        $types = UserTypeEnum::toSelectArray();
        $delivery_types = DeliveryTypeEnum::toArray();
        return response()
                ->json([
                    'users' => $users,
                    'deleted_users' => $deleted_users,
                    'types' => $types,
                    'delivery_types' => $delivery_types,
                ], 200);
    }
    public function getActiveUsers()
    {
        $users = User::where('active', 1)->get();

        return response()
            ->json([
                "message" => "success",
                "status" => 200,
                "users" => $users
            ]);
    }

    public function getNonActiveUsers()
    {
        $nonactive_users = User::where('active', 0)->get();

        return response()
            ->json([
                "message" => "success",
                "status" => 200,
                "nonactive_users" => $nonactive_users
            ]);
    }

    public function getOnlyTrashedUsers()
    {
        $deleted_users = User::onlyTrashed()->get();

        return response()
            ->json([
                "message" => "success",
                "status" => 200,
                "deleted_users" => $deleted_users
            ]);
    }

    public function changePassword(Request $request)
    {
        $user = User::find($request->id);
        $user->password = bcrypt($request->password);
        $user->save();

        return response()
            ->json([
                "message" => "success",
                "status" => 200,
            ]);
    }
    public function sendAuthCode(Request $request)
    {
        $user = User::find($request->id);
        SemySMS::sendOne([
            'to' => $user->phone,
            'text' => "Ваш пароль для доступа к ресурсу https://fastoran.com: $user->code"
        ]);
        return response()
            ->json([
                "message" => "success",
                "status" => 200,
            ]);
    }

    public function restore($id)
    {
        $user = User::onlyTrashed()->where('id', $id)->restore();

        return response()
            ->json([
                "message" => "Пользователь восстановлен",
                "status" => 200,
            ]);
    }
    public function getPhones(Request $request)
    {
        $users = User::all();
        $phones = array();
        foreach ($users as $user) {
            array_push($phones, $user->phone);
        }

        return response()
            ->json([
                "message" => "Телефоны успешно подгружены",
                'phones' => $phones,
                "status" => 200,
            ]);
    }

}
