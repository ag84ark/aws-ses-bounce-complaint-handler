<?php

namespace ag84ark\AwsSesBounceComplaintHandler\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail.
 *
 * @property int                             $id
 * @property string                          $email
 * @property string                          $problem_type
 * @property string                          $problem_subtype
 * @property int                             $repeated_attempts
 * @property int                             $ignore
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail active()
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail bounced()
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail complained()
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail query()
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail whereIgnore($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail whereProblemSubtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail whereProblemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail whereRepeatedAttempts($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\ag84ark\AwsSesBounceComplaintHandler\Models\WrongEmail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class WrongEmail extends Model
{
    protected $table = 'wrong_emails';

    protected $fillable = ['email', 'problem_type', 'problem_subtype', 'repeated_attempts'];

    public function unsubscribed(): bool
    {
        return 'Complaint' === $this->problem_type;
    }

    public function dontSend(): bool
    {
        return ('Bounce' === $this->problem_type && 'Permanent' === $this->problem_subtype) || 'Complaint' === $this->problem_type;
    }

    public function canBouncedSend(): bool
    {
        return 'Bounce' === $this->problem_type
            && 'Permanent' !== $this->problem_subtype
            && $this->updated_at->diffInMinutes(config('aws-ses-bounce-complaint-handler.block_bounced_transient_for_minutes'));
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param Builder $query
     */
    public function scopeBounced($query): Builder
    {
        return $query->where('problem_type', '=', 'Bounce');
    }

    /**
     * Scope a query to only include popular users.
     *
     * @param Builder $query
     */
    public function scopeComplained($query): Builder
    {
        return $query->where('problem_type', '=', 'Complaint');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('ignore', '=', false);
    }
}
