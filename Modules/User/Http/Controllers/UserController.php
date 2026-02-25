<?php

namespace Modules\User\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
	/**
	 * Display a listing of the resource.
	 * @param Request $request
	 * @return Response
	 */
	public function index(Request $request)
	{
		$search = strtolower($request->search);

		$query = User::with('roles:name')
			->select('users.id', 'profiles.photo', 'users.name', 'users.email', 'profiles.full_name', 'users.is_active')
			->whereNull('users.deleted_at')
			->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
			->when($search, function ($q, $search) {
				$q->where(function ($inner) use ($search) {
					$inner
						->whereRaw('LOWER(users.name) LIKE ?', ["%{$search}%"])
						->orWhereRaw('LOWER(users.email) LIKE ?', ["%{$search}%"])
						->orWhereRaw('LOWER(profiles.full_name) LIKE ?', ["%{$search}%"]);
				});
			})
			->when($request->filled('status'), function ($q) {
				$q->where('users.is_active', request('status'));
			});

		$sortBy = $request->input('sort_by', 'id');
		$sortOrder = $request->input('sort_order', 'desc');

		if ($sortBy === 'full_name') {
			$query->orderBy('profiles.full_name', $sortOrder);
		} else {
			$query->orderBy('users.' . $sortBy, $sortOrder);
		}

		$perPage = (int) $request->input('per_page', 10);

		$data = $query->paginate($perPage);

		$data->getCollection()->transform(function ($item) {
			if ($item->photo) {
				$item->photo_url = asset('storage/' . $item->photo);
			} else {
				$item->photo_url = 'https://ui-avatars.com/api/?name=' . urlencode($item->name);
			}
			return $item;
		});

		return response()->json($data);
	}

	/**
	 * Show the specified resource.
	 * @param int $id
	 * @return Response
	 */
	public function show($id)
	{
		$user = User::with([
			'profile' => function ($query) {
				$query->select(
					'user_id',
					'photo',
					'identity_number',
					'full_name',
					'birth_date',
					'gender',
					'phone_number',
					'address'
				);
			}
		])
			->select('id', 'name', 'email', 'is_active')
			->findOrFail($id);
		$activeTokens = $user
			->tokens()
			->select('id', 'tokenable_id', 'name', 'ip_address', 'last_used_at', 'created_at')
			->get();
		$user->role_names = $user->getRoleNames();
		$user->permission_names = $user->getAllPermissions()->pluck('name');

		return response()->json([
			'success' => true,
			'data' => [
				'user' => $user,
				'active_logins' => $activeTokens
			]
		]);
	}

	/**
	 * Display a listing of the resource.
	 * @param Request $request
	 * @return Response
	 */
	public function roleOptionsData(Request $request)
	{
		$search = strtolower($request->q);

		$roles = Role::select('name')
			->when($search, function ($q, $search) {
				$q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
			})
			->get()
			->pluck('name');

		return response()->json([
			'success' => true,
			'data' => $roles
		]);
	}

	/**
	 * Update the specified resource in storage.
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function update(Request $request, $id)
	{
		$request->validate(
			[
				'photo' => 'nullable|image|mimes:jpg,jpeg,png,gif|max:2048',
				'name' => 'required|string|min:3|max:255|regex:/^[a-z0-9]+$/|unique:users,name,' . $id,
				'email' => 'required|string|email|max:255|unique:users,email,' . $id,
				'is_active' => 'required|in:0,1',
				'identity_number' =>
					'required|string|regex:/^[0-9]+$/|max:50|unique:profiles,identity_number,' . $id . ',user_id',
				'full_name' => 'required|string|max:255',
				'birth_date' => 'required|date',
				'gender' => 'required|in:male,female',
				'phone_number' => 'required|string|max:20',
				'address' => 'required|string',
				'roles' => 'nullable|array'
			],
			[
				'name.regex' => __('Usernames can only contain lowercase letters and numbers.'),
				'name.unique' => __('Username already in use.'),
				'identity_number.regex' => __('The identity number may only contain numbers (0-9).'),
				'identity_number.unique' => __('The identity number is already in use.')
			]
		);

		$user = User::findOrFail($id);
		$profile = $user->profile;

		DB::beginTransaction();

		try {
			$dataUser = $request->only(['name', 'email', 'is_active']);
			$dataProfile = $request->only([
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

				$dataProfile['photo'] = $file->storeAs('img/profile_photos', $fileName, 'public');
			}

			$user->update($dataUser);

			$user->profile()->updateOrCreate(['user_id' => $user->id], $dataProfile);

			if ($request->has('roles')) {
				$user->syncRoles($request->roles);
			}

			DB::commit();

			return response()->json([
				'success' => true,
				'message' => __('Successfully updated')
			]);
		} catch (\Exception $e) {
			DB::rollback();

			return response()->json(
				[
					'success' => false,
					'message' => __('Something went wrong: ') . $e->getMessage()
				],
				500
			);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 * @param int $id
	 * @return Response
	 */
	public function bulkDelete(Request $request)
	{
		$request->validate([
			'ids' => 'required|array|min:1',
			'ids.*' => 'exists:users,id'
		]);

		$ids = $request->input('ids');
		$deletedCount = User::whereIn('id', $ids)->delete();
		return response()->json(
			[
				'status' => 'success',
				'message' => __('Successfully deleted') . ` $deletedCount data user.`,
				'data' => $ids
			],
			200
		);
	}
}
