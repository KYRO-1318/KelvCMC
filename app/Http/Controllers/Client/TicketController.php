<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = auth()->user()->tickets()->with('category')->latest()->paginate(10);

        return view('client.tickets.index', compact('tickets'));
    }

    public function create()
    {
        return view('client.tickets.create', [
            'categories' => TicketCategory::active()->get(),
            'departments' => TicketDepartment::active()->get(),
            'services' => auth()->user()->activeServices()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'ticket_category_id' => ['required', 'exists:ticket_categories,id'],
            'ticket_department_id' => ['nullable', 'exists:ticket_departments,id'],
            'service_id' => ['nullable', 'exists:services,id'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'message' => ['required', 'string', 'min:10'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120', 'mimes:png,jpg,jpeg,pdf,zip,txt,log,doc,docx'],
        ]);

        $ticket = Ticket::create([
            'number' => Ticket::generateNumber(),
            'user_id' => auth()->id(),
            'ticket_category_id' => $validated['ticket_category_id'],
            'ticket_department_id' => $validated['ticket_department_id'] ?? null,
            'service_id' => $validated['service_id'] ?? null,
            'subject' => $validated['subject'],
            'priority' => $validated['priority'],
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        $attachments = $this->storeAttachments($request->file('attachments', []));

        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'body' => $validated['message'],
            'attachments' => $attachments ?: null,
        ]);

        return redirect()->route('tickets.show', $ticket)->with('success', 'Ticket opened.');
    }

    public function show(Ticket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);

        return view('client.tickets.show', [
            'ticket' => $ticket->load(['messages.user', 'category']),
        ]);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        abort_if($ticket->isClosed(), 403, 'This ticket is closed.');

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:2'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:5120'],
        ]);

        $attachments = $this->storeAttachments($request->file('attachments', []));

        $ticket->messages()->create([
            'user_id' => auth()->id(),
            'body' => $validated['message'],
            'attachments' => $attachments ?: null,
        ]);

        $ticket->update([
            'status' => 'open',
            'last_reply_at' => now(),
        ]);

        return back()->with('success', 'Reply posted.');
    }

    public function close(Ticket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);

        $ticket->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return back()->with('success', 'Ticket closed.');
    }

    protected function storeAttachments(array $files): array
    {
        $attachments = [];

        foreach ($files as $file) {
            $path = $file->store('tickets/'.auth()->id(), 'uploads');
            $attachments[] = [
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'size' => $file->getSize(),
            ];
        }

        return $attachments;
    }
}
