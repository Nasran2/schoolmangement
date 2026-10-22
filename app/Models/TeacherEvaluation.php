<?php

namespace App\Models;

use App\Services\TeacherEvaluations\EvaluationForm;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherEvaluation extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'appraisal_year' => 'integer', 'review_date' => 'date', 'event_date' => 'date',
        'period_start' => 'date', 'period_end' => 'date', 'teacher_signed_date' => 'date',
        'evaluator_signed_date' => 'date', 'follow_up_completed_date' => 'date',
        'supervisors' => 'array', 'criteria' => 'array', 'evidence' => 'array', 'development' => 'array',
        'score' => 'float', 'completed_at' => 'datetime', 'version' => 'integer',
    ];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'annual' ? 'Annual appraisal' : 'Event evidence and development';
    }

    public function getBandAttribute(): ?string
    {
        return EvaluationForm::band($this->score);
    }

    public function getFollowUpDueAttribute(): ?string
    {
        if ($this->follow_up_completed_date) {
            return null;
        }
        $dates = collect($this->evidence ?? [])->pluck('review_date')
            ->merge(collect($this->development ?? [])->pluck('target_date'))->filter()->sort();

        return $dates->first();
    }
}
