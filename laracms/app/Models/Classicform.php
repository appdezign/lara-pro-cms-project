<?php

namespace Lara\App\Models;

use Lara\Common\Http\Concerns\HasLanguage;
use Lara\Common\Models\BaseModel;

class Classicform extends BaseModel
{
    use HasLanguage;

    protected $table = 'lara_form_classicforms';
}
