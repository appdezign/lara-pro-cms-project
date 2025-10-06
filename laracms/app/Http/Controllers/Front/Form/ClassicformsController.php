<?php

namespace Lara\App\Http\Controllers\Front\Form;

use App\Http\Controllers\Controller;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;

use Illuminate\View\View;
use Lara\Common\Models\Page;
use Lara\App\Models\Blog;

use Lara\Front\Http\Concerns\FrontTrait;
use Lara\Front\Http\Concerns\HasFrontAuth;
use Lara\Front\Http\Concerns\HasFrontEntity;
use Lara\Front\Http\Concerns\HasFrontList;
use Lara\Front\Http\Concerns\HasFrontMenu;
use Lara\Front\Http\Concerns\HasFrontObject;
use Lara\Front\Http\Concerns\HasFrontRoutes;
use Lara\Front\Http\Concerns\HasFrontSecurity;
use Lara\Front\Http\Concerns\HasFrontTerms;
use Lara\Front\Http\Concerns\FrontThemeTrait;
use Lara\Front\Http\Concerns\HasFrontView;

use Lara\Front\Mail\MailConfirmation;

use Lara\Front\Rules\ReCaptcha;

use Jenssegers\Agent\Agent;

use LaravelLocalization;
use stdClass;

class ClassicformsController extends Controller
{

	use FrontTrait;
	use HasFrontAuth;
	use HasFrontEntity;
	use HasFrontList;
	use HasFrontMenu;
	use HasFrontObject;
	use HasFrontRoutes;
	use HasFrontSecurity;
	use HasFrontTerms;
	use FrontThemeTrait;
	use HasFrontView;

	/**
	 * @var string
	 */
	protected $modelClass = \Lara\App\Models\Classicform::class;

	/**
	 * @var string|null
	 */
	protected $routename;

	/**
	 * @var object
	 */
	protected $entity;

	/**
	 * @var string
	 */
	protected $language;

	/**
	 * @var object
	 */
	protected $data;

	/**
	 * @var bool
	 */
	protected $ismobile;

	/**
	 * @var object
	 */
	protected $globalwidgets;

	public function __construct()
	{

		// get language
		$this->language = LaravelLocalization::getCurrentLocale();

		// create an empty Laravel object to hold all the data (see: https://goo.gl/ufmFHe)
		$this->data = new stdClass();

		if (!App::runningInConsole()) {

			// get route name
			$this->routename = Route::current()->getName();

			// get entity
			$this->entity = $this->getFrontEntity($this->routename);

			// get default seo
			$this->data->seo = $this->getDefaultSeo($this->language);

			// get default layout
			$this->data->layout = $this->getDefaultThemeLayout();

			// get entity routes from menu
			$this->data->eroutes = $this->getMenuEntityRoutes($this->language);

			// get global widgets
			$this->globalwidgets = $this->getGlobalWidgets($this->language);

			// get agent
			$agent = new Agent();
			$this->ismobile = $agent->isMobile();

			// share data with all views, see: https://goo.gl/Aqxquw
			$this->middleware(function ($request, $next) {
				view()->share('entity', $this->entity);
				view()->share('language', $this->language);
				view()->share('ismobile', $this->ismobile);
				view()->share('globalwidgets', $this->globalwidgets);

				return $next($request);
			});
		}

	}

	/**
	 * Show form
	 *
	 * @param Request $request
	 * @return Application|Factory|View
	 * @throws BindingResolutionException
	 */
	public function form(Request $request)
	{

		// custom GET example
		$blogId = $this->getFrontRequestParam($request, 'blog', null, 'classicform', true);

		if ($blogId) {
			$this->data->blog = Blog::where('id', $blogId)->first();
		} else {
			$this->data->blog = null;
		}


		// get params
		$this->data->params = $this->getFrontParams($this->entity, $request);

		// get related module page for SEO and Intro
		$this->data->modulepage = $this->getModulePageBySlug($this->language, $this->entity, $this->entity->getMethod());

		// Use module page for Intro
		$this->data->page = $this->data->modulepage;

		// seo
		$this->data->seo = $this->getSeo($this->data->modulepage);

		// opengraph
		$this->data->opengraph = $this->getOpengraph($this->data->modulepage);

		// get language versions
		$this->data->langversions = $this->getFrontLanguageVersions($this->language, $this->entity);

		// header tags
		$this->data->htag = $this->getEntityHeaderTag($this->entity);

		// override default layout with custom module page layout
		$this->data->layout = $this->getObjectThemeLayout($this->data->modulepage);
		$this->data->grid = $this->getGrid($this->data->layout);

		// template vars & override
		$this->data->gridvars = $this->getGridVars($this->entity);
		$this->data->override = $this->getGridOverride($this->entity);

		$viewfile = $this->getFrontViewFile($this->entity);

		return view($viewfile, [
			'data' => $this->data,
		]);

	}

