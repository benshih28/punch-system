<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @OA\Schema(
 *     schema="Department",
 *     title="Department",
 *     description="部門資料結構",
 *     type="object",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="人事部")
 * )
 */
class Department extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    // 部門下的所有職位 (1對多)
    public function positions(): HasMany
    {
        return $this->hasMany(Position::class, 'department_id', 'id');
    }
}
