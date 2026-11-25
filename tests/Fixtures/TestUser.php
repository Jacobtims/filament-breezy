<?php

namespace Jeffgreco13\FilamentBreezy\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Jeffgreco13\FilamentBreezy\Traits\TwoFactorAuthenticatable;

class TestUser extends Authenticatable
{
    use TwoFactorAuthenticatable;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];
}
