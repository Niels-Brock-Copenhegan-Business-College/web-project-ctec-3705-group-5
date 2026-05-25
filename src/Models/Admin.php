<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Admin extends Model {
    protected $table    = 'admins';
    protected $fillable = ['username','email','password_hash','role'];
    protected $hidden   = ['password_hash'];
    const UPDATED_AT    = null;
}
