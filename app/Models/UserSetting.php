<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserSetting extends Model
{
	use HasFactory, SoftDeletes, RecordSignature;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_id',
		'ui_primary',
		'ui_neutral',
		'ui_dark_mode',
		'language',
		'created_by',
		'updated_by',
		'deleted_by'
	];

	/**
	 * Relationship: Get the user that owns the profile.
	 */
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}
}
