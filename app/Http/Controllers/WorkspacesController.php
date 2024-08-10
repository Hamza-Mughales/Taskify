<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Workspace;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use App\Services\DeletionService;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class WorkspacesController extends Controller
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
    public function index()
    {
        $workspaces = Workspace::all();
        $adminId = getAdminIdByUserRole();
        $admin = Admin::with('user', 'teamMembers.user')->find($adminId);

        $users = $admin->teamMembers;
        $clients = Client::where('admin_id', $adminId)->get();
        return view('workspaces.workspaces', compact('workspaces', 'users', 'clients', 'admin'));
    }
    public function create()
    {
        $adminId = getAdminIdByUserRole();
        $admin = Admin::with('user', 'teamMembers.user')->find($adminId);

        $users = User::all();
        $clients = Client::where('admin_id', $adminId)->get();
        $auth_user = $this->user;

        return view('workspaces.create_workspace', compact('users', 'clients', 'auth_user', 'admin'));
    }
    public function store(Request $request)
    {

        $adminId = null;
        if (Auth::guard('web')->check() && $this->user->hasRole('admin')) {
            $admin = Admin::where('user_id', $this->user->id)->first();
            if ($admin) {
                $adminId = $admin->id;
            }
        }

        $formFields = $request->validate([
            'title' => ['required']
        ]);

        $formFields['user_id'] = $this->user->id;
        $formFields['admin_id'] = $adminId;
        $userIds = $request->input('user_ids') ?? [];
        $clientIds = $request->input('client_ids') ?? [];

        // Set creator as a participant automatically

        if (Auth::guard('client')->check() && !in_array($this->user->id, $clientIds)) {
            array_splice($clientIds, 0, 0, $this->user->id);
        } else if (Auth::guard('web')->check() && !in_array($this->user->id, $userIds)) {
            array_splice($userIds, 0, 0, $this->user->id);
        }

        // Create new workspace

        $new_workspace = Workspace::create($formFields);

        $workspace_id = $new_workspace->id;
        if ($this->workspace == null) {
            session()->put('workspace_id', $workspace_id);
        }
        // Attach users and clients to the workspace
        $workspace = Workspace::find($workspace_id);
        $workspace->users()->attach($userIds, ['admin_id' => $adminId]);
        $workspace->clients()->attach($clientIds, ['admin_id' => $adminId]);

        //Create activity log
        $activityLogData = [
            'workspace_id' => $workspace_id,
            'admin_id' => $adminId,
            'actor_id' => $this->user->id,
            'actor_type' => 'user',
            'type_id' => $workspace_id,
            'type' => 'workspace',
            'activity' => 'created',
            'message' => $this->user->name . ' created workspace ' . $new_workspace->title,
        ];

        ActivityLog::create($activityLogData);
        $notification_data = [
            'type' => 'workspace',
            'type_id' => $workspace_id,
            'type_title' => $workspace->title,
            'action' => 'assigned',
            'title' => 'Added in a workspace',
            'message' => $this->user->first_name . ' ' . $this->user->last_name . ' added you in workspace: ' . $workspace->title . ', ID #' . $workspace_id . '.'

        ];

        // Combine user and client IDs for notification recipients
        $recipients = array_merge(
            array_map(function ($userId) {
                return 'u_' . $userId;
            }, $userIds),
            array_map(function ($clientId) {
                return 'c_' . $clientId;
            }, $clientIds)
        );

        // Process notifications
        processNotifications($notification_data, $recipients);
        Session::flash('message', 'Workspace created successfully.');
        return response()->json(['error' => false]);
    }

    public function list()
    {
        $search = request('search');
        $sort = request('sort', 'id');
        $order = request('order', 'DESC');
        $user_id = request('user_id');
        $client_id = request('client_id');

        $workspaces = isAdminOrHasAllDataAccess() ? Workspace::query() : $this->user->workspaces();

        if ($user_id) {
            $workspaces->whereHas('users', function ($query) use ($user_id) {
                $query->where('users.id', $user_id);
            });
        }
        if ($client_id) {
            $workspaces->whereHas('clients', function ($query) use ($client_id) {
                $query->where('clients.id', $client_id);
            });
        }

        // Filter workspaces by admin_id
        $adminId = getAdminIdByUserRole();
        $workspaces->where('admin_id', $adminId);

        $workspaces->when($search, function ($query) use ($search) {
            return $query->where('title', 'like', '%' . $search . '%')
                ->orWhere('id', 'like', '%' . $search . '%');
        });

        $totalworkspaces = $workspaces->count();

        $workspaces = $workspaces->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(function ($workspace) {
                return [
                    'id' => $workspace->id,
                    'title' => '<a href="workspaces/switch/' . $workspace->id . '">' . $workspace->title . '</a>',
                    'users' => $workspace->users,
                    'clients' => $workspace->clients
                ];
            });

        // Modify clients and users links
        foreach ($workspaces->items() as $workspace) {
            foreach ($workspace['clients'] as $i => $client) {
                $workspace['clients'][$i] = "<a href='" . route('clients.profile', ['id' => $client->id]) . "' target='_blank'><li class='avatar avatar-sm pull-up'  title='" . $client->full_name . "'><img src='" . ($client->photo ? asset('storage/' . $client->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle' /></li></a>";
            };
            foreach ($workspace['users'] as $i => $user) {
                $workspace['users'][$i] = "<a href='" . route('users.show', [$user->id]) . "' target='_blank'><li class='avatar avatar-sm pull-up'  title='" . $user->full_name . "'><img src='" . ($user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')) . "' class='rounded-circle' /></li></a>";
            };
        }

        return response()->json([
            "rows" => $workspaces->items(),
            "total" => $totalworkspaces,
        ]);
    }


    public function edit($id)
    {
        $workspace = Workspace::findOrFail($id);
        $admin = Admin::with('user', 'teamMembers.user')->find(getAdminIdByUserRole());
        $clients = Client::where('admin_id', getAdminIdByUserRole())->get();
        return view('workspaces.update_workspace', compact('workspace', 'clients', 'admin'));
    }

    public function update(Request $request, $id)
    {
        $formFields = $request->validate([
            'title' => ['required']
        ]);

        $userIds = $request->input('user_ids') ?? [];
        $clientIds = $request->input('client_ids') ?? [];
        $workspace = Workspace::findOrFail($id);

        // Set creator as a participant automatically
        if (User::where('id', $workspace->user_id)->exists() && !in_array($workspace->user_id, $userIds)) {
            array_splice($userIds, 0, 0, $workspace->user_id);
        } elseif (Client::where('id', $workspace->user_id)->exists() && !in_array($workspace->user_id, $clientIds)) {
            array_splice($clientIds, 0, 0, $workspace->user_id);
        }
        $existingUserIds = $workspace->users->pluck('id')->toArray();
        $existingClientIds = $workspace->clients->pluck('id')->toArray();
        $workspace->update($formFields);
        $workspace->users()->sync($userIds);
        $workspace->clients()->sync($clientIds);
        $userIds = array_diff($userIds, $existingUserIds);
        $clientIds = array_diff($clientIds, $existingClientIds);
        // Prepare notification data
        $notification_data = [
            'type' => 'workspace',
            'type_id' => $id,
            'type_title' => $workspace->title,
            'action' => 'assigned',
            'title' => 'Added in a workspace',
            'message' => $this->user->first_name . ' ' . $this->user->last_name . ' added you in workspace: ' . $workspace->title . ', ID #' . $id . '.'
        ];

        // Combine user and client IDs for notification recipients
        $recipients = array_merge(
            array_map(function ($userId) {
                return 'u_' . $userId;
            }, $userIds),
            array_map(function ($clientId) {
                return 'c_' . $clientId;
            }, $clientIds)
        );

        // Process notifications
        processNotifications($notification_data, $recipients);
        Session::flash('message', 'Workspace updated successfully.');
        return response()->json(['error' => false, 'id' => $id]);
    }

    public function destroy($id)
    {
        // dd($id);

        if ($this->workspace->id != $id) {
            $response = DeletionService::delete(Workspace::class, $id, 'Workspace');
            return $response;
        } else {
            return response()->json(['error' => true, 'message' => 'Current workspace couldn\'t deleted.']);
        }
    }

    public function destroy_multiple(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:workspaces,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);

        $ids = $validatedData['ids'];
        $deletedWorkspaces = [];
        $deletedWorkspaceTitles = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $workspace = Workspace::find($id);
            if ($workspace) {
                $deletedWorkspaces[] = $id;
                $deletedWorkspaceTitles[] = $workspace->title;
                DeletionService::delete(Workspace::class, $id, 'Workspace');
            }
        }

        return response()->json(['error' => false, 'message' => 'Workspace(s) deleted successfully.', 'id' => $deletedWorkspaces, 'titles' => $deletedWorkspaceTitles]);
    }

    public function switch($id)
    {
        if (Workspace::findOrFail($id)) {
            session()->put('workspace_id', $id);
            return back()->with('message', 'Workspace changed successfully.');
        } else {
            return back()->with('error', 'Workspace not found.');
        }
    }

    public function remove_participant()
    {
        $workspace = Workspace::findOrFail(session()->get('workspace_id'));
        if ($this->user->hasRole('client')) {
            $workspace->clients()->detach($this->user->id);
        } else {
            $workspace->users()->detach($this->user->id);
        }
        $workspace_id = isset($this->user->workspaces[0]['id']) && !empty($this->user->workspaces[0]['id']) ? $this->user->workspaces[0]['id'] : 0;
        $data = ['workspace_id' => $workspace_id];
        session()->put($data);
        Session::flash('message', 'Removed from workspace successfully.');
        return response()->json(['error' => false]);
    }

    public function duplicate($id)
    {
        // Define the related tables for this meeting
        $relatedTables = ['users', 'clients']; // Include related tables as needed

        // Use the general duplicateRecord function
        $duplicate = duplicateRecord(Workspace::class, $id, $relatedTables);

        if (!$duplicate) {
            return response()->json(['error' => true, 'message' => 'Workspace duplication failed.']);
        }
        if (request()->has('reload') && request()->input('reload') === 'true') {
            Session::flash('message', 'Workspace duplicated successfully.');
        }
        return response()->json(['error' => false, 'message' => 'Workspace duplicated successfully.', 'id' => $id]);
    }
}
