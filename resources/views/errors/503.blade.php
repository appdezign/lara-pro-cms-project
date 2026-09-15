<?php

$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
$acceptLang = ['nl', 'en'];
$lang = in_array($lang, $acceptLang) ? $lang : 'en';

$languageStrings = array();
$languageStrings = [
	'nl' => [
		'title'   => 'Onderhoud',
		'message' => 'Deze website wordt momenteel bijgewerkt.',
	],
	'en' => [
		'title'   => 'Maintenance',
		'message' => 'This website is under mainternance.',
	],
];

$languageArray = $languageStrings[$lang];
$content = json_decode(json_encode($languageArray), false);

?>
<html>
<head>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

	<style>
		html, body {
			color: #565973;
			background-color: #fff9f5;
		}
		h1 {
			margin: 0;
			font-size: 1.5rem;
			color: #007E92;
		}
		.container-fluid {
			max-width: 720px !important;
		}
		.card {
			box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
		}
		.card .card-header {
			padding: 1rem !important;
			background-color: #fff;

		}
	</style>
</head>
<body>
<section>
	<div class="container-fluid">
		<div class="row pt-3 pt-md-4 pt-lg-5">
			<div class="col text-center">

				<div class="logo mb-3 mb-md-4 mb-lg-5">
					<img src="/assets/themes/base/images/maintenance.png" alt="Lara CMS" class="" width="120">
				</div>

				<div class="card shadow-sm">
					<div class="card-header">
						<h1>{{ $content->title }}</h1>
					</div>
					<div class="card-body" style="padding: 2rem 1rem">
						<p>{{ $content->message }}</p>
					</div>
				</div>

			</div>
		</div>
	</div>
</section>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>


</body>
</html>