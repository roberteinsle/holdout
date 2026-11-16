<?php

namespace App\Models;

use App\Enums\MemberType;
use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'target_days', 'eat_soon_days'])]
class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'target_days' => 'integer',
            'eat_soon_days' => 'integer',
        ];
    }

    /** @return HasMany<User, $this> */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<Invitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    /** @return HasMany<Member, $this> */
    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function memberCount(MemberType $type): int
    {
        return (int) $this->members->firstWhere('type', $type)?->count;
    }

    public function setMemberCount(MemberType $type, int $count): void
    {
        $this->members()->updateOrCreate(['type' => $type], ['count' => $count]);
        $this->unsetRelation('members');
    }

    /** Total number of people the supply is planned for. */
    public function memberTotal(): int
    {
        return (int) $this->members->sum('count');
    }
}
