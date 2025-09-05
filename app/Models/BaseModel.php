<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class BaseModel extends Model
{
    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }
        
        return Str::snake(Str::pluralStudly(class_basename($this)));
    }
}