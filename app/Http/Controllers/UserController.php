<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'employee_id' => 'nullable|string|max:50|unique:users,employee_id',
            'role'        => 'required|in:admin,lead_auditor,inspector,supervisor,contractor',
            'password'    => 'required|string|min:8|confirmed',
        ]);

        $data['password'] = Hash::make($data['password']);

        User::create($data);

        return redirect()->route('users.index')
            ->with('success', "User '{$data['name']}' created.");
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => ['required', 'email', Rule::unique('users','email')->ignore($user->id)],
            'employee_id' => ['nullable', 'string', 'max:50', Rule::unique('users','employee_id')->ignore($user->id)],
            'role'        => 'required|in:admin,lead_auditor,inspector,supervisor,contractor',
            'password'    => 'nullable|string|min:8|confirmed',
        ]);

        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')
            ->with('success', "User '{$user->name}' updated.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "User '{$name}' deleted.");
    }
}