	/**
	 * Process form
	 *
	 * @param Request $request
	 * @return false|Application|Factory|View|string
	 * @throws BindingResolutionException
	 */
	public function process(Request $request)
	{

		$validationRules = $this->getValidationRules($this->entity);
		if(config('app.env') == 'production') {
			$validationRules['g-recaptcha-response'] = [new ReCaptcha];
		}
		$validatedData = $request->validate($validationRules);

		$formfields = array();
		$formfields[] = 'title';
		foreach ($this->entity->getCustomColumns() as $field) {
			// fix empty strings
			if ($field->required == 0) {
				if ($field->fieldtype == 'text' || $field->fieldtype == 'string') {
					$fieldname = $field->fieldname;
					if (empty($request->input($fieldname))) {
						$request->merge([$fieldname => '']);
					}
				}
			}
			// add field to array
			$formfields[] = $field->fieldname;
		}
		if ($request->has('name')) {
			$request->merge(['title' => $request->input('name')]);
		}

		// patch 6.2.23 - start
		if ($request->has('_ipaddress')) {
			$this->checkBlackListColumn($this->entity);
			$formfields[] = 'ipaddress';
			$request->merge(['ipaddress' => $request->input('_ipaddress')]);
		}
		// patch 6.2.23 - end

		$newObject = $this->modelClass::create($request->only($formfields));

		// SEND MAIL
		$this->sendMail($request);

		// show thank you page
		$thankYouPage = Page::where('slug', 'classic-form-thank-you')->first();

		$this->data = $this->showThankyouPage($request, $thankYouPage->id);

		$viewfile = 'content.page.show';

		return view($viewfile, [
			'data' => $this->data,
		]);

	}

	/**
	 * @param Request $request
	 * @param int $pageId
	 * @return mixed
	 * @throws BindingResolutionException
	 */
	private function showThankyouPage(Request $request, int $pageId)
	{

		$data = new stdClass;

		$pageEntity = $this->getFrontEntity('entity.page.show.' . $pageId);

		// get params
		$data->params = $this->getFrontParams($pageEntity, $request);

		// get object
		$data->object = Page::find($pageId);

		if ($data->object) {

			// replace shortcodes
			$data->object->body = $this->replaceShortcodes($data->object->body);

			// seo
			$data->seo = $this->getSeo($data->object);

			// get language versions
			$data->langversions = $this->getFrontLanguageVersions($this->language, $this->entity, $data->object);

			// header tags
			$data->htag = $this->getEntityHeaderTag($this->entity);

			// override default layout with custom page layout
			$data->layout = $this->getObjectThemeLayout($data->object);
			$data->grid = $this->getGrid($this->data->layout);

			$data->gridvars = $this->getGridVars($pageEntity);
			$data->override = $this->getGridOverride($pageEntity);

		}

		return $data;

	}

	/**
	 * @param Request $request
	 * @return bool
	 * @throws BindingResolutionException
	 */
	private function sendMail(Request $request)
	{

		$app = app();
		$maildata = $app->make('stdClass');

		// company
		$company = $this->getSettingsByGroup('company');
		$maildata->company = $company;

		// visitor
		$user = $app->make('stdClass');
		$user->email = $request->input('email');
		if ($request->has('name')) {
			$user->name = $request->input('name');
		}

		// webmaster
		$webmaster = $app->make('stdClass');
		if (config('app.env') == 'production') {
			$webmaster->email = $company->company_email;
			$webmaster->name = $company->company_name;
		} else {
			$webmaster->email = config('lara.admin_company_email');
			$webmaster->name = config('lara.admin_company_name');
		}

		// from
		$maildata->from = $app->make('stdClass');
		$maildata->from->email = $company->company_email;
		$maildata->from->name = $company->company_name;

		// subject
		$maildata->subject = _q('lara-eve::' . $this->entity->resource_slug . '.email.subject');

		// style
		$maildata->style = json_decode(json_encode(config('lara-front.mail')), false);

		// Content
		$intro = $this->getEmailPageContent($this->language, $this->entity->getEntityKey());
		$maildata->content = $app->make('stdClass');
		$maildata->content->title = $intro->title;
		$maildata->content->lead = $intro->lead;
		$maildata->content->body = strip_tags($intro->body);

		// dynamic content
		$maildata->content->data = $app->make('stdClass');
		foreach ($this->entity->getCustomColumns() as $field) {
			$fieldname = $field->fieldname;
			if($field->fieldtype == 'boolean' || $field->fieldtype == 'yesno') {
				if($request->input($fieldname) == 1) {
					$fieldvalue = _q('lara-admin::default.value.yes');
				} else {
					$fieldvalue = _q('lara-admin::default.value.no');
				}
			} else {
				$fieldvalue = $request->input($fieldname);
			}
			$maildata->content->data->$fieldname = [
				'colname' => _q('lara-eve::' . $this->entity->getEntityKey() . '.column.' . $fieldname),
				'colval'  => $fieldvalue,
			];
		}

		// mail to visitor
		$maildata->view = 'email.' . $this->entity->getEntityKey() . '.confirm';
		Mail::to($user)->queue(new MailConfirmation($maildata));

		// mail to webmaster
		$maildata->view = 'email.' . $this->entity->getEntityKey() . '.webmaster';
		$mlr = (config('app.env') == 'production') ? 'smtp' : 'dev';
		Mail::mailer($mlr)->to($webmaster)->queue(new MailConfirmation($maildata));

		return true;

	}

}

