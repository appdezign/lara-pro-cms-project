<?php

namespace Lara\App\Models;

use Lara\Common\Http\Concerns\HasLanguage;
use Lara\Common\Models\BaseModel;

class Contactform extends BaseModel
{
    use HasLanguage;

    protected $table = 'lara_form_contactforms';
}
