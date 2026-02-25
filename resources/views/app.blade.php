<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">

		<title>{{ config('app.name') }}</title>

		<link type="image/x-icon" rel="icon" href="{{ url('favicon.ico') }}">

		<!-- Fonts -->
		<link href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;600;700&display=swap"
			rel="stylesheet">

		<style rel="stylesheet">
			:root {
				--preloader-background-color: #10b981;
			}

			#preloader {
				position: fixed;
				top: 0;
				left: 0;
				width: 100%;
				height: 100%;
				display: flex;
				flex-direction: column;
				justify-content: center;
				align-items: center;
				background-color: var(--preloader-background-color);
				color: #ffffff;
				z-index: 9999;
			}

			.spinner {
				width: 40px;
				height: 40px;
				border: 8px solid #f3f3f3;
				border-top: 8px solid transparent;
				border-radius: 50%;
				animation: spin 1s linear infinite;
				margin-bottom: 10px;
			}

			@keyframes spin {
				0% {
					transform: rotate(0deg);
				}

				100% {
					transform: rotate(360deg);
				}
			}
		</style>
	</head>

	<body>
		<noscript>
			<strong>We're sorry but this app doesn't work properly without JavaScript enabled. Please enable
				it to
				continue.</strong>
		</noscript>
		<div class="isolate" id ="app">
			<div id="preloader">
				<div class="spinner"></div>
				<p>{{ __('Loading application...') }}</p>
			</div>
		</div>
		@vite('resources/js/src/main.js')
		<script>
			window.Laravel = {
				baseUrl: "{{ url('/') }}",
				apiUrl: "{{ url('/api') }}"
			};
			(function() {
				const themeData = JSON.parse(localStorage.getItem('theme'));
				const tailwindColors = {
					'red': '#ef4444',
					'orange': '#f97316',
					'amber': '#f59e0b',
					'yellow': '#eab308',
					'lime': '#84cc16',
					'green': '#10b981',
					'emerald': '#10b981',
					'teal': '#14b8a6',
					'cyan': '#06b6d4',
					'sky': '#0ea5e9',
					'blue': '#3b82f6',
					'indigo': '#6366f1',
					'violet': '#8b5cf6',
					'purple': '#a855f7',
					'fuchsia': '#d946ef',
					'pink': '#ec4899',
					'rose': '#f43f5e',
					'slate': '#64748b',
					'gray': '#6b7280',
					'zinc': '#71717a',
					'neutral': '#737373',
					'stone': '#78716c'
				};
				if (themeData) {
					const primaryHex = tailwindColors[themeData.primary] || '#10b981';
					document.documentElement.style.setProperty('--preloader-background-color', primaryHex);
				}
			})();
		</script>
	</body>

</html>
