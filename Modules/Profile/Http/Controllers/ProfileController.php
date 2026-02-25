<?php

namespace Modules\Profile\Http\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
	/**
	 * show the specified resource.
	 * @param int $user_id
	 * @return Response
	 */
	public function show($user_id)
	{
		$data = Profile::select(
			'id',
			'user_id',
			'photo',
			'identity_number',
			'full_name',
			'birth_date',
			'gender',
			'phone_number',
			'address'
		)
			->where('user_id', $user_id)
			->first();

		return response()->json([
			'success' => true,
			'data' => $data
		]);
	}

	/**
	 * Save the specified resource in storage.
	 * @param Request $request
	 * @param int $user_id
	 * @return Response
	 */
	public function save(Request $request, $user_id)
	{
		$request->validate([
			'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
			'identity_number' =>
				'required|string|regex:/^[0-9]+$/|max:50|unique:profiles,identity_number,' . $user_id . ',user_id',
			'full_name' => 'required|string|max:255',
			'birth_date' => 'required|date',
			'gender' => 'required|in:male,female',
			'phone_number' => 'required|string|max:20',
			'address' => 'required|string'
		]);

		$profile = Profile::where('user_id', $user_id)->firstOrFail();
		$data = $request->only([
			'user_id',
			'identity_number',
			'full_name',
			'birth_date',
			'gender',
			'phone_number',
			'address'
		]);

		if ($request->hasFile('photo')) {
			if ($profile && $profile->photo) {
				Storage::disk('public')->delete($profile->photo);
			}

			$file = $request->file('photo');
			$fileName = time() . '_' . $file->getClientOriginalName();

			$data['photo'] = $file->storeAs('img/profile_photos', $fileName, 'public');
		}

		$profile->updateOrCreate(['user_id' => $user_id], $data);

		return response()->json([
			'success' => true,
			'message' => __('Successfully updated')
		]);
	}

	/**
	 * show the specified resource.
	 * @param int $id
	 * @return Response
	 */
	public function showUser($id)
	{
		$user = User::select('id', 'name', 'email', 'is_active')->findOrFail($id);

		$user->role_names = $user->getRoleNames();
		$user->permission_names = $user->getAllPermissions()->pluck('name');

		return response()->json([
			'success' => true,
			'data' => $user
		]);
	}

	/**
	 * Update the specified resource in storage.
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function updateUser(Request $request, $id)
	{
		$request->validate(
			[
				'name' => 'required|string|min:3|max:255|regex:/^[a-z0-9]+$/|unique:users,name,' . $id,
				'email' => 'required|string|email|max:255|unique:users,email,' . $id
			],
			[
				'name.regex' => __('Usernames can only contain lowercase letters and numbers.'),
				'name.unique' => __('Username already in use.')
			]
		);

		$user = User::findOrFail($id);

		$data = $request->only(['name', 'email']);

		$user->update($data);

		return response()->json([
			'success' => true,
			'message' => __('Successfully updated')
		]);
	}

	/**
	 * show the specified resource.
	 * @param int $id
	 * @return Response
	 */
	public function showSecurity($id)
	{
		$user = User::select('id')->findOrFail($id);

		$activeTokens = $user
			->tokens()
			->select('id', 'tokenable_id', 'name', 'ip_address', 'last_used_at', 'created_at')
			->get();

		return response()->json([
			'success' => true,
			'data' => [
				'active_logins' => $activeTokens
			]
		]);
	}

	/**
	 * Update the specified resource in storage.
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function updatePassword(Request $request, $id)
	{
		$request->validate(
			[
				'old_password' => ['required', 'current_password'],
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

		$user = User::findOrFail($id);

		$data = ['passoword' => Hash::make($request->password)];

		$user->update($data);

		return response()->json([
			'success' => true,
			'message' => __('Successfully updated')
		]);
	}

	public function logoutSession($token_id)
	{
		/** @var \App\Models\User $user */
		$user = auth()->user();

		$user->tokens()->where('id', $token_id)->delete();

		return response()->json([
			'success' => true,
			'message' => __('Session closed successfully')
		]);
	}

	/**
	 * Save the specified resource in storage.
	 * @param Request $request
	 * @param int $user_id
	 * @return Response
	 */
	public function saveSettings(Request $request, $user_id)
	{
		$data = $request->all();
		UserSetting::updateOrCreate(['user_id' => $user_id], $data);

		return response()->json([
			'success' => true,
			'message' => __('Successfully updated')
		]);
	}
}
