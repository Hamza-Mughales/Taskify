<?php
namespace App\Http\Controllers;
use Throwable;
use App\Models\Task;
use App\Models\User;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Project;
use App\Models\TaskUser;
use App\Models\Template;
use App\Models\Workspace;
use App\Models\TeamMember;
use Illuminate\Http\Request;
use App\Services\DeletionService;
use GuzzleHttp\Promise\TaskQueue;
use App\Notifications\VerifyEmail;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Notifications\AccountCreation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Contracts\Role as ContractsRole;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\Request as FacadesRequest;
use Spatie\Permission\Contracts\Permission;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $workspace = Workspace::find(session()->get('workspace_id'));
        $users = $workspace->users;
        return view('users.users', ['users' => $users]);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $roles = Role::where('guard_name', 'web')->where('name', '!=', 'superadmin')->get();
        return view('users.create_user', ['roles' => $roles]);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, User $user)
    {
        $adminId = null;
        if (Auth::guard('web')->check() && Auth::user()->hasRole('admin')) {
            $admin = Admin::where('user_id', Auth::user()->id)->first();
            if ($admin) {
                $adminId = $admin->id;
            }
        }
        $formFields = $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => 'required|confirmed|min:6',
            'password_confirmation' => 'required',
            'address' => 'required',
            'phone' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'country_code' => 'required',
            'zip' => 'required',
            'dob' => 'required',
            'doj' => 'required',
            'role' => 'required',
            'status' => 'required',
        ]);
        $workspace = Workspace::find(session()->get('workspace_id'));
        $dob = $request->input('dob');
        $doj = $request->input('doj');
        $password = $request->input('password');
        $formFields['dob'] = format_date($dob, null, "Y-m-d");
        $formFields['doj'] = format_date($doj, null, "Y-m-d");
        $formFields['password'] = bcrypt($formFields['password']);
        if ($request->hasFile('profile')) {
            $formFields['photo'] = $request->file('profile')->store('photos', 'public');
        } else {
            $formFields['photo'] = 'photos/no-image.jpg';
        }
        $require_ev = isAdminOrHasAllDataAccess() && $request->has('require_ev') && $request->input('require_ev') == 0 ? 0 : 1;
        $status = getAuthenticatedUser()->hasRole('admin') && $request->has('status') && $request->input('status') == 1 ? 1 : 0;
        if ($status == 1) {
            $formFields['email_verified_at'] = now()->tz(config('app.timezone'));
        }
        $user = User::create($formFields);
        TeamMember::create([
            'admin_id' => $adminId,
            'user_id' => $user->id,
        ]);
        try {
            if ($require_ev == 1) {
                $user->notify(new VerifyEmail($user));
            }
            $workspace->users()->attach($user->id);
            $user->assignRole($request->input('role'));
            if (isEmailConfigured()) {
                $account_creation_template = Template::where('type', 'email')
                    ->where('name', 'account_creation')
                    ->first();
                if (!$account_creation_template || ($account_creation_template->status !== 0)) {
                    $user->notify(new AccountCreation($user, $password));
                }
            }
            Session::flash('message', 'User created successfully.');
            return response()->json(['error' => false, 'id' => $user->id]);
        } catch (TransportExceptionInterface $e) {
            $user = User::findOrFail($user->id);
            $user->delete();
            return response()->json(['error' => true, 'message' => 'User TransportException couldn\'t be created, please check email settings.']);
        } catch (Throwable $e) {
            // Catch any other throwable, including non-Exception errors
            $user = User::findOrFail($user->id);
            $user->delete();
            return response()->json(['error' => true, 'message' => $e->getMessage()]);
        }
    }
    public function email_verification()
    {
        $user = getAuthenticatedUser();
        if (!$user->hasVerifiedEmail()) {
            return view('auth.verification-notice');
        } else {
            return redirect(route('home.index'));
        }
    }
    public function resend_verification_link(Request $request)
    {
        if (isEmailConfigured()) {
            $request->user()->sendEmailVerificationNotification();
            return back()->with('message', 'Verification link sent.');
        } else {
            return back()->with('error', 'Verification link couldn\'t sent.');
        }
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit_user($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::where('guard_name', 'web')->get();
        return view('users.edit_user', ['user' => $user, 'roles' => $roles]);
    }
    public function update_user(Request $request, $id)
    {
        $formFields = $request->validate([
            'first_name' => ['required'],
            'last_name' => ['required'],
            'phone' => 'required',
            'address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'country_code' => 'required',
            'zip' => 'required',
            'dob' => 'required',
            'doj' => 'required',
            'status' => 'required',
        ]);
        $user = User::findOrFail($id);
        $dob = $request->input('dob');
        $doj = $request->input('doj');
        $formFields['dob'] = format_date($dob, null, "Y-m-d");
        $formFields['doj'] = format_date($doj, null, "Y-m-d");
        if ($request->hasFile('upload')) {
            if ($user->photo != 'photos/no-image.jpg' && $user->photo !== null)
                Storage::disk('public')->delete($user->photo);
            $formFields['photo'] = $request->file('upload')->store('photos', 'public');
        }
        $status = getAuthenticatedUser()->hasRole('admin') && $request->has('status') && $request->input('status') == 1 ? 1 : 0;
        $formFields['status'] = $status;
        $user->update($formFields);
        $user->syncRoles($request->input('role'));
        Session::flash('message', 'Profile details updated successfully.');
        return response()->json(['error' => false, 'id' => $user->id]);
    }
    public function update_photo(Request $request, $id)
    {
        if ($request->hasFile('upload')) {
            $old = User::findOrFail($id);
            if ($old->photo != 'photos/no-image.jpg' && $old->photo !== null)
                Storage::disk('public')->delete($old->photo);
            $formFields['photo'] = $request->file('upload')->store('photos', 'public');
            User::findOrFail($id)->update($formFields);
            return back()->with('message', 'Profile picture updated successfully.');
        } else {
            return back()->with('error', 'No profile picture selected.');
        }
    }
    public function delete_user($id)
    {
        $user = User::findOrFail($id);
        $response = DeletionService::delete(User::class, $id, 'User');
        $user->todos()->delete();
        return $response;
    }
    public function delete_multiple_user(Request $request)
    {
        // Validate the incoming request
        $validatedData = $request->validate([
            'ids' => 'required|array', // Ensure 'ids' is present and an array
            'ids.*' => 'integer|exists:users,id' // Ensure each ID in 'ids' is an integer and exists in the table
        ]);
        $ids = $validatedData['ids'];
        $deletedUsers = [];
        $deletedUserNames = [];
        // Perform deletion using validated IDs
        foreach ($ids as $id) {
            $user = User::findOrFail($id);
            if ($user) {
                $deletedUsers[] = $id;
                $deletedUserNames[] = $user->first_name . ' ' . $user->last_name;
                DeletionService::delete(User::class, $id, 'User');
                $user->todos()->delete();
            }
        }
        return response()->json(['error' => false, 'message' => 'User(s) deleted successfully.', 'id' => $deletedUsers, 'titles' => $deletedUserNames]);
    }
    public function logout(Request $request)
    {
        if (Auth::guard('web')->check()) {
            auth('web')->logout();
        } else {
            auth('client')->logout();
        }
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/')->with('message', 'Logged out successfully.');
    }
    public function login()
    {
        return view('front-end.login');
        // return view('auth.login');
    }
    public function authenticate(Request $request)
    {
        $formFields = $request->validate([
            'email' => ['required', 'email'],
            'password' => 'required'
        ]);

        if (!User::where('email', $formFields['email'])->first() && !Client::where('email', $formFields['email'])->first()) {
            return response()->json(['error' => true, 'message' => 'Account not found!']);
        }
        $logged_in = false;
        if (auth('web')->attempt($formFields)) {
            $user = auth('web')->user();
            if ($user->hasRole('admin') || $user->status == 1) {
                $logged_in = true;
            } else {
                return response()->json(['error' => true, 'message' => get_label('status_not_active', 'Your account is currently inactive. Please contact admin for assistance.')]);
            }
        }
        if (auth('client')->attempt($formFields)) {
            $user = auth('client')->user();
            if ($user->status == 1) {
                $logged_in = true;
            } else {
                return response()->json(['error' => true, 'message' => get_label('status_not_active', 'Your account is currently inactive. Please contact admin for assistance.')]);
            }
        }
        if ($logged_in) {
            $workspace_id = isset($user->workspaces[0]['id']) && !empty($user->workspaces[0]['id']) ? $user->workspaces[0]['id'] : 0;
            $my_locale = $locale = isset($user->lang) && !empty($user->lang) ? $user->lang : 'en';
            $data = ['user_id' => $user->id, 'workspace_id' => $workspace_id, 'my_locale' => $my_locale, 'locale' => $locale];
            session()->put($data);
            $request->session()->regenerate();
            Session::flash('message', 'Logged in successfully.');
            return response()->json(['error' => false, 'message' => 'Logged in successfully', 'redirect_url' => $request->redirect_url]);
        } else {
            return response()->json(['error' => true, 'message' => 'Invalid credentials!']);
        }
    }
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|regex:/^[^\d]+$/',
            'last_name' => 'required|string|regex:/^[^\d]+$/',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|regex:/^\d+$/|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'password_confirmation' => 'required'
        ], [
            'first_name.required' => 'First name is required.',
            'first_name.string' => 'First name must be a string.',
            'first_name.regex' => 'First name cannot contain integers.',
            'last_name.required' => 'Last name is required.',
            'last_name.string' => 'Last name must be a string.',
            'last_name.regex' => 'Last name cannot contain integers.',
            'email.required' => 'Email is required.',
            'email.email' => 'Invalid email format.',
            'email.unique' => 'Email already exists.',
            'phone.required' => 'Phone number is required.',
            'phone.string' => 'Phone number must be a string.',
            'phone.unique' => 'Phone Number already exists.',
            'phone.regex' => 'Phone number can only contain digits.',
            'password.required' => 'Password is required.',
            'password.string' => 'Password must be a string.',
            'password.min' => 'Password must be at least 6 characters long.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()], 422);
        }
        // Create a new user
        $user = new User();
        $user->first_name = $request->first_name;
        $user->last_name =  $request->last_name;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $user->status = '1';
        $user->email_verified_at = now()->tz(config('app.timezone'));
        $user->save();
        $user->assignRole('admin');
        if (isEmailConfigured()) {
            $account_creation_template = Template::where('type', 'email')
                ->where('name', 'account_creation')
                ->first();
            if (!$account_creation_template || ($account_creation_template->status !== 0)) {
                $user->notify(new AccountCreation($user, $request->password));
            }
        }
        // Create a new admin with the user ID
        $admin = new Admin();
        $admin->user_id = $user->id;
        $admin->save();
        return response()->json(['error' => false, 'message' => 'User registered successfully', 'redirect_url' => route('login')]);
    }
    public function show($id)
    {
        $user = User::findOrFail($id);
        $workspace = Workspace::find(session()->get('workspace_id'));
        $projects = isAdminOrHasAllDataAccess() ? $workspace->projects : $user->projects;
        $tasks = isAdminOrHasAllDataAccess() ? $workspace->tasks->count() : $user->tasks->count();
        $users = $workspace->users;
        $clients = $workspace->clients;
        return view('users.user_profile', ['user' => $user, 'projects' => $projects, 'tasks' => $tasks, 'users' => $users, 'clients' => $clients, 'auth_user' => getAuthenticatedUser()]);
    }
    public function list()
    {
        $workspace = Workspace::find(session()->get('workspace_id'));
        $search = request('search');
        $sort = (request('sort')) ? request('sort') : "id";
        $order = (request('order')) ? request('order') : "DESC";
        $users = $workspace->users();
        $users = $users->when($search, function ($query) use ($search) {
            return $query->where('first_name', 'like', '%' . $search . '%')
                ->orWhere('last_name', 'like', '%' . $search . '%')
                ->orWhere('phone', 'like', '%' . $search . '%')
                ->orWhere('email', 'like', '%' . $search . '%');
        });
        $totalusers = $users->count();
        $users = $users->orderBy($sort, $order)
            ->paginate(request("limit"))
            ->through(fn ($user) => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'role' => "<span class='badge bg-label-" . (isset(config('taskify.role_labels')[$user->getRoleNames()->first()]) ? config('taskify.role_labels')[$user->getRoleNames()->first()] : config('taskify.role_labels')['default']) . " me-1'>" . $user->getRoleNames()->first() . "</span>",
                'email' => $user->email,
            'phone' => $user->country_code . $user->phone,
                'photo' => "<div class='avatar avatar-md pull-up' title='" . $user->first_name . " " . $user->last_name . "'>
                    <a href='" . route('users.show', [$user->id]) . "'>
                    <img src='" . ($user->photo ? asset('storage/' . $user->photo) : asset('storage/photos/no-image.jpg')) . "' alt='Avatar' class='rounded-circle'>
                    </a>
                    </div>",
                'tasks' =>  count($user->tasks),
                'projects' => count($user->projects),
                'status' => $user->status,
            ]);
        return response()->json([
            "rows" => $users->items(),
            "total" => $totalusers,
        ]);
    }
    public function permissions(Request $request, User $user)
    {
        $userId = $user->id;
        $role  = $user->roles[0]['name'];
        $role = Role::where('name', $role)->first();
        // Fetch permissions associated with the role
        // $rolePermissions = $role->permissions;
        $mergedPermissions = collect();
        // Loop through each role to merge its permissions
        $mergedPermissions = $mergedPermissions->merge($role->permissions);
        // If you also want to include permissions directly assigned to the user
        $mergedPermissions = $mergedPermissions->merge($user->permissions);
        return view('users.permissions', ['mergedPermissions' => $mergedPermissions, 'role' => $role, 'user' => $user]);
    }
    public function update_permissions(Request $request, User $user)
    {
        $user->syncPermissions($request->permissions);
        return redirect()->back()->with(['message' => 'Permissions updated successfully']);
    }
}
