<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OnboardingMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $nombre;
    public array $pack;

    public function __construct(string $nombre, array $pack)
    {
        $this->nombre = $nombre;
        $this->pack   = $pack;
    }

    public function build()
    {
        return $this
            ->subject($this->pack['subject'] ?? 'Onboarding Babyplant')
            ->view('emails.onboarding')
            ->with([
                'nombre' => $this->nombre,
                'intro'  => $this->pack['intro'] ?? '',
                'items'  => $this->pack['items'] ?? [],
            ]);
    }
}
