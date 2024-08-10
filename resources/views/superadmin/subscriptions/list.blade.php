@extends('layout')

@section('title')
    <?= get_label('subscriptions', 'Subscriptions') ?>
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

                        <li class="breadcrumb-item active">
                            <?= get_label('subscriptions', 'Subscriptions') ?>
                        </li>

                    </ol>
                </nav>
            </div>

            <div>
                <a href="{{ route('subscriptions.create') }}"><button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title=" <?= get_label('create_subscriptions', 'Create Subscriptions') ?>"><i
                            class="bx bx-plus"></i></button></a>
                <a href="{{ route('subscriptions.index') }}"><button type="button" class="btn btn-sm btn-primary"
                        data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="<?= get_label('subscriptions', 'Subscriptions') ?>"><i
                            class='bx bx-list-ul'></i></button></a>
            </div>
        </div>
        @if (is_countable($subscriptions) && count($subscriptions) > 0)
            <div class="card ">
                <div class="card-header  d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">{{ get_label('subscriptions', 'Subscriptions') }}</h4>
                    <input type="hidden" id="data_type" value="subscriptions">
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">{{ get_label('filter_by_plans' , 'Filter by plans') }}</label>
                            <select class="form-select" name="filter_plans" id="filter_plans">
                                <option value="">{{ get_label("select_plans" , 'Select Plans') }}</option>
                                @foreach ($plans as $plan )
                                    <option value="{{ $plan->id }}">{{ ucfirst($plan->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ get_label('filter_by_status' , 'Filter by status') }}</label>
                            <select class="form-select" id="status">
                                <option value="">{{ get_label('select_status' , 'Select Status') }}</option>
                                <option value="active">{{ get_label('active' , 'Active') }}</option>
                                <option value="inactive">{{ get_label('inactive' , 'Inactive') }}</option>
                                <option value="pending">{{ get_label('pending' , 'Pending') }}</option>
                            </select>
                        </div>
                    </div>
                    <div class="table-responsive text-nowrap">

                        <table id="table" data-toggle="table" data-loading-template="loadingTemplate"
                            data-url="{{ route('subscriptions.list') }}" data-icons-prefix="bx" data-icons="icons"
                            data-show-refresh="true" data-total-field="total" data-trim-on-search="false"
                            data-data-field="rows" data-page-list="[5, 10, 20, 50, 100, 200]" data-search="true"
                            data-side-pagination="server" data-show-columns="true" data-pagination="true"
                            data-sort-name="id" data-sort-order="desc" data-mobile-responsive="true"
                            data-query-params="queryParams">
                            <thead>
                                <tr>
                                    <th data-checkbox="true"></th>
                                    <th data-visible="false" data-sortable="true" data-field="id">
                                        {{ get_label('id', 'ID') }}</th>
                                    <th data-field="user_name">{{ get_label('user_name', 'User Name') }}</th>
                                    <th data-field="plan_name">{{ get_label('plan_name', 'Plan Name') }}</th>
                                    <th data-field="tenure">{{ get_label('tenure', 'Tenure') }}</th>
                                    <th data-field="start_date">{{ get_label('starts_at', 'Start Date') }}</th>
                                    <th data-field="end_date">{{ get_label('end_date', 'End Date') }}</th>
                                    <th data-field="payment_method">{{ get_label('payment_method', 'Payment Method') }}
                                    </th>
                                    <th data-field="features">{{ get_label('features', 'Features') }}</th>
                                    <th data-sortable="true" data-field="charging_price">
                                        {{ get_label('charging_price', 'Charging Price') }}</th>
                                    <th data-visible="false" data-field="charging_currency">
                                        {{ get_label('charging_currency', 'Charging Currency') }}</th>
                                    <th data-field="status">{{ get_label('status', 'Status') }}</th>
                                    <th data-formatter="actionFormatter">{{ get_label('actions', 'Actions') }}</th>
                                </tr>
                            </thead>
                        </table>

                    </div>
                </div>
            </div>
        @else
            <?php
            $type = 'Subscriptions'; ?>
            <x-empty-state-card :type="$type" />
        @endif



    </div>
    @php
        $routePrefix = Route::getCurrentRoute()->getPrefix();
    @endphp

    <script>
        var label_update = '<?= get_label('upgrade ', 'Upgrade ') ?>';
        var label_delete = '<?= get_label('delete ', 'Delete ') ?>';
        var routePrefix = '{{ $routePrefix }}';
    </script>

    <script src="{{ asset('assets/js/pages/subscriptions.js') }}"></script>
@endsection
