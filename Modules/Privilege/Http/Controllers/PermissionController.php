<?php

namespace Modules\Privilege\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
	/**
	 * Display a listing of the resource.
	 * @param Request $request
	 * @return Response
	 */
	public function index(Request $request)
	{
		$search = strtolower($request->search);

		$query = Permission::select('id', 'name', 'description', 'guard_name')
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
		$request->validate(
			[
				'module' => 'required',
				'name' => 'required|regex:/^[a-z.]+$/',
				'guard_name' => 'required'
			],
			[
				'name.regex' => __('Names can only contain lowercase letters and dots.')
			]
		);

		$module = $request->module;
		$permissionName = $request->name;
		$fullName = "{$module}.{$permissionName}";

		$data = $request->only('description', 'guard_name');
		$data['name'] = $fullName;

		$exists = Permission::where('name', $fullName)->exists();

		if ($exists) {
			return response()->json(
				[
					'success' => false,
					'message' => __('Permission with this name already exists.')
				],
				422
			);
		}

		$permission = Permission::create($data);

		$systemAdministrator = Role::where('name', 'System Administrator')->first();

		if ($systemAdministrator) {
			$systemAdministrator->givePermissionTo($permission);
		}

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
		$permission = Permission::select('id', 'name', 'description', 'guard_name')->findOrFail($id);

		$module = Str::before($permission->name, '.');
		$nameOnly = Str::after($permission->name, '.');

		$data = [
			'id' => $permission->id,
			'module' => $module,
			'name' => $nameOnly,
			'description' => $permission->description,
			'guard_name' => $permission->guard_name,
			'full_name' => $permission->name
		];

		return response()->json([
			'success' => true,
			'data' => $data
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
				'module' => 'required',
				'name' => 'required|string|regex:/^[a-z.]+$/',
				'guard_name' => 'required'
			],
			[
				'name.regex' => __('Names can only contain lowercase letters and dots.')
			]
		);

		$permission = Permission::findOrFail($id);

		$module = $request->module;
		$name = $request->name;
		$fullName = "{$module}.{$name}";

		$data = $request->only('description', 'guard_name');
		$data['name'] = $fullName;

		$exists = Permission::where('name', $fullName)->where('id', '!=', $id)->exists();

		if ($exists) {
			return response()->json(
				[
					'success' => false,
					'message' => __('Permission with this name already exists.')
				],
				422
			);
		}

		$permission->update($data);

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
		$deletedCount = Permission::whereIn('id', $ids)->delete();
		return response()->json(
			[
				'status' => 'success',
				'message' => __('Successfully deleted') . " $deletedCount data permission.",
				'data' => $ids
			],
			200
		);
	}
}
