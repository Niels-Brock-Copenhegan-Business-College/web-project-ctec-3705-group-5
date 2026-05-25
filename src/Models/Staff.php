<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model {
    protected $table = 'staff';
    protected $fillable = ['name','email','bio','photo'];
    public $timestamps = true;
    const UPDATED_AT = null;

    public function ledProgrammes() { return $this->hasMany(Programme::class, 'leader_id'); }
    public function ledModules()    { return $this->hasMany(Module::class,    'leader_id'); }
}
