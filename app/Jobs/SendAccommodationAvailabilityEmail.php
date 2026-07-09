<?php

namespace App\Jobs;

use App\Models\Accommodation;
use App\Services\MailjetService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class SendAccommodationAvailabilityEmail implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public $uniqueFor = 86400;

    public function __construct(
        public string $email,
        public array $accommodationIds
    ) {
    }

    public function uniqueId(): string
    {
        return 'accommodation-email-group-' . md5(strtolower(trim($this->email)));
    }

    public function handle(MailjetService $mailjetService): void
    {
        if (empty(trim($this->email))) {
            Log::warning('SendAccommodationAvailabilityEmail dispatched with empty email', [
                'accommodation_ids' => $this->accommodationIds,
            ]);
            return;
        }

        $accommodations = Accommodation::whereIn('id', $this->accommodationIds)
            ->get()
            ->filter(fn (Accommodation $a) => !($a->email_sent_at && $a->email_sent_at->isToday()));

        if ($accommodations->isEmpty()) {
            Log::info("All accommodations in group {$this->email} already received an email today, skipping");
            return;
        }

        $payload = $accommodations->map(function (Accommodation $accommodation) {
            return [
                'name' => $accommodation->name,
                'available_url' => URL::temporarySignedRoute('accommodation.response', now()->addDays(7), [
                    'accommodation' => $accommodation->id,
                    'available' => 1,
                ]),
                'not_available_url' => URL::temporarySignedRoute('accommodation.response', now()->addDays(7), [
                    'accommodation' => $accommodation->id,
                    'available' => 0,
                ]),
            ];
        })->values()->all();

        $result = $mailjetService->sendBatchedAvailabilityRequest($this->email, $payload);

        if ($result['success']) {
            foreach ($accommodations as $accommodation) {
                $accommodation->markEmailSent();
            }
            Log::info("Availability email sent to {$this->email}", [
                'accommodation_ids' => $accommodations->pluck('id')->all(),
                'count' => $accommodations->count(),
            ]);
        } else {
            Log::error("Failed to send availability email to {$this->email}", [
                'accommodation_ids' => $accommodations->pluck('id')->all(),
                'error' => $result['error'],
            ]);
        }
    }
}
