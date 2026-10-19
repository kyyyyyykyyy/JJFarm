<?php

namespace App\Events;

use App\Models\Ternak;
use App\Models\RiwayatTimbang;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Broadcast instan tanpa queue worker
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TelemetriBaruEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ternak;
    public $riwayat;

    public function __construct(Ternak $ternak, RiwayatTimbang $riwayat)
    {
        $this->ternak = $ternak;
        $this->riwayat = $riwayat;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('telemetri-kandang'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'data.masuk';
    }
}