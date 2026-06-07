<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case Scheduled = 'scheduled';
    case Completed = 'completed';
    case NoShow = 'no_show';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Scheduled => 'Agendado',
            self::Completed => 'Concluído',
            self::NoShow => 'Faltou',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $status) => $status->value, self::cases());
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return array_combine(
            self::values(),
            array_map(static fn (self $status) => $status->label(), self::cases()),
        );
    }
}
