<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckTokenExpiration
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
	 * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
	 */
	public function handle(Request $request, Closure $next)
	{
		$user = $request->user();

		if ($user && $user->currentAccessToken()) {
			$expiration = $user->currentAccessToken()->expires_at;
			if ($expiration && now()->greaterThan($expiration)) {
				$user->currentAccessToken()->delete();
				return response()->json(['message' => __('Token Expired.')], 401);
			}
		}
		return $next($request);
	}
}
