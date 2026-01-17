<?php

namespace App\Domain\Enum;

enum BookPurchaseStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    
    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Oczekujące',
            self::COMPLETED => 'Zakończone',
            self::CANCELLED => 'Anulowane',
        };
    }
    
    public function getBadgeClass(): string
    {
        return match($this) {
            self::PENDING => 'badge-warning',
            self::COMPLETED => 'badge-success',
            self::CANCELLED => 'badge-danger',
        };
    }
    
    public static function getValidStatuses(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
    
    public static function getDefault(): self
    {
        return self::PENDING;
    }
    
    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::PENDING => in_array($newStatus, [self::COMPLETED, self::CANCELLED]),
            self::COMPLETED => false, // Zakończone nie można zmienić
            self::CANCELLED => false, // Anulowane nie można zmienić
        };
    }
}
