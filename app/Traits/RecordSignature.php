<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

trait RecordSignature
{
	/**
	 * Boot the trait to listen for Eloquent model events.
	 */
	protected static function bootRecordSignature()
	{
		// Automatically set created_by when a new record is created
		static::creating(function ($model) {
			if (Auth::check()) {
				$model->created_by = Auth::id();
			}
		});

		// Automatically set updated_by when a record is updated
		static::updating(function ($model) {
			if (Auth::check()) {
				$model->updated_by = Auth::id();
			}
		});

		// Automatically set deleted_by when a record is soft-deleted
		if (method_exists(static::class, 'bootSoftDeletes')) {
			static::deleting(function ($model) {
				if (Auth::check() && !$model->isForceDeleting()) {
					$model->deleted_by = Auth::id();
					$model->save();
				}
			});
		}
	}

	/**
	 * Relationship: Get the user who created the record.
	 */
	public function creator()
	{
		return $this->belongsTo(User::class, 'created_by')->withTrashed();
	}

	/**
	 * Relationship: Get the user who updated the record.
	 */
	public function editor()
	{
		return $this->belongsTo(User::class, 'updated_by')->withTrashed();
	}

	/**
	 * Relationship: Get the user who deleted the record.
	 */
	public function destroyer()
	{
		return $this->belongsTo(User::class, 'deleted_by')->withTrashed();
	}
}
