<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Project;
use App\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CrmController extends Controller
{
    public function index(): Response
    {
        $projects = Project::with(['company', 'contacts', 'tags'])
            ->whereNotIn('stage', ['won', 'lost'])
            ->orderBy('position')
            ->get()
            ->groupBy('stage');

        // Calculate stage values
        $stageValues = [];
        foreach (Project::STAGES as $key => $label) {
            if (!in_array($key, ['won', 'lost'])) {
                $stageValues[$key] = Project::where('stage', $key)->sum('value');
            }
        }

        // Calculate won/lost stats
        $stats = [
            'week' => $this->getStats(now()->startOfWeek()),
            'month' => $this->getStats(now()->startOfMonth()),
            'quarter' => $this->getStats(now()->startOfQuarter()),
            'year' => $this->getStats(now()->startOfYear()),
        ];

        return Inertia::render('CRM/Index', [
            'projects' => $projects,
            'stageValues' => $stageValues,
            'stats' => $stats,
            'companies' => Company::orderBy('name')->get(),
            'contacts' => Contact::with('company')->orderBy('name')->get(),
            'tags' => Tag::orderBy('name')->get(),
            'stages' => Project::STAGES,
        ]);
    }

    private function getStats($startDate): array
    {
        return [
            'won' => Project::where('stage', 'won')
                ->where('won_at', '>=', $startDate)
                ->sum('value'),
            'lost' => Project::where('stage', 'lost')
                ->where('lost_at', '>=', $startDate)
                ->sum('value'),
        ];
    }

    public function storeCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        return Company::create($validated);
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'title' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        return Contact::create($validated);
    }

    public function storeProject(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'value' => 'required|numeric|min:0',
            'stage' => 'required|in:' . implode(',', array_keys(Project::STAGES)),
            'notes' => 'nullable|string',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        $contactIds = $validated['contact_ids'] ?? [];
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['contact_ids'], $validated['tag_ids']);

        $project = Project::create($validated);
        
        if (!empty($contactIds)) {
            $project->contacts()->attach($contactIds);
        }

        if (!empty($tagIds)) {
            $project->tags()->attach($tagIds);
        }

        return $project->load(['company', 'contacts', 'tags']);
    }

    public function updateProject(Request $request, Project $project)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'value' => 'sometimes|numeric|min:0',
            'stage' => 'sometimes|in:' . implode(',', array_keys(Project::STAGES)),
            'notes' => 'nullable|string',
            'position' => 'sometimes|integer',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'exists:contacts,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id',
        ]);

        if (isset($validated['contact_ids'])) {
            $project->contacts()->sync($validated['contact_ids']);
            unset($validated['contact_ids']);
        }

        if (isset($validated['tag_ids'])) {
            $project->tags()->sync($validated['tag_ids']);
            unset($validated['tag_ids']);
        }

        $project->update($validated);

        return $project->load(['company', 'contacts', 'tags']);
    }

    public function storeTag(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags',
            'color' => 'required|string|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        return Tag::create($validated);
    }

    public function updateProjectStage(Request $request, Project $project)
    {
        $validated = $request->validate([
            'stage' => 'required|in:' . implode(',', array_keys(Project::STAGES)),
            'position' => 'required|integer',
        ]);

        $project->update($validated);

        return $project->load(['company', 'contacts']);
    }

    public function deleteProject(Project $project)
    {
        $project->delete();
        return response()->json(['success' => true]);
    }
}