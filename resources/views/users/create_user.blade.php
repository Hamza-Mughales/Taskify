@extends('layout')
@section('title')
    <?= get_label('create_user', 'Create user') ?>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('home.index') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('users.index') }}"><?= get_label('users', 'Users') ?></a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('create', 'Create') ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <form action="{{ route('users.store') }}" method="POST" class="form-submit-event"
                    enctype="multipart/form-data">
                    <input type="hidden" name="redirect_url" value="{{ route('users.index') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstName" class="form-label"><?= get_label('first_name', 'First name') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="first_name" name="first_name"
                                placeholder="Enter First name" value="{{ old('first_name') }}">
                            @error('first_name')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastName" class="form-label"><?= get_label('last_name', 'Last name') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="text" name="last_name" id="last_name"
                                placeholder="Enter Last name" value="{{ old('last_name') }}">
                            @error('last_name')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label"><?= get_label('email', 'E-mail') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="email" name="email"
                                placeholder="Enter E-mail" value="{{ old('email') }}">
                            @error('email')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label
                                class="form-label"><?= get_label('country_code_and_phone_number', 'Country code and phone number') ?>
                                <span class="asterisk">*</span></label>
                            <div class="input-group">
                                <!-- Country Code Input -->
                                <input type="text" name="country_code" class="form-control country-code-input"
                                    placeholder="+1" value="{{ old('country_code') }}">

                                <!-- Mobile Number Input -->
                                <input type="text" name="phone" class="form-control" placeholder="1234567890"
                                    value="{{ old('phone') }}">
                            </div>
                        </div>
                        @error('phone')
                            <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                        @enderror

                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label"><?= get_label('password', 'Password') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="password" id="password" name="password"
                                placeholder="Enter Password">
                            @error('password')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirmation"
                                class="form-label"><?= get_label('confirm_password', 'Confirm password') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="password" id="password_confirmation"
                                name="password_confirmation" placeholder="Re enter Password">
                            @error('password_confirmation')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dob" class="form-label"><?= get_label('date_of_birth', 'Date of birth') ?>
                                <span class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="dob" name="dob"
                                placeholder="<?= get_label('please_select', 'Please select') ?>" autocomplete="off">
                            @error('dob')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="doj" class="form-label"><?= get_label('date_of_join', 'Date of joining') ?>
                                <span class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="doj" name="doj"
                                placeholder="<?= get_label('please_select', 'Please select') ?>" autocomplete="off">
                            @error('doj')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label" for="role"><?= get_label('role', 'Role') ?> <span
                                    class="asterisk">*</span></label>
                            <select class="form-select text-capitalize" id="role" name="role">
                                <option value=""><?= get_label('please_select', 'Please select') ?></option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}" {{ old('role') == $role->id ? 'selected' : '' }}>
                                        {{ $role->name }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="address" class="form-label"><?= get_label('address', 'Address') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="address" name="address"
                                placeholder="Enter Address" value="{{ old('address') }}">
                            @error('address')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="city" class="form-label"><?= get_label('city', 'City') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="city" name="city"
                                placeholder="Enter City" value="{{ old('city') }}">
                            @error('city')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="state" class="form-label"><?= get_label('state', 'State') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="state" name="state"
                                placeholder="Enter State" value="{{ old('state') }}">
                            @error('state')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="country" class="form-label"><?= get_label('country', 'Country') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="country" name="country"
                                placeholder="Enter Country" value="{{ old('country') }}">
                            @error('country')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="zip" class="form-label"><?= get_label('zip_code', 'Zip code') ?> <span
                                    class="asterisk">*</span></label>
                            <input class="form-control" type="text" id="zip" name="zip"
                                placeholder="Enter Zip Code" value="{{ old('zip') }}">
                            @error('zip')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="photo"
                                class="form-label"><?= get_label('profile_picture', 'Profile picture') ?></label>
                            <input class="form-control" type="file" id="photo" name="profile">
                            @error('profile')
                                <p class="text-danger mt-1 text-xs">{{ $message }}</p>
                            @enderror
                            <p class="text-muted mt-2"><?= get_label('allowed_jpg_png', 'Allowed JPG or PNG.') ?></p>
                        </div>
                        @if (isAdminOrHasAllDataAccess())
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for=""><?= get_label('status', 'Status') ?> (<small
                                        class="text-muted mt-2">If the Active option is selected, email verification won't
                                        be required.</small>)</label>
                                <div class="">
                                    <div class="btn-group btn-group d-flex justify-content-center" role="group"
                                        aria-label="Basic radio toggle button group">
                                        <input type="radio" class="btn-check" id="user_active" name="status"
                                            value="1">
                                        <label class="btn btn-outline-primary"
                                            for="user_active"><?= get_label('active', 'Active') ?></label>
                                        <input type="radio" class="btn-check" id="user_deactive" name="status"
                                            value="0" checked>
                                        <label class="btn btn-outline-primary"
                                            for="user_deactive"><?= get_label('deactive', 'Deactive') ?></label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="">
                                    <?= get_label('require_email_verification', 'Require email verification?') ?>
                                    <i class='bx bx-info-circle text-primary' data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        title="If 'Yes' is selected, user will receive a verification link via email. Please ensure that email settings are configured and operational."></i>
                                </label>
                                <div class="">
                                    <div class="btn-group btn-group d-flex justify-content-center" role="group"
                                        aria-label="Basic radio toggle button group">
                                        <input type="radio" class="btn-check" id="require_ev_yes" name="require_ev"
                                            value="1" checked>
                                        <label class="btn btn-outline-primary"
                                            for="require_ev_yes"><?= get_label('yes', 'Yes') ?></label>
                                        <input type="radio" class="btn-check" id="require_ev_no" name="require_ev"
                                            value="0">
                                        <label class="btn btn-outline-primary"
                                            for="require_ev_no"><?= get_label('no', 'No') ?></label>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="mt-2">
                            <button type="submit" class="btn btn-primary me-2"
                                id="submit_btn"><?= get_label('create', 'Create') ?></button>
                            <button type="reset"
                                class="btn btn-outline-secondary"><?= get_label('cancel', 'Cancel') ?></button>
                        </div>
                    </div>
            </div>
            </form>
        </div>

    </div>
@endsection
