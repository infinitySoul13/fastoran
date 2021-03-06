<?php

namespace App\Parts\Models\Fastoran;

use App\Enums\ContentTypeEnum;
use App\Enums\UserTypeEnum;
use BenSampo\Enum\Traits\CastsEnums;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Kitchen extends Model
{
    use CastsEnums, SoftDeletes;
    protected $fillable = [
        'name',
        'img',
        'view',
        'alt_description'
    ];

    protected $hidden = [
        "created_at","updated_at"
    ];

    protected $appends = ["rest_count","rating"];

    public function getRestCountAttribute(){
        return $this->restorans()->count();
    }

    public function restorans()
    {
        return $this->belongsToMany(Restoran::class, 'kitchen_in_restorans', 'kitchen_id', 'restoran_id')
            ->withTimestamps();
    }

    public function getRatingAttribute()
    {
        return Rating::where("content_type", ContentTypeEnum::Kitchen)
            ->select(["dislike_count","like_count"])
            ->where('content_id', $this->id)
            ->first();
    }
}
