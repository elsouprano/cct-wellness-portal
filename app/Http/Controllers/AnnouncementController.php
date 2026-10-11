<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Announcement;

class AnnouncementController extends Controller
{
    public function index(Request $request)
    {
        $query = Announcement::with('user')->orderByDesc('created_at');

        // Students only see published announcements
        if ($request->user() && ! $request->user()->isAdmin() && ! $request->user()->isCounselor()) {
            $query->whereNotNull('published_at')->where('published_at', '<=', now());
        }

        $announcements = $query->paginate(10);
        return view('announcements.index', compact('announcements'));
    }

    public function show(Announcement $announcement, Request $request)
    {
        // Prevent students from seeing drafts/future published announcements
        if (! $request->user()->isAdmin() && ! $request->user()->isCounselor()) {
            if (is_null($announcement->published_at) || $announcement->published_at > now()) {
                abort(403, 'THIS ACTION IS UNAUTHORIZED.');
            }
        }

        return view('announcements.show', compact('announcement'));
    }

    public function create()
    {
        return view('announcements.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120', // 5MB max
            'status' => 'required|in:publish_now,schedule,draft',
            'scheduled_at' => 'required_if:status,schedule|nullable|date|after:now',
        ]);

        $announcement = new Announcement();
        $announcement->user_id = $request->user()->id;
        $announcement->title = $validated['title'];
        $announcement->body = clean($validated['body']); // HTMLPurifier
        
        if ($validated['status'] === 'publish_now') {
            $announcement->published_at = now();
        } elseif ($validated['status'] === 'schedule') {
            $announcement->published_at = $validated['scheduled_at'];
        } else {
            $announcement->published_at = null;
        }

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('announcements', 'public');
            $announcement->attachment_path = $path;
            $announcement->attachment_original_name = $file->getClientOriginalName();
        }

        $announcement->save();

        return redirect()->route('announcements.index')->with('status', 'Announcement created successfully.');
    }

    public function edit(Announcement $announcement)
    {
        return view('announcements.edit', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120', // 5MB max
            'remove_attachment' => 'nullable|boolean',
            'status' => 'required|in:keep,publish_now,schedule,draft',
            'scheduled_at' => 'required_if:status,schedule|nullable|date|after:now',
        ]);

        $announcement->title = $validated['title'];
        $announcement->body = clean($validated['body']);
        
        if ($validated['status'] === 'publish_now') {
            $announcement->published_at = now();
        } elseif ($validated['status'] === 'schedule') {
            $announcement->published_at = $validated['scheduled_at'];
        } elseif ($validated['status'] === 'draft') {
            $announcement->published_at = null;
        }

        $oldAttachmentPath = null;
        if ($request->boolean('remove_attachment') && $announcement->attachment_path) {
            $oldAttachmentPath = $announcement->attachment_path;
            $announcement->attachment_path = null;
            $announcement->attachment_original_name = null;
        }

        if ($request->hasFile('attachment')) {
            if ($announcement->attachment_path && !$oldAttachmentPath) {
                $oldAttachmentPath = $announcement->attachment_path;
            }
            $file = $request->file('attachment');
            $path = $file->store('announcements', 'public');
            $announcement->attachment_path = $path;
            $announcement->attachment_original_name = $file->getClientOriginalName();
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($announcement) {
            $announcement->save();
        });

        if ($oldAttachmentPath) {
            Storage::disk('public')->delete($oldAttachmentPath);
        }

        return redirect()->route('announcements.index')->with('status', 'Announcement updated successfully.');
    }

    public function destroy(Announcement $announcement)
    {
        if ($announcement->published_at !== null) {
            return redirect()->back()->with('error', "Posted announcements can't be deleted. Edit it instead, or switch it back to draft first.");
        }

        if ($announcement->attachment_path) {
            Storage::disk('public')->delete($announcement->attachment_path);
        }
        $announcement->delete();

        return redirect()->route('announcements.index')->with('status', 'Announcement deleted successfully.');
    }

    public function unpublish(Announcement $announcement)
    {
        $announcement->published_at = null;
        $announcement->save();
        
        return redirect()->back()->with('status', 'Announcement unpublished and moved to draft.');
    }
}
