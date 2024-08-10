@extends('layout')

@section('title')
    <?= get_label('tasks', 'Tasks') ?> - <?= get_label('list_view', 'List view') ?>
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
                        @isset($project->id)
                            <li class="breadcrumb-item">
                                <a href="{{ route('projects.index') }}"><?= get_label('projects', 'Projects') ?></a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="{{ route('projects.info', ['id' => $project->id]) }}">{{ $project->title }}</a>
                            </li>
                        @endisset
                        <li class="breadcrumb-item active"><?= get_label('tasks', 'Tasks') ?></li>
                    </ol>
                </nav>
            </div>
            <div>
                @php
                    $url = isset($project->id)
                        ? route('projects.tasks.draggable', ['id' => $project->id])
                        : route('tasks.draggable');
                    $additionalParams = request()->has('project')
                        ? route('projects.tasks.draggable', ['id' => request()->project])
                        : '';
                    $finalUrl = url($additionalParams ?: $url);
                @endphp


                <a
                    href="{{ url(isset($project->id) ? route('projects.tasks.create', ['id' => $project->id]) : route('tasks.create')) }}"><button
                        type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip" data-bs-placement="left"
                        data-bs-original-title="<?= get_label('create_task', 'Create task') ?>"><i
                            class='bx bx-plus'></i></button></a>
                <a href="{{ $finalUrl }}"><button type="button" class="btn btn-sm btn-primary" data-bs-toggle="tooltip"
                        data-bs-placement="left" data-bs-original-title="<?= get_label('draggable', 'Draggable') ?>"><i
                            class="bx bxs-dashboard"></i></button></a>
            </div>
        </div>
        <?php
        $id = isset($project->id) ? 'project_' . $project->id : '';
        ?>
        <x-tasks-card :tasks="$tasks" :id="$id" :users="$users" :clients="$clients" :projects="$projects"
            :project="$project" />
    </div>
@endsection
