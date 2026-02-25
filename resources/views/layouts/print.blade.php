<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>@yield('title', 'Print')</title>

		<link type="image/x-icon" rel="icon" href="{{ url('favicon.ico') }}">

		<!-- Fonts -->
		<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap"
			rel="stylesheet">
		{{-- CSS --}}
		<link rel="stylesheet" href="{{ asset('dist/css/app.css') }}">
		@stack('css')

	</head>

	<body>
		<div class="container">
			@yield('content')
		</div>
		@stack('scripts')
	</body>

</html>
