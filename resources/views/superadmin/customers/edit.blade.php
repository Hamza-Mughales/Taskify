@extends('layout')

@section('title')
    <?= get_label('edit_customers', 'Edit Customers') ?>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="d-flex justify-content-between mt-4">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb breadcrumb-style1">
                        <li class="breadcrumb-item">
                            <a href="{{ route('superadmin.panel') }}"><?= get_label('home', 'Home') ?></a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('customers.index') }}">{{ get_label('customers', 'Customers') }}</a>
                        </li>
                        <li class="breadcrumb-item active">
                            <?= get_label('edit_customers', 'Edit Customers') ?>
                        </li>
                    </ol>
                </nav>
            </div>
            <div>
                <a href="{{ route('customers.index') }}">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="<?= get_label('customers', 'Customers') ?>">
                        <i class='bx bx-list-ul'></i>
                    </button>
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <form class="form-submit-event" method="POST"
                    action="{{ route('customers.update', ['id' => $customer->id]) }}">
                    @csrf
                    <input type="hidden" name="redirect_url" value="{{ route('customers.index') }}">
                    <h2 class="mb-4">{{ get_label('edit_customer', 'Edit Customer') }}</h2>
                    <div class="row mt-3">
                        <div class="col-lg-6 mb-3">
                            <label for="first_name" class="form-label"><?= get_label('first_name', 'First Name') ?>:</label>
                            <input type="text" class="form-control" id="first_name" name="first_name"
                                value="{{ $customer->first_name }}" required>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label for="last_name" class="form-label"><?= get_label('last_name', 'Last Name') ?>:</label>
                            <input type="text" class="form-control" id="last_name" name="last_name"
                                value="{{ $customer->last_name }}" required>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-lg-6 mb-3">
                            <label for="email" class="form-label"><?= get_label('email', 'Email') ?>:</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="{{ $customer->email }}" required>
                        </div>
                        <div class="mb-3 col-md-6">
                        <label class="form-label"><?= get_label('country_code_and_phone_number', 'Country code and phone number') ?> <span class="asterisk">*</span></label>
                        <div class="input-group">
                            <!-- Country Code Input -->
                            <input type="text" name="country_code" class="form-control country-code-input" placeholder="+1" value="{{ $customer->country_code }}">

                            <!-- Mobile Number Input -->
                            <input type="text" name="phone" class="form-control" placeholder="1234567890" value="{{ $customer->phone }}">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="alert alert-info alert-dismissible" role="alert">
                            {{ get_label('leave_it_blank_if_do_not_want_change_the_password', 'Leave it blank if do not want to change the password') }}
                            !!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close">
                            </button>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label for="password" class="form-label"><?= get_label('password', 'Password') ?>:</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label for="password_confirmation"
                                class="form-label"><?= get_label('confirm_password', 'Confirm Password') ?>:</label>
                            <input type="password" class="form-control" id="password_confirmation"
                                name="password_confirmation">
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label for="status" class="form-label">{{ get_label('status', 'Status') }}</label>
                            <br>

                            <div class="btn-group" role="group">
                                <input type="radio" class="btn-check" name="status" id="active" value="1"
                                    {{ $customer->status == 1 ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary"
                                    for="active">{{ get_label('active', 'Active') }}</label>

                                <input type="radio" class="btn-check" name="status" id="inactive" value="0"
                                    {{ $customer->status == 0 ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary"
                                    for="deactive">{{ get_label('inactive', 'Inactive') }}</label>
                            </div>
                        </div>

                    <button type="submit" id="submitBtn"
                        class="btn btn-primary"><?= get_label('update_customer', 'Update Customer') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/js/pages/customers.js') }}"></script>
@endsection
