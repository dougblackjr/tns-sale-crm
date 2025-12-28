<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'value',
        'stage',
        'notes',
        'position',
        'won_at',
        'lost_at',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
    ];

    public const STAGES = [
        'lead' => 'Lead',
        'qualified' => 'Qualified',
        'proposal' => 'Proposal',
        'negotiation' => 'Negotiation',
        'won' => 'Won',
        'lost' => 'Lost',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class);
    }

    protected static function booted(): void
    {
        static::updating(function (Project $project) {
            if ($project->isDirty('stage')) {
                if ($project->stage === 'won' && !$project->won_at) {
                    $project->won_at = now();
                } elseif ($project->stage === 'lost' && !$project->lost_at) {
                    $project->lost_at = now();
                }
            }
        });
    }
}