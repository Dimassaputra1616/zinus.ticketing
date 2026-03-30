<?php

namespace App\Http\Controllers;

use App\Mail\TicketCreatedMail;
use App\Mail\TicketStatusUpdatedMail;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Form membuat tiket baru
     */
    public function create(Request $request)
    {
        $categories = Category::orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        
        $users = [];
        if ($request->user() && $request->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        }

        return view('tickets.create', [
            'categories' => $categories,
            'departments' => $departments,
            'users' => $users,
        ]);
    }

    /**
     * Halaman admin buat lihat semua tiket
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $statuses = [
            'open' => __('messages.status_open'),
            'assigned' => __('messages.status_assigned'),
            'in_progress' => __('messages.status_in_progress'),
            'waiting_user' => __('messages.status_waiting_user'),
            'resolved' => __('messages.status_resolved'),
            'closed' => __('messages.status_closed'),
        ];

        $statusFilter = $request->query('status');
        $sortFilter = $request->query('sort', 'newest'); // default to newest

        $ticketsQuery = Ticket::with(['category', 'department', 'user', 'attachments']);

        if ($user->isTechnician()) {
            $ticketsQuery->where('assigned_admin_id', $user->id);
        }

        if ($statusFilter && array_key_exists($statusFilter, $statuses)) {
            $ticketsQuery->where('status', $statusFilter);
        }

        $departmentFilter = $request->query('department');

        if ($departmentFilter) {
            $ticketsQuery->where('department_id', $departmentFilter);
        }

        $priorityFilter = $request->query('priority');

        if ($priorityFilter && in_array($priorityFilter, ['low', 'medium', 'high', 'critical'])) {
            $ticketsQuery->where('priority', $priorityFilter);
        }

        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $parsedStartDate = $startDate ? Carbon::parse($startDate)->startOfDay() : null;
        $parsedEndDate = $endDate ? Carbon::parse($endDate)->endOfDay() : null;

        if ($parsedStartDate && $parsedEndDate) {
            $ticketsQuery->whereBetween('created_at', [$parsedStartDate, $parsedEndDate]);
        } elseif ($parsedStartDate) {
            $ticketsQuery->where('created_at', '>=', $parsedStartDate);
        } elseif ($parsedEndDate) {
            $ticketsQuery->where('created_at', '<=', $parsedEndDate);
        }

        $searchTerm = trim((string) $request->query('search', ''));

        if ($searchTerm !== '') {
            $ticketsQuery->where(function ($query) use ($searchTerm) {
                $query
                    ->where('title', 'like', '%' . $searchTerm . '%')
                    ->orWhere('description', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($relation) use ($searchTerm) {
                        $relation->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('email', 'like', '%' . $searchTerm . '%');
                    });

                if (is_numeric($searchTerm)) {
                    $query->orWhere('id', (int) $searchTerm);
                }
            });
        }

        // Apply distinct sorting logic based on the $sortFilter
        if ($sortFilter === 'priority') {
            $ticketsQuery->orderByRaw("
                CASE priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                    ELSE 5
                END ASC
            ")->latest();
        } elseif ($sortFilter === 'oldest') {
            $ticketsQuery->oldest();
        } elseif ($sortFilter === 'title') {
            $ticketsQuery->orderBy('title', 'asc');
        } else {
            // Default newest sorting
            $ticketsQuery->latest();
        }

        if ($request->boolean('autocomplete')) {
            $suggestions = (clone $ticketsQuery)
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get(['id', 'title', 'status', 'user_id'])
                ->map(fn (Ticket $ticket) => [
                    'id' => $ticket->id,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'user' => $ticket->user?->name,
                    'url' => route('tickets.show', $ticket),
                ]);

            return response()->json([
                'suggestions' => $suggestions,
            ]);
        }

        $tickets = $ticketsQuery
            ->paginate(12)
            ->appends($request->except(['page', 'refresh']));

        $statusCounts = DB::table('tickets')
            ->when($departmentFilter, fn ($query) => $query->where('department_id', $departmentFilter))
            ->when($priorityFilter, fn ($query) => $query->where('priority', $priorityFilter))
            ->when($parsedStartDate, fn ($query) => $query->where('created_at', '>=', $parsedStartDate))
            ->when($parsedEndDate, fn ($query) => $query->where('created_at', '<=', $parsedEndDate))
            ->when($user->isTechnician(), fn ($query) => $query->where('assigned_admin_id', $user->id))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalTickets = DB::table('tickets')
            ->when($departmentFilter, fn ($query) => $query->where('department_id', $departmentFilter))
            ->when($priorityFilter, fn ($query) => $query->where('priority', $priorityFilter))
            ->when($parsedStartDate, fn ($query) => $query->where('created_at', '>=', $parsedStartDate))
            ->when($parsedEndDate, fn ($query) => $query->where('created_at', '<=', $parsedEndDate))
            ->when($user->isTechnician(), fn ($query) => $query->where('assigned_admin_id', $user->id))
            ->count();

        $departments = Department::orderBy('name')->get();

        $ticketCollection = $tickets->getCollection();
        $statusChecksum = collect($statusCounts)
            ->sortKeys()
            ->map(fn ($count, $status) => "{$status}:{$count}")
            ->join(',');
        $checksum = $this->buildTicketsChecksum($ticketCollection, $totalTickets, $statusChecksum);

        if ($request->boolean('refresh')) {
            return response()->json([
                'checksum' => $checksum,
                'hasResults' => $ticketCollection->count() > 0,
                'fragments' => [
                    'ticket-summary' => view('tickets.partials.summary', [
                        'statuses' => $statuses,
                        'statusCounts' => $statusCounts,
                        'totalTickets' => $totalTickets,
                    ])->render(),
                    'ticket-table' => view('tickets.partials.table', [
                        'tickets' => $tickets,
                        'statuses' => $statuses,
                        'statusCounts' => $statusCounts,
                        'statusFilter' => $statusFilter,
                        'departments' => $departments,
                        'departmentFilter' => $departmentFilter,
                        'priorityFilter' => $priorityFilter,
                        'sortFilter' => $sortFilter,
                        'searchTerm' => $searchTerm,
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                    ])->render(),
                ],
            ]);
        }

        return view('tickets.index', [
            'tickets' => $tickets,
            'statuses' => $statuses,
            'statusCounts' => $statusCounts,
            'statusFilter' => $statusFilter,
            'departments' => $departments,
            'departmentFilter' => $departmentFilter,
            'priorityFilter' => $priorityFilter,
            'sortFilter' => $sortFilter,
            'totalTickets' => $totalTickets,
            'checksum' => $checksum,
            'searchTerm' => $searchTerm,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }

    /**
     * Simpan tiket baru dari user
     */
    public function store(Request $request)
    {
        $validationRules = [
            'title' => 'required|string|min:8|max:255',
            'description' => 'required|string|min:20',
            'category_id' => 'required|exists:categories,id',
            'department_id' => 'required|exists:departments,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'nullable|file|max:5120|mimes:pdf,jpeg,jpg,png,doc,docx,xls,xlsx,txt,zip',
            'idempotency_key' => 'nullable|string|max:64',
            'priority' => 'required|in:low,medium,high,critical',
        ];

        if ($request->user() && $request->user()->isAdmin()) {
            $validationRules['user_id'] = 'required|exists:users,id|not_in:' . $request->user()->id;
        }

        $request->validate($validationRules, [
            'user_id.not_in' => __('messages.admin_cannot_create_for_self', [], 'id') ?? 'Admin tidak dapat membuat tiket untuk diri sendiri.',
        ]);

        $idempotencyKey = $request->input('idempotency_key') ?: (string) Str::uuid();
        $userId = $request->user()?->id;
        $sessionId = $request->session()->getId();
        $dedupeWindow = now()->subSeconds(60);
        $attachmentMeta = collect($request->file('attachments', []))
            ->filter()
            ->map(fn ($file) => $file->getClientOriginalName() . '|' . $file->getSize())
            ->values()
            ->all();
        $payloadHash = hash('sha256', json_encode([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'department_id' => $request->department_id,
            'priority' => $request->priority,
            'attachments' => $attachmentMeta,
        ]));

        $idempotencyQuery = DB::table('ticket_idempotency_keys')
            ->where('idempotency_key', $idempotencyKey)
            ->where('created_at', '>=', $dedupeWindow);

        if ($userId) {
            $idempotencyQuery->where('user_id', $userId);
        } else {
            $idempotencyQuery->where('session_id', $sessionId);
        }

        $existingKey = $idempotencyQuery->first();
        if ($existingKey) {
            if ($existingKey->ticket_id) {
                return redirect()
                    ->route('tickets.show', $existingKey->ticket_id)
                    ->with('success', '✅ Tiket sudah dibuat. Duplikasi dicegah.');
            }

            return redirect()
                ->back()
                ->with('success', '⏳ Permintaan sedang diproses. Mohon tunggu sebentar.');
        }

        $duplicatePayloadQuery = DB::table('ticket_idempotency_keys')
            ->where('payload_hash', $payloadHash)
            ->where('created_at', '>=', $dedupeWindow);

        if ($userId) {
            $duplicatePayloadQuery->where('user_id', $userId);
        } else {
            $duplicatePayloadQuery->where('session_id', $sessionId);
        }

        $duplicatePayload = $duplicatePayloadQuery->orderByDesc('created_at')->first();
        if ($duplicatePayload) {
            if ($duplicatePayload->ticket_id) {
                return redirect()
                    ->route('tickets.show', $duplicatePayload->ticket_id)
                    ->with('success', '✅ Tiket sudah dibuat. Duplikasi dicegah.');
            }

            return redirect()
                ->back()
                ->with('success', '⏳ Permintaan sedang diproses. Mohon tunggu sebentar.');
        }

        DB::table('ticket_idempotency_keys')->insert([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'idempotency_key' => $idempotencyKey,
            'payload_hash' => $payloadHash,
            'ticket_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reporterName = $request->user()?->name ?? $request->input('reporter_name');
        $reporterEmail = $request->user()?->email ?? $request->input('reporter_email');

        $ticketUserId = auth()->id();
        if ($request->user() && $request->user()->isAdmin()) {
            $ticketUserId = $request->input('user_id');
            // override reporter name/email for the target user
            $targetUser = User::find($ticketUserId);
            if ($targetUser) {
                $reporterName = $targetUser->name;
                $reporterEmail = $targetUser->email;
            }
        }

        $ticket = Ticket::create([
            'title' => $request->title,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'department_id' => $request->department_id,
            'user_id' => $ticketUserId,
            'reporter_name' => $reporterName,
            'reporter_email' => $reporterEmail,
            'status' => 'open',
            'priority' => $request->priority,
        ]);

        DB::table('ticket_idempotency_keys')
            ->where('idempotency_key', $idempotencyKey)
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->when(! $userId, fn ($query) => $query->where('session_id', $sessionId))
            ->update([
                'ticket_id' => $ticket->id,
                'updated_at' => now(),
            ]);

        $actor = $request->user();
        $actorName = $actor?->name ?? 'System';
        $actorEmail = $actor?->email;

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'actor_name' => $actorName,
            'actor_email' => $actorEmail,
            'status' => 'open',
            'message' => 'Tiket dibuat oleh user',
            'action' => 'created',
            'old_value' => null,
            'new_value' => 'open',
        ]);

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $uploadedFile) {
                if (! $uploadedFile) {
                    continue;
                }

                $storedPath = $uploadedFile->store('tickets', 'public');

                $attachmentPayload = [];
                if (Schema::hasColumn('ticket_attachments', 'original_name')) {
                    $attachmentPayload = [
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'stored_name' => $storedPath,
                        'mime_type' => $uploadedFile->getClientMimeType(),
                        'file_size' => $uploadedFile->getSize(),
                        'disk' => 'public',
                    ];
                } else {
                    $attachmentPayload = [
                        'file_name' => $uploadedFile->getClientOriginalName(),
                        'file_path' => $storedPath,
                        'file_type' => $uploadedFile->getClientMimeType(),
                    ];
                }

                $ticket->attachments()->create($attachmentPayload);
            }

            $ticket->touch();
        }

        $ticket->loadMissing('user', 'category', 'department');

        $admins = $this->adminUsers($actor);
        $emailRecipients = $this->adminEmailRecipients($actor, $admins);

        try {
            // Send database notifications to admins
            User::query()
                ->where(function ($query) {
                    $query->where('role', 'admin')
                        ->orWhere('role', 'Admin')
                        ->orWhere('is_admin', true);
                })
                ->get()
                ->each(fn (User $admin) => $admin->notify(new TicketCreatedNotification($ticket, $actor)));

            // Send emails to admins and extra recipients
            if ($emailRecipients->isNotEmpty()) {
                Mail::to($emailRecipients->all())->send(new TicketCreatedMail($ticket, $actor));
            }
        } catch (\Exception $e) {
            logger()->error('Failed to send ticket creation notifications/emails', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
        }

        return redirect()->route('dashboard')->with('success', '✅ Tiket berhasil dikirim! Tim IT akan segera menindaklanjuti.');
    }

    public function show(Request $request, Ticket $ticket)
    {
        $user = $request->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isTechnician() && $ticket->assigned_admin_id !== $user->id) {
            abort(403, 'Akses ditolak - tiket ini tidak di-assign ke Anda.');
        }

        if (! $user->hasDashboardAccess() && $ticket->user_id !== $user->id) {
            abort(403);
        }

        $ticket->loadMissing(['category', 'department', 'user', 'attachments']);

        $statuses = [
            'open' => __('messages.status_open'),
            'assigned' => __('messages.status_assigned'),
            'in_progress' => __('messages.status_in_progress'),
            'waiting_user' => __('messages.status_waiting_user'),
            'resolved' => __('messages.status_resolved'),
            'closed' => __('messages.status_closed'),
        ];

        $statusLogs = $ticket->ticketLogs()
            ->where('action', 'status_updated')
            ->with('user')
            ->orderByDesc('created_at')
            ->take(30)
            ->get();

        $logs = $ticket->ticketLogs()->get();

        return view('tickets.show', [
            'ticket' => $ticket,
            'statuses' => $statuses,
            'isAdmin' => $user->isAdmin() || $user->isTechnician(),
            'logs' => $logs,
            'statusLogs' => $statusLogs,
        ]);
    }

    /**
     * Update status tiket (khusus admin)
     */
    public function updateStatus(Request $request, Ticket $ticket)
    {
        $request->validate([
            'status' => 'required|in:open,assigned,in_progress,waiting_user,resolved,closed',
            'resolution' => 'nullable|string',
        ]);

        $previousStatus = $ticket->status;
        $actor = $request->user();
        $actorName = $actor?->name ?? 'System';
        $actorEmail = $actor?->email;

        $updateData = [
            'status' => $request->status,
        ];

        if ($request->has('resolution')) {
            $updateData['resolution'] = $request->resolution;
        }

        if (is_null($ticket->assigned_admin_id) && $actor && ($actor->isAdmin() || $actor->isTechnician())) {
            $updateData['assigned_admin_id'] = $actor->id;
        }

        $ticket->update($updateData);

        TicketLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $actor?->id,
            'actor_name' => $actorName,
            'actor_email' => $actorEmail,
            'status' => $request->status,
            'message' => null,
            'action' => 'status_updated',
            'old_value' => $previousStatus,
            'new_value' => $request->status,
        ]);

        $ticket->loadMissing('user', 'category', 'department');

        $recipient = $ticket->user;

        if ($recipient && $recipient->email) {
            Mail::to([$recipient->email])->send(
                new TicketStatusUpdatedMail($ticket, $previousStatus, $ticket->status, $actor)
            );
        }

        $filter = $request->input('filter');
        $departmentFilter = $request->input('department_filter');
        $searchFilter = $request->input('search');
        $redirectTo = $request->input('redirect_to');

        $filterParams = array_filter([
            'status' => $filter,
            'department' => $departmentFilter,
            'search' => $searchFilter,
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ], static fn ($value) => $value !== null && $value !== '');

        $redirectResponse = $redirectTo
            ? redirect()->to($redirectTo)
            : redirect()->route('tickets.index', $filterParams);

        return $redirectResponse
            ->with('ok', "Status tiket #{$ticket->id} diperbarui menjadi " . ucfirst(str_replace('_', ' ', $request->status)) . '.');
    }

    /**
     * Daftar tiket milik user yang sedang login
     */
    public function myTickets(Request $request)
    {
        $user = $request->user();

        $statuses = [
            'open' => __('messages.status_open'),
            'in_progress' => __('messages.status_in_progress'),
            'resolved' => __('messages.status_resolved'),
            'closed' => __('messages.status_closed'),
        ];

        $statusFilter = $request->query('status');

        $ticketsQuery = Ticket::with(['category', 'department', 'attachments'])
            ->where('user_id', $user->id)
            ->latest();

        if ($statusFilter && array_key_exists($statusFilter, $statuses)) {
            $ticketsQuery->where('status', $statusFilter);
        }

        $tickets = $ticketsQuery->paginate(10)->appends($request->except(['page', 'refresh']));

        $statusCounts = DB::table('tickets')
            ->where('user_id', $user->id)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $totalTickets = DB::table('tickets')
            ->where('user_id', $user->id)
            ->count();

        $statusChecksum = collect($statusCounts)
            ->sortKeys()
            ->map(fn ($count, $status) => "{$status}:{$count}")
            ->join(',');
        $checksum = $this->buildTicketsChecksum($tickets->getCollection(), $totalTickets, $statusChecksum);

        if ($request->boolean('refresh')) {
            return response()->json([
                'checksum' => $checksum,
                'fragments' => [
                    'my-ticket-cards' => view('tickets.partials.mine-cards', [
                        'totalTickets' => $totalTickets,
                        'statusCounts' => $statusCounts,
                    ])->render(),
                    'my-ticket-table' => view('tickets.partials.mine-table', [
                        'tickets' => $tickets,
                        'statuses' => $statuses,
                        'statusFilter' => $statusFilter,
                        'statusCounts' => $statusCounts,
                    ])->render(),
                ],
            ]);
        }

        return view('tickets.mine', [
            'tickets' => $tickets,
            'statuses' => $statuses,
            'statusFilter' => $statusFilter,
            'statusCounts' => $statusCounts,
            'totalTickets' => $totalTickets,
            'checksum' => $checksum,
        ]);
    }

    private function buildTicketsChecksum(Collection $tickets, int $totalTickets, ?string $statusChecksum = null): string
    {
        $ids = $tickets->pluck('id')->join('-');
        $timestamps = $tickets->pluck('updated_at')->map(function ($date) {
            return optional($date)->format('U') ?? '0';
        })->join('-');

        return hash('sha256', $totalTickets.'|'.($statusChecksum ?? '').'|'.$ids.'|'.$timestamps);
    }

    public function adminEmailRecipients(?User $actor = null, ?Collection $admins = null): Collection
    {
        $mailBlocklist = array_map('strtolower', [
            'admin@znus.com',
        ]);

        $extraAdminEmails = collect(preg_split('/[;,]+/', (string) config('mail.extra_admins', '')))
            ->map(fn ($email) => trim((string) $email))
            ->filter();

        $adminEmails = ($admins ?: $this->adminUsers($actor))->pluck('email');

        return $adminEmails
            ->merge($extraAdminEmails)
            ->filter(fn ($email) => $email && ! in_array(strtolower((string) $email), $mailBlocklist, true))
            ->unique(fn ($email) => strtolower((string) $email))
            ->values();
    }

    private function adminUsers(?User $actor = null): Collection
    {
        return User::query()
            ->where(function ($query) {
                $query->where('role', 'admin')
                    ->orWhere('role', 'Admin')
                    ->orWhere('is_admin', true);
            })
            ->when($actor, fn ($query) => $query->where('id', '!=', $actor->id))
            ->get();
    }
}
