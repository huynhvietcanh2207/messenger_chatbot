<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User3 extends Model {
    use HasFactory;

    protected $table = 'users3'; // Chỉ định bảng

    protected $fillable = [
        'messenger_id',
        'name',
    ];
    public $timestamps = true; // Bật timestamps, nếu không cần thì đặt false

}
