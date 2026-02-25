<?php

namespace Modules\Authentication\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends Controller
{
	public function login(Request $request)
	{
		$request->validate([
			'email' => 'required|email',
			'password' => 'required|min:8',
			'remember' => 'nullable|boolean'
		]);

		$user = User::where('email', $request->email)->first();

		if (!$user) {
			throw ValidationException::withMessages([
				'email' => [__('Email address not registered.')]
			]);
		}

		if (!Hash::check($request->password, $user->password)) {
			throw ValidationException::withMessages([
				'password' => [__('The password you entered is incorrect.')]
			]);
		}

		if ($user->is_active == 0) {
			throw ValidationException::withMessages([
				'email' => [__('Your account has been banned. Please contact administrator.')]
			]);
		}

		$user->load('profile');

		$user->load('settings');

		$userAgent = $request->userAgent() ? $request->userAgent() : 'Unknown Device';

		$user->tokens()->where('name', $userAgent)->where('ip_address', $request->ip())->delete();

		$expiration = $request->boolean('remember') ? now()->addDays(30) : now()->addDays(7);

		$token = $user->createToken($userAgent);

		$token->accessToken
			->forceFill([
				'ip_address' => $request->ip(),
				'expires_at' => $expiration
			])
			->save();

		return response()->json([
			'success' => true,
			'message' => __('Login successful.'),
			'user' => $user,
			'roles' => $user->getRoleNames(),
			'permissions' => $user->getAllPermissions()->pluck('name'),
			'token' => [
				'token' => $token->plainTextToken,
				'expires_at' => $expiration->toDateTimeString(),
				'type' => 'Bearer'
			]
		]);
	}

	public function signup(Request $request)
	{
		$request->validate(
			[
				'name' => 'required|string|min:3|max:255|regex:/^[a-z0-9]+$/|unique:users,name',
				'email' => 'required|string|email|max:255|unique:users,email',
				'password' => [
					'required',
					'confirmed',
					'min:8',
					'regex:/[a-z]/',
					'regex:/[A-Z]/',
					'regex:/[0-9]/',
					'regex:/[@$!%*#?&]/'
				]
			],
			[
				'name.regex' => __('Usernames can only contain lowercase letters and numbers.'),
				'name.unique' => __('Username already in use.'),
				'password.regex' => __(
					'Password must contain at least one uppercase letter, one lowercase letter, one number, and one symbol.'
				)
			]
		);

		User::create([
			'name' => $request->name,
			'email' => $request->email,
			'email_verified_at' => now(),
			'password' => Hash::make($request->password),
			'is_active' => 1
		]);

		return response()->json(
			[
				'success' => true,
				'message' => __('Registration successful.')
			],
			201
		);
	}

	public function logout(Request $request)
	{
		$request->user()->currentAccessToken()->delete();

		return response()->json([
			'success' => true,
			'message' => __('Logout successful.')
		]);
	}

	public function user(Request $request)
	{
		$user = $request->user();
		$user->load('profile');
		$user->load('settings');
		$token = $user->currentAccessToken();

		return response()->json([
			'success' => true,
			'message' => __('User data retrieved successfully.'),
			'user' => $user,
			'roles' => $user->getRoleNames(),
			'permissions' => $user->getAllPermissions()->pluck('name'),
			'token' => [
				'token' => $request->bearerToken(),
				'expires_at' => $token->expires_at
					? Carbon::parse($token->expires_at)->toDateTimeString()
					: now()->addDays(7)->toDateTimeString(),
				'type' => 'Bearer'
			]
		]);
	}

	public function forgotPassword(Request $request)
	{
		$request->validate([
			'email' => 'required|email'
		]);

		$status = Password::sendResetLink($request->only('email'));

		return $status === Password::RESET_LINK_SENT
			? response()->json([
				'success' => true,
				'message' => __('A password reset link has been sent to your email.')
			])
			: response()->json(
				[
					'success' => false,
					'message' => __('Gagal mengirim email.')
				],
				502
			);
	}

	public function resetPassword(Request $request)
	{
		$request->validate(
			[
				'token' => 'required',
				'email' => 'required|email',
				'password' => [
					'required',
					'confirmed',
					'min:8',
					'regex:/[a-z]/',
					'regex:/[A-Z]/',
					'regex:/[0-9]/',
					'regex:/[@$!%*#?&]/'
				]
			],
			[
				'password.regex' => __(
					'Password must contain at least one uppercase letter, one lowercase letter, one number, and one symbol.'
				)
			]
		);

		$status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (
			$user,
			$password
		) {
			$user->forceFill(['password' => Hash::make($password)])->setRememberToken(Str::random(60));
			$user->save();
			event(new PasswordReset($user));
		});

		return $status === Password::PASSWORD_RESET
			? response()->json([
				'success' => true,
				'message' => 'Password berhasil diperbarui.'
			])
			: response()->json(
				[
					'success' => false,
					'message' => 'Token tidak valid.'
				],
				400
			);
	}
}
