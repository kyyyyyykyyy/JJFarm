<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// Tambahkan "implements ShouldBroadcastNow" biar datanya langsung terkirim saat itu juga
class TimbanganUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ternak;
    public $telemetry;

    public function __construct($ternak, $telemetry)
    {
        $this->ternak = $ternak;
        $this->telemetry = $telemetry;
    }

    public function broadcastOn(): array
    {
        // Channel ini yang nanti didengerin sama Vue.js lu
        return [
            new Channel('timbangan-channel'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'TimbanganBaruMasuk';
    }
}