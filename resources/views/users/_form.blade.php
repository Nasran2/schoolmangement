                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <x-input-label for="name" :value="__('Full Name')" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name ?? '')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('name')" />
                        </div>

                        <div>
                            <x-input-label for="username" :value="__('Username')" />
                            <x-text-input id="username" name="username" type="text" class="mt-1 block w-full" :value="old('username', $user->username ?? '')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('username')" />
                        </div>

                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email ?? '')" required />
                            <x-input-error class="mt-2" :messages="$errors->get('email')" />
                        </div>

                        <div>
                            <x-input-label for="roles" :value="__('Roles')" />
                            <input type="hidden" name="roles_present" value="1">
                            @php($selectedRoles = session()->hasOldInput() ? old('roles', []) : (isset($user) ? $user->getRoleNames()->all() : []))
                            <div id="roles" class="mt-2 space-y-2 rounded-md border border-gray-300 p-3">
                                @foreach($roles as $role)
                                    <label class="flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $selectedRoles, true)) class="rounded border-gray-300 text-indigo-600">
                                        <span>{{ $role->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Permissions from selected roles are combined. No role means no module access.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('roles')" />
                            <x-input-error class="mt-2" :messages="$errors->get('roles.*')" />
                        </div>

                        <div>
                            <x-input-label for="password" :value="__('Password')" />
                            <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" :required="!isset($user)" autocomplete="new-password" />
                            <p class="mt-1 text-xs text-gray-500">At least 8 characters. Leave blank when editing to keep the current password.</p>
                            <x-input-error class="mt-2" :messages="$errors->get('password')" />
                        </div>

                        <div>
                            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                            <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" :required="!isset($user)" autocomplete="new-password" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <x-input-label for="active" :value="__('Account Status')" />
                            <select id="active" name="active" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="1" @selected((string) old('active', isset($user) ? (int) $user->active : 1) === '1')>Active</option>
                                <option value="0" @selected((string) old('active', isset($user) ? (int) $user->active : 1) === '0')>Inactive</option>
                            </select>
                        </div>

                        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                            <div class="font-semibold text-gray-800">Notes</div>
                            <p class="mt-1">Choose roles to control access to modules and individual dashboard cards. You cannot change your own roles or deactivate your own account.</p>
                        </div>
                    </div>

