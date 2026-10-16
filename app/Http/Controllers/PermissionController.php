<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $this->authorize('manageUsers', User::class);

        return view('permissions.index', [
            'roles' => User::ROLES,
        ]);
    }
}
