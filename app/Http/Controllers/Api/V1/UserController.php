<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->input('search'), fn ($q, $search) => $q->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->paginate($this->perPage($request));

        return $this->ok($users->items(), ['pagination' => [
            'total' => $users->total(),
            'per_page' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
        ]]);
    }

    public function me(Request $request)
    {
        return $this->ok($request->user()->loadCount(['services', 'invoices', 'tickets']));
    }

    public function show(User $user)
    {
        return $this->ok($user);
    }
}
