<?php
// ── EnergyReading Model ─────────────────────────────────────────────────────
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnergyReading extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id','solar_kw','wind_kw','demand_kw',
        'battery_soc','temperature','wind_speed',
    ];
    protected $casts = [
        'solar_kw'    => 'float',
        'wind_kw'     => 'float',
        'demand_kw'   => 'float',
        'battery_soc' => 'float',
        'temperature' => 'float',
        'wind_speed'  => 'float',
    ];
    public function user() { return $this->belongsTo(User::class); }
}
