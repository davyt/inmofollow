<?php

namespace App\Models;

use App\Models\Concerns\HasDefaultCompany;
use App\Models\Concerns\LogsBasicActivity;
use App\Models\Concerns\LogsLeadCriticalChanges;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasDefaultCompany, LogsBasicActivity, LogsLeadCriticalChanges;
    
    protected $guarded = [];

    protected $casts = [
        'whatsapp_consent' => 'boolean',
        'email_consent' => 'boolean',
        'do_not_contact' => 'boolean',
        'last_contacted_at'  => 'datetime',
        'last_wa_inbound_at' => 'datetime',
        'next_follow_up_at'  => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leadStatus()
    {
        return $this->belongsTo(LeadStatus::class);
    }

    public function notes()
    {
        return $this->hasMany(LeadNote::class);
    }

    public function scheduledMessages()
    {
        return $this->hasMany(ScheduledMessage::class);
    }

    public function waInboundMessages()
    {
        return $this->hasMany(WaInboundMessage::class);
    }

    public function hasActiveWhatsAppSession(): bool
    {
        return $this->last_wa_inbound_at !== null
            && $this->last_wa_inbound_at->gt(now()->subHours(24));
    }

    public static function findByWhatsAppPhone(string $waPhone): ?static
    {
        $local = strlen($waPhone) >= 11 && str_starts_with($waPhone, '598')
            ? '0' . substr($waPhone, 3)
            : null;

        return static::where('phone', $waPhone)
            ->orWhere('phone', '+' . $waPhone)
            ->when($local, fn ($q) => $q->orWhere('phone', $local))
            ->first();
    }

    /**
     * Reduce un teléfono a su forma canónica (solo dígitos, sin 0 local ni código de país 598)
     * para poder comparar "099699427", "+59899090071" y "598 99090071" como el mismo número.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '598') && strlen($digits) >= 11) {
            $digits = substr($digits, 3);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 9) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }
}