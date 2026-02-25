<?php

namespace App\Models;

use App\Traits\RecordSignature;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
	use HasFactory, SoftDeletes, RecordSignature;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array<int, string>
	 */
	protected $fillable = [
		'user_id',
		'photo',
		'identity_number',
		'full_name',
		'birth_date',
		'gender',
		'phone_number',
		'address',
		'created_by',
		'updated_by',
		'deleted_by'
	];

	/**
	 * The attributes that should be cast.
	 *
	 * @var array<string, string>
	 */
	protected $casts = [
		'birth_date' => 'date'
	];

	/**
	 * Relationship: Get the user that owns the profile.
	 */
	public function user()
	{
		return $this->belongsTo(User::class, 'user_id');
	}

	protected $appends = ['photo_url'];

	public function getPhotoUrlAttribute()
	{
		if ($this->photo) {
			return asset('storage/' . $this->photo);
		}
		return 'https://ui-avatars.com/api/?name=' . urlencode($this->full_name);
	}
}
