<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name'])]
class EmploymentStatus extends Model
{
    use HasFactory;

    public function scopeAlphabetical(Builder $query): void
    {
        $query->orderBy('name');
    }
}
