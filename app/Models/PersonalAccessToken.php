<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

class PersonalAccessToken extends SanctumToken
{
	protected $fillable = ['name', 'ip_address', 'token', 'abilities', 'last_used_at', 'expires_at'];
}
