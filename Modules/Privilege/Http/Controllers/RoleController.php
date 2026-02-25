<?php

namespace Modules\Privilege\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
	/**
	 * Display a listing of the resource.
	 * @param Request $request
	 * @return Response
	 */
	public function index(Request $request)
	{
		$search = strtolower($request->search);

		$query = Role::select('id', 'name', 'description', 'guard_name')
			->withCount(['users', 'permissions'])
			->when($search, function ($q, $search) {
				$q->where(function ($inner) use ($search) {
					$inner->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
				});
			})
			->orderBy($request->input('sort_by', 'id'), $request->input('sort_order', 'desc'));

		$perPage = (int) $request->input('per_page', 10);

		$data = $query->paginate($perPage);

		return response()->json($data);
	}

	/**
	 * Store a newly created resource in storage.
	 * @param Request $request
	 * @return Response
	 */
	public function store(Request $request)
	{
		$request->validate([
			'name' => 'required|string|unique:roles,name',
			'guard_name' => 'required'
		]);

		$data = $request->only('name', 'description', 'guard_name');

		Role::create($data);

		return response()->json([
			'success' => true,
			'message' => __('Successfully created')
		]);
	}

	/**
	 * Show the specified resource.
	 * @param int $id
	 * @return Response
	 */
	public function show($id)
	{
		$role = Role::select('id', 'name', 'description', 'guard_name')->findOrFail($id);

		return response()->json([
			'success' => true,
			'data' => $role
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
		$request->validate([
			'name' => 'required|string|unique:roles,name,' . $id,
			'guard_name' => 'required'
		]);

		$role = Role::findOrFail($id);

		$data = $request->only('name', 'description', 'guard_name');

		$role->update($data);

		return response()->json([
			'success' => true,
			'message' => __('Successfully updated')
		]);
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
		$deletedCount = Role::whereIn('id', $ids)->delete();
		return response()->json(
			[
				'status' => 'success',
				'message' => __('Successfully deleted') . " $deletedCount data role.",
				'data' => $ids
			],
			200
		);
	}

	/**
	 * Display a listing of the resource.
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function userData(Request $request, $id)
	{
		$role = Role::select('id', 'name')->findOrFail($id);

		$search = strtolower($request->search);

		$query = User::role($role->name)
			->select('users.id', 'profiles.photo', 'users.name', 'users.email', 'profiles.full_name')
			->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
			->when($search, function ($q, $search) {
				$q->where(function ($inner) use ($search) {
					$inner
						->where('users.is_active', 1)
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

		return response()->json([
			'name' => $role->name,
			'pagination' => $data
		]);
	}

	/**
	 * Display a listing of the resource.
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function userOptionsData(Request $request, $id)
	{
		$role = Role::select('id', 'name')->findOrFail($id);

		$notIn = User::role($role->name)->pluck('users.id')->toArray();

		$search = strtolower($request->q);

		$query = User::select('users.id', 'profiles.photo', 'users.name', 'users.email', 'profiles.full_name')
			->leftJoin('profiles', 'users.id', '=', 'profiles.user_id')
			->where('users.is_active', 1)
			->whereNull('users.deleted_at')
			->whereNotIn('users.id', $notIn)
			->when($search, function ($q, $search) {
				$q->where(function ($inner) use ($search) {
					$inner
						->whereRaw('LOWER(users.name) LIKE ?', ["%{$search}%"])
						->orWhereRaw('LOWER(users.email) LIKE ?', ["%{$search}%"])
						->orWhereRaw('LOWER(profiles.full_name) LIKE ?', ["%{$search}%"]);
				});
			});

		$query->orderBy('users.name', 'asc');

		$user = $query->get();

		$user->transform(function ($item) {
			$item->label = $item->full_name . ' [' . $item->name . '][' . $item->email . ']';
			$item->value = $item->id;
			$item->avatar = [
				'src' => $item->photo
					? asset('storage/' . $item->photo)
					: 'https://ui-avatars.com/api/?name=' . urlencode($item->name),
				'alt' => $item->name
			];
			unset($item->email, $item->full_name, $item->photo);
			return $item;
		});

		return response()->json([
			'success' => true,
			'data' => $user
		]);
	}

	/**
	 * Store a newly created resource in storage.
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function userStore(Request $request, $id)
	{
		$request->validate([
			'user_id' => 'required|exists:users,id'
		]);

		$role = Role::findOrFail($id);
		$user_id = $request->input('user_id');

		$user = User::findOrFail($user_id);

		if ($user->hasRole($role->name)) {
			return response()->json(
				[
					'success' => false,
					'message' => __('User already has this role')
				],
				400
			);
		}

		$user->assignRole($role->name);

		return response()->json([
			'success' => true,
			'message' => __('Successfully added')
		]);
	}

	/**
	 * Remove the specified resource from storage.
	 * @param int $id
	 * @param Request $request
	 * @return Response
	 */
	public function userBulkDelete(Request $request, $id)
	{
		$request->validate([
			'user_ids' => 'required|array|min:1',
			'user_ids.*' => 'exists:users,id'
		]);

		$role = Role::findOrFail($id);
		$user_ids = $request->input('user_ids');

		foreach ($user_ids as $row) {
			$user = User::findOrFail($row);
			if ($user->hasRole($role->name)) {
				$user->removeRole($role->name);
			}
		}

		return response()->json([
			'success' => true,
			'message' => __('Successfully deleted')
		]);
	}

	/**
	 * Display a listing of the resource.
	 * @param Request $request
	 * @return Response
	 */
	public function permissionOptions(Request $request)
	{
		$q = strtolower($request->q);

		$permission = Permission::when($q, function ($q, $search) {
			$q->where(function ($inner) use ($search) {
				$inner->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"]);
			});
		})->pluck('name');

		return response()->json([
			'success' => true,
			'data' => $permission
		]);
	}

	/**
	 * Show the specified resource.
	 * @param int $id
	 * @return Response
	 */
	public function permissionShow($id)
	{
		$role = Role::select('id', 'name')->findOrFail($id);

		$permissions = $role->permissions()->pluck('name');

		return response()->json([
			'success' => true,
			'name' => $role->name,
			'data' => $permissions
		]);
	}

	/**
	 * Update the specified resource in storage.
	 * @param Request $request
	 * @param int $id
	 * @return Response
	 */
	public function permissionUpdate(Request $request, $id)
	{
		$request->validate([
			'permission' => 'required|array|min:1',
			'permission.*' => 'exists:permissions,name'
		]);

		$role = Role::findOrFail($id);
		$permission = $request->input('permission');

		$role->syncPermissions($permission);

		return response()->json([
			'success' => true,
			'message' => __('Successfully updated')
		]);
	}
}
