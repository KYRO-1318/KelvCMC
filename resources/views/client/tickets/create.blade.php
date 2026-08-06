<x-client-layout title="Open a ticket">
    <div class="mx-auto max-w-2xl space-y-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('tickets.index') }}" class="text-slate-500 transition hover:text-white">←</a>
            <h1 class="text-2xl font-bold text-white">Open a support ticket</h1>
        </div>

        <form method="POST" action="{{ route('tickets.store') }}" enctype="multipart/form-data" class="card space-y-4">
            @csrf

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-300">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required class="input" placeholder="Brief summary of your issue">
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Category</label>
                    <select name="ticket_category_id" class="input" required>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(old('ticket_category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Priority</label>
                    <select name="priority" class="input" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Department (optional)</label>
                    <select name="ticket_department_id" class="input">
                        <option value="">— None —</option>
                        @foreach ($departments as $department)
                            <option value="{{ $department->id }}">{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-300">Related service (optional)</label>
                    <select name="service_id" class="input">
                        <option value="">— None —</option>
                        @foreach ($services as $service)
                            <option value="{{ $service->id }}">{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-300">Message</label>
                <textarea name="message" rows="6" required class="input" placeholder="Describe the issue in detail. Include error messages if any.">{{ old('message') }}</textarea>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-300">Attachments (optional, max 5 MB each)</label>
                <input type="file" name="attachments[]" multiple class="input file:mr-3 file:rounded-lg file:border-0 file:bg-violet-500/20 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-violet-300">
            </div>

            <button type="submit" class="btn-primary">Submit ticket</button>
        </form>
    </div>
</x-client-layout>
