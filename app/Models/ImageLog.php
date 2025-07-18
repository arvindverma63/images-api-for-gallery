<?php

namespace App\Models;
// app/Models/ImageLog.php\

use Illuminate\Database\Eloquent\Model;

class ImageLog extends Model
{
    protected $fillable = ['telegram_user_id', 'image'];
}
