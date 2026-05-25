<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Interest extends Model {
    protected $table    = 'interest_registrations';
    protected $fillable = ['first_name','last_name','email','phone','programme_id'];
    const UPDATED_AT    = null;
    const CREATED_AT    = 'registered_at';

    public function programme() { return $this->belongsTo(Programme::class, 'programme_id'); }
}
