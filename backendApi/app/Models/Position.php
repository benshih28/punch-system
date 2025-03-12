<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
/**
 * @OA\Schema(
 *     schema="Position",
 *     title="Position",
 *     description="職位資料結構",
 *     type="object",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="資深工程師"),
 *     @OA\Property(property="department_id", type="integer", nullable=true, example=2),
 *     @OA\Property(property="department", ref="#/components/schemas/Department")
 * )
 */
class Position extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'department_id'];

    // 這個職位所屬的部門 (1對1)
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }
}