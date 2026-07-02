<?php

declare(strict_types=1);

namespace App\Enums;

enum RecurrenceType: string
{
    case None = 'none';
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Biweekly = 'biweekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    /**
     * Get human-readable label for the recurrence type.
     */
    public function label(): string
    {
        return match ($this) {
            self::None => 'None',
            self::Daily => 'Daily',
            self::Weekly => 'Weekly',
            self::Biweekly => 'Every 2 Weeks',
            self::Monthly => 'Monthly',
            self::Yearly => 'Yearly',
        };
    }

    /**
     * Get all options as label => value array.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            if ($case !== self::None) {
                $options[$case->value] = $case->label();
            }
        }
        return $options;
    }

    /**
     * Calculate the next occurrence date.
     */
    public function getNextOccurrence(\Carbon\Carbon $currentDate, int $interval = 1): \Carbon\Carbon
    {
        return match ($this) {
            self::Daily => $currentDate->copy()->addDays($interval),
            self::Weekly => $currentDate->copy()->addWeeks($interval),
            self::Biweekly => $currentDate->copy()->addWeeks(2 * $interval),
            self::Monthly => $currentDate->copy()->addMonths($interval),
            self::Yearly => $currentDate->copy()->addYears($interval),
            default => $currentDate->copy(),
        };
    }
}
