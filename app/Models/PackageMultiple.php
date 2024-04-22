<?php
  
namespace App\Models;
  
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Package;
class PackageMultiple extends Model
{
    use HasFactory;
  
    protected  $table = 'multiple_packages_features';
    			
    protected $fillable = [
        'multi_pack_parent','multi_pack_value','multi_pack_status'
    ];

       /**
     * Get the user that owns the Package
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}