<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Module extends Model {
    protected $table = 'modules';
    protected $fillable = ['title','code','description','credits','image','leader_id'];

    public function leader()     { return $this->belongsTo(Staff::class, 'leader_id'); }
    public function programmes() { return $this->belongsToMany(Programme::class, 'programme_modules', 'module_id', 'programme_id')->withPivot('year_of_study'); }
}
