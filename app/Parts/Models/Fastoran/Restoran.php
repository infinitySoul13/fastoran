<?php

namespace App\Parts\Models\Fastoran;

use Illuminate\Database\Eloquent\Model;

class Restoran extends Model
{
    //
    protected $table = "restorans";

    protected $fillable = [
        'name',
        'category',
        'adress',
        'orientir',
        'city',
        'region_id',
        'phone1',
        'phone2',
        'www',
        'mail',
        'tim',
        'checkout',
        'dance',
        'karaoke',
        'wifi',
        'bussines',
        'parking',
        'children',
        'remark',
        'cont_face',
        'cont_phone',
        'vk_page',
        'odn_page',
        'inst_page',
        'manager',
        'logo',
        'money',
        'rating',
        'seo_domen',
        'seo_title',
        'seo_h1',
        'seo_description',
        'url',
        'view',
        'comments',
        'images',
        'reg_dat',
        'rest_like',
        'rest_antilike',
        'rest_img',
        'moderation',
        'tarif',
        'fav',
        'count_people',
        'special',
        'discount',
        'dir_mail',
        'bron_phone',
        'discount_text',
        'phone_view',
        'child',
        'min_sum',
        'price_delivery',
        'time_delivery',
        'filters',
        'fastoran_money',
        'sms',
        'start_lanch',
        'end_lanch',
    ];

    public function kitchens()
    {
        return $this->belongsToMany(Kitchen::class, 'kitchen_in_restorans', 'kitchen_id', 'restoran_id')
            ->withTimestamps();
    }

    public function menus()
    {
        return $this->hasMany(Menu::class, 'rest_id', 'id');
    }

    public function region()
    {
        return $this->hasOne(Region::class, 'id', 'region_id');
    }
}
