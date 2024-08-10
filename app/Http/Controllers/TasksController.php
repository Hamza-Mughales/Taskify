<?php

namespace App\Http\Controllers;

use PDO;
use Exception;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use App\Models\Client;
use App\Models\Status;

use App\Models\Project;
use App\Models\Workspace;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Services\DeletionService;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Request as FacadesRequest;

class TasksController extends Controller
{
    protected $workspace;
    protected $user;
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            // fetch session and use it in entire class with constructor
            $this->workspace = Workspace::find(session()->get('workspace_id'));
            $this->user = getAuthenticatedUser();
            return $next($request);
        });
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id = '')
    {
        $project = (object)[];
        if ($id) {
            $project = Project::findOrFail($id);
            $tasks = $project->tasks;
        } else {
            $tasks = isAdminOrHasAllDataAccess() ? $this->workspace->tasks : $this->user->tasks();
        }
        $tasks = $tasks->count();
        $users = $this->workspace->users;
        $clients = $this->workspace->clients;
        $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects : $this->user->projects;
        return view('tasks.tasks', ['project' => $project, 'tasks' => $tasks, 'users' => $users, 'clients' => $clients, 'projects' => $projects]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id = '')
    {
        $project = (object)[];
        $projects = [];
        if ($id) {
            $project = Project::find($id);
            $users = $project->users;
        } else {
            $projects = isAdminOrHasAllDataAccess() ? $this->workspace->projects : $this->user->projects;
            $users = $this->workspace->users;
        }
        $statuses = Status::where('admin_id', getAdminIdByUserRole())
            ->orWhereNull('admin_id')
            ->get();
        return view('tasks.create_task', ['project' => $project, 'projects' => $projects, 'users' => $users, 'statuses' => $statuses]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $adminId = getAdminIdByUserRole();
        $formFields = $request->validate([
            'title' => ['required'],
            'status_id' => ['required'],
            'start_date' => ['required', 'before_or_equal:due_date'],
            'due_date' => ['required'],
            'description' => ['required'],
            'project' => ['required']
        ]);
        $project_id = $request->input('project');

        $start_date = $request->input('start_date');
        $due_date = $request->input('due_date');
        $formFields['start_date'] = format_date($start_date, null, "Y-m-d");
        $formFields['due_date'] = format_date($due_date, null, "Y-m-d");

        $formFields['workspace_id'] = $this->workspace->id;
        $formFields['created_by'] = $this->user->id;
        $formFields['admin_id'] = $adminId;
        $formFields['project_id'] = $project_id;
        $userIds = $request->input('user_id');

        $new_task = Task::create($formFields);
        $task_id = $new_task->id;
        $task = Task::find($task_id);
        $task->users()->attach($userIds, ['admin_id' => $adminId]);
        $notification_data = [
            'type' => 'task',
            'type_id' => $task_id,
            'type_title' => $task->title,
            'access_url' => 'tasks/information/' . $task->id,
            'action' => 'assigned',
            'title' => 'New task assigned',
            'message' => $this->user->first_name . ' ' . $this->user->last_name . ' assigned you new task : ' . $task->title . ', ID #' . $task_id . '.'
        ];
        $recipients = array_map(function ($userId) {
            return 'u_' . $userId;
        }, $userIds);
        processNotifications($notification_data, $recipients);
        Session::flash('message', 'Task created successfully.');
        return response()->json(['error' => false, 'id' => $new_task->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $task = Task::findOrFail($id);
        return view('tasks.task_information', ['task' => $task]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $task = Task::findOrFail($id);
        $project = $task->project;
        $users = $task->project->users;
        $task_users = $task->users;
        $statuses = Status::where("admin_id", getAdminIdByUserRole())->orWhereNull('admin_id')->get();
        $tags = Tag::where('admin_id', getAdminIdByUserRole());
        return view('tasks.update_task', ["project" => $project, "task" => $task, "users" => $users, "task_users" => $task_users, 'statuses' => $statuses, 'tags' => $tags]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $formFields = $request->validate([
            'title' => ['required'],
            'status_id' => ['required'],
            'start_date' => ['required', 'before_or_equal:due_date'],
            'due_date' => ['required'],
            'description' => ['required'],
        ]);

        $start_date = $request->input('start_date');
        $due_date = $request->input('due_date');
        $formFields['start_date'] = format_date($start_date, null, "Y-m-d");
        $formFields['due_date'] = format_date($due_date, null, "Y-m-d");

        $userIds = $request->input('user_id');

        $task = Task::findOrFail($id);
        $task->update($formFields);
        $currentUsers = $task->users->pluck('id')->toArray();
        $task->users()->sync($userIds);
        $newUsers = array_diff($userIds, $currentUsers);
        // Prepare notification data for new users
        $notification_data = [
            'type' => 'task',
            'type_id' => $id,
            'type_title' => $task->title,
            'access_url' => 'tasks/information/' . $task->id,
            'action' => 'assigned',
            'title' => 'Task updated',
            'message' => $this->user->first_name . ' ' . $this->user->last_name . ' assigned you new task : ' . $task->title . ', ID #' . $id . '.'
        ];

        // Notify only the new users
        $recipients = array_map(function ($userId) {
            return 'u_' . $userId;
        }, $newUsers);

        // Process notifications for new users
        processNotifications($notification_data, $recipients);
        Session::flash('message', 'Task updated successfully.');
        return response()->json(['error' => false, 'id' => $id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $task = Task::find($id);
        DeletionService::delete(Task::class, $id, 'Task');
        return response()->json(['error' => false, 'message' => 'Task deleted successfully.', 'id' => $id, 'title' => $task->title, 'parent_id' => $task->project_id, 'parent_type' => 'project']);
    }

    public function destroy_multiple(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:tasks,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);

        $ids = $validatedData['ids'];
        $deletedTasks = [];
        $deletedTaskTitles = [];
        $parentIds = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $task = Task::find($id);
            if ($task) {
                $deletedTaskTitles[] = $task->title;
                DeletionService::delete(Task::class, $id, 'Task');
                $deletedTasks[] = $id;
                $parentIds[] = $task->project_id;
            }
        }

        return response()->json(['error' => false, 'message' => 'Task(s) deleted successfully.', 'id' => $deletedTasks, 'titles' => $deletedTaskTitles, 'parent_id' => $parentIds, 'parent_type' => 'project']);
    }


    public function list($id = '')
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $status = isset($_REQUEST['status']) && $_REQUEST['status'] !== '' ? $_REQUEST['status'] : "";
        $user_id = (request('user_id')) ? request('user_id') : "";
        $client_id = (request('client_id')) ? request('client_id') : "";
        $project_id = (request('project_id')) ? request('project_id') : "";
        $start_date_from = (request('task_start_date_from')) ? request('task_start_date_from') : "";
        $start_date_to = (request('task_start_date_to')) ? request('task_start_date_to') : "";
        $end_date_from = (request('task_end_date_from')) ? request('task_end_date_from') : "";
        $end_date_to = (request('task_end_date_to')) ? request('task_end_date_to') : "";
        $where = [];
        if ($status != '') {
            $where['status_id'] = $status;
        }
        if ($id) {
            $id = explode('_', $id);
            $belongs_to = $id[0];
            $belongs_to_id = $id[1];
            if ($belongs_to == 'project') {
                $belongs_to = Project::find($belongs_to_id);
            }
            if ($belongs_to == 'user') {
                $belongs_to = User::find($belongs_to_id);
            }
            if ($belongs_to == 'client') {
                $belongs_to = Client::find($belongs_to_id);
            }
            $tasks = $belongs_to->tasks();
        } else {
            $tasks = isAdminOrHasAllDataAccess() ? $this->workspace->tasks() : $this->user->tasks();
        }
        if ($user_id) {
            $user = User::find($user_id);
            $tasks = $user->tasks();
        }
        if ($client_id) {
            $client = Client::find($client_id);
            $tasks = $client->tasks();
        }
        if ($project_id) {
            $where['project_id'] = $project_id;
        }
        if ($start_date_from && $start_date_to) {
            $tasks->whereBetween('start_date', [$start_date_from, $start_date_to]);
        }
        if ($end_date_from && $end_date_to) {
            $tasks->whereBetween('due_date', [$end_date_from, $end_date_to]);
        }
        if ($search) {
            $tasks = $tasks->where(function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            });
        }
        $tasks->where($where);
        $totaltasks = $tasks->count();

        $tasks = $tasks->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(
                fn ($task) => [
                    'id' => $task->id,
                    'title' => "<a href='" . route('tasks.info', ['id' => $task->id]) . "' target='_blank' data-bs-toggle='tooltip' data-bs-placement='bottom' title='" . htmlspecialchars($task->description, ENT_QUOTES, 'UTF-8') ."'><strong>" . $task->title . "</strong></a>",
                'project_id' => "<a href='" . route('projects.info', ['id' => $task->project->id]) . "' target='_blank data-bs-toggle='tooltip' data-bs-placement='bottom' title=" . htmlspecialchars($task->project->description, ENT_QUOTES, 'UTF-8') . "><strong>" . $task->project->title . "</strong></a> <a href='javascript:void(0);' class='mx-2'><i class='bx " . ($task->project->is_favorite ? 'bxs' : 'bx') . "-star favorite-icon text-warning' data-favorite=" . $task->project->is_favorite . " data-route-prefix='" . Route::getCurrentRoute()->getPrefix() . "' data-id=" . $task->project->id . " title='" . ($task->project->is_favorite ? get_label('remove_favorite', 'Click to remove from favorite') : get_label('add_favorite', 'Click to mark as favorite')) . "'></i></a>",
                    'users' => $task->users,
                    'clients' => $task->project->clients,
                    'start_date' => format_date($task->start_date),
                    'end_date' => format_date($task->due_date),

                    'status_id' => "<span class='badge bg-label-" . $task->status->color . " me-1'>" . $task->status->title . "</span>",
                ]
            );

        foreach ($tasks->items() as $task => $collection) {
            foreach ($collection['users'] as $i => $user) {
                $collection['users'][$i] = "<a href='" . route('users.show', [$user->id]) . "' target='_blank'><li class='avatar avatar-sm pull-up'  title='" . $user['first_name'] . " " . $user['last_name'] . "'>
                <img src='" . ($user['photo'] ? asset('storage/' . $user['photo']) : asset('storage/photos/no-image.jpg')) . "' class='rounded-circle' />
                </li></a>";
            };
        }

        foreach ($tasks->items() as $task => $collection) {
            foreach ($collection['clients'] as $i => $client) {
                $collection['clients'][$i] = "<a href='" . route('clients.profile', ['id' => $client->id]) . "' target='_blank'><li class='avatar avatar-sm pull-up'  title='" . $client['first_name'] . " " . $client['last_name'] . "'>
                <img src='" . ($client['photo'] ? asset('storage/' . $client['photo']) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle' />
                </li></a>";
            };
        }

        return response()->json([
            "rows" => $tasks->items(),
            "total" => $totaltasks,
        ]);
    }

    public function dragula($id = '')
    {
        $project = (object)[];
        if ($id) {
            $project = Project::findOrFail($id);

            $tasks = isAdminOrHasAllDataAccess() ? $project->tasks : $this->user->project_tasks($id)->get();
        } else {
            $tasks = isAdminOrHasAllDataAccess() ? $this->workspace->tasks : $this->user->tasks()->get();
        }
        $total_tasks = $tasks->count();
        $statuses = Status::where("admin_id", getAdminIdByUserRole())->orWhereNull('admin_id')->get();
        return view('tasks.board_view', ['project' => $project, 'tasks' => $tasks, 'total_tasks' => $total_tasks, 'statuses' => $statuses]);
    }

    public function updateStatus($id, $newStatus)
    {
        $task = Task::findOrFail($id);
        $current_status = $task->status->title;
        $task->status_id = $newStatus;
        if ($task->save()) {
            $task->refresh();
            $new_status = $task->status->title;
            return response()->json(['error' => false, 'message' => 'Task status updated successfully.', 'id' => $id, 'activity_message' => $this->user->first_name . ' ' . $this->user->last_name . ' updated task status from ' . $current_status . ' to ' . $new_status]);
        } else {
            return response()->json(['error' => true, 'message' => 'Task status couldn\'t updated.']);
        }
    }

    public function duplicate($id)
    {
        // Define the related tables for this meeting
        $relatedTables = ['users']; // Include related tables as needed

        // Use the general duplicateRecord function
        $duplicate = duplicateRecord(Task::class, $id, $relatedTables);

        if (!$duplicate) {
            return response()->json(['error' => true, 'message' => 'Task duplication failed.']);
        }
        if (request()->has('reload') && request()->input('reload') === 'true') {
            Session::flash('message', 'Task duplicated successfully.');
        }
        return response()->json(['error' => false, 'message' => 'Task duplicated successfully.', 'id' => $id]);
    }

    public function upload_media(Request $request)
    {

        try {
            $validatedData = $request->validate([
                'id' => 'integer|exists:tasks,id'
            ]);

            $mediaIds = [];

            if ($request->hasFile('media_files')) {
                $task = Task::find($validatedData['id']);
                $mediaFiles = $request->file('media_files');

                foreach ($mediaFiles as $mediaFile) {
                    $mediaItem = $task->addMedia($mediaFile)
                        ->sanitizingFileName(function ($fileName) {
                            // Replace special characters and spaces with hyphens
                            return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                        })
                        ->toMediaCollection('task-media');

                    $mediaIds[] = $mediaItem->id;
                }

                Session::flash('message', 'File(s) uploaded successfully.');
                return response()->json(['error' => false, 'message' => 'File(s) uploaded successfully.', 'id' => $mediaIds, 'type' => 'media']);
            } else {
                Session::flash('error', 'No file(s) chosen.');
                return response()->json(['error' => true, 'message' => 'No file(s) chosen.']);
            }
        } catch (Exception $e) {
            // Handle the exception as needed
            Session::flash('error', 'An error occurred during file upload: ' . $e->getMessage());
            return response()->json(['error' => true, 'message' => 'An error occurred during file upload: ' . $e->getMessage()]);
        }
    }


    public function get_media($id)
    {
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $task = Task::findOrFail($id);
        $media = $task->getMedia('task-media');

        if ($search) {
            $media = $media->filter(function ($mediaItem) use ($search) {
                return (
                    // Check if ID contains the search query
                    stripos($mediaItem->id, $search) !== false ||
                    // Check if file name contains the search query
                    stripos($mediaItem->file_name, $search) !== false ||
                    // Check if date created contains the search query
                    stripos($mediaItem->created_at->format('Y-m-d'), $search) !== false
                );
            });
        }


        $formattedMedia = $media->map(function ($mediaItem) {
            // Check if the disk is public
            $isPublicDisk = $mediaItem->disk == 'public' ? 1 : 0;

            // Generate file URL based on disk visibility
            $fileUrl = $isPublicDisk
                ? asset('storage/task-media/' . $mediaItem->file_name)
                : $mediaItem->getFullUrl();
            return [
                'id' => $mediaItem->id,
                'file' => '<a href="' . $fileUrl . '" data-lightbox="task-media"> <img src="' . $fileUrl . '" alt="' . $mediaItem->file_name . '" width="50"></a>',
                'file_name' => $mediaItem->file_name,
                'file_size' => formatSize($mediaItem->size),
                'created_at' => format_date($mediaItem->created_at),
                'updated_at' => format_date($mediaItem->updated_at),
                'actions' => [
                    '<a href="' . $fileUrl . '" title="' . get_label('download', 'Download') . '" download>' .
                        '<i class="bx bx-download bx-sm"></i>' .
                        '</a>' .
                        '<button title="' . get_label('delete', 'Delete') . '" type="button" class="btn delete" data-id="' . $mediaItem->id . '" data-type="task-media" data-table = "task_media_table" >' .
                        '<i class="bx bx-trash text-danger"></i>' .
                        '</button>'
                ],


            ];
        });

        if ($order == 'asc') {
            $formattedMedia = $formattedMedia->sortBy($sort);
        } else {
            $formattedMedia = $formattedMedia->sortByDesc($sort);
        }

        return response()->json([
            'rows' => $formattedMedia->values()->toArray(),
            'total' => $formattedMedia->count(),
        ]);
    }

    public function delete_media($mediaId)
    {
        $mediaItem = Media::find($mediaId);

        if (!$mediaItem) {
            // Handle case where media item is not found
            return response()->json(['error' => true, 'message' => 'File not found.']);
        }

        // Delete media item from the database and disk
        $mediaItem->delete();

        return response()->json(['error' => false, 'message' => 'File deleted successfully.', 'id' => $mediaId, 'title' => $mediaItem->file_name, 'parent_id' => $mediaItem->model_id,  'type' => 'media', 'parent_type' => 'task']);
    }

    public function delete_multiple_media(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:media,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);

        $ids = $validatedData['ids'];
        $deletedIds = [];
        $deletedTitles = [];
        $parentIds = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $media = Media::find($id);
            if ($media) {
                $deletedIds[] = $id;
                $deletedTitles[] = $media->file_name;
                $parentIds[] = $media->model_id;
                $media->delete();
            }
        }

        return response()->json(['error' => false, 'message' => 'Files(s) deleted successfully.', 'id' => $deletedIds, 'titles' => $deletedTitles, 'parent_id' => $parentIds, 'type' => 'media', 'parent_type' => 'task']);
    }
}
