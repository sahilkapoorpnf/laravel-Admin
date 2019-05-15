<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class ShoppingLocation extends Authenticatable
{
    use Notifiable;
    
    /* app/User.php */

    const ADMIN_TYPE = 'admin';
    const DEFAULT_TYPE = 'default';

    public function isAdmin()    {        
        return $this->type === self::ADMIN_TYPE;    
    }
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];
    
//     public function user()
//    {
//        return $this->belongsTo('App\User', 'id');
//    }
}
