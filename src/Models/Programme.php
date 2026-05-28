<?php
// src/Models/Programme.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Programme extends Model {
    protected $table = 'programmes';
    protected $fillable = ['title','slug','level','description','duration_years','image','is_published','leader_id'];

    public function leader()    { return $this->belongsTo(Staff::class, 'leader_id'); }
    public function modules()   { return $this->belongsToMany(Module::class, 'programme_modules', 'programme_id', 'module_id')->withPivot('year_of_study'); }
    public function interests() { return $this->hasMany(Interest::class, 'programme_id'); }
}
