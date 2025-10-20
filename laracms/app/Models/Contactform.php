<?php

namespace Lara\App\Models;

use Lara\Common\Models\BaseModel;

class Contactform extends BaseModel
{
    protected $table = 'lara_form_contactforms';

	protected array $appendCasts = [];

	public function __construct($attributes = [])
	{
		$this->mergeCasts($this->appendCasts);
		parent::__construct($attributes);
	}


}
