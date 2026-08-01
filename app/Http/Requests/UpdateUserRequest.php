<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'password' => ['sometimes', 'nullable', 'confirmed', Password::defaults()],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->rejectPrivilegeEscalation($validator);
        });
    }

    /**
     * Managers hold users.update, which without this check would let them grant
     * themselves — or anyone else — the admin role and bypass every gate. Only
     * an existing administrator may hand out or take away that role.
     */
    private function rejectPrivilegeEscalation(Validator $validator): void
    {
        if (! $this->has('roles') || $this->user()->isAdmin()) {
            return;
        }

        /** @var User $target */
        $target = $this->route('user');

        $wantsAdmin = in_array(Role::ADMIN, $this->input('roles', []), true);
        $isAdmin = $target->hasRole(Role::ADMIN);

        if ($wantsAdmin !== $isAdmin) {
            $validator->errors()->add('roles', __('Only an administrator may change the administrator role.'));
        }
    }
}
