<?php

namespace Lara\App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Lara\App\Models\Blog;

use stdClass;

class CustomBlogController extends Controller
{

	protected $data;

	public function __construct()
	{
		$this->data = new stdClass();
	}

	public function index()
	{

		$this->data->objects = Blog::where('language', 'nl')->get();

		return view('lara-app::pages.custom-blog.index', [
			'data' => $this->data,
		]);
	}

	public function show(Request $request, int $id)
	{
		$this->data->object = Blog::find($id);

		return view('lara-app::pages.custom-blog.show', [
			'data' => $this->data,
		]);
	}

	public function edit(Request $request, int $id)
	{

		$this->data->object = Blog::find($id);

		return view('lara-app::pages.custom-blog.edit', [
			'data' => $this->data,
		]);
	}

	public function update(Request $request, int $id)
	{

		$object = Blog::find($id);

		$object->update($request->all());

		return redirect()->route('admin.custom-blog.edit', ['id' => $id]);

	}
}