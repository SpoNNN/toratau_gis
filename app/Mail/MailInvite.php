<?php

namespace App\Mail;

use App\Models\Booking;
use App\Models\routeOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class MailInvite extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public routeOrder $event;
    public $eventDate;
    public $eventTime;

    public function __construct(Booking $booking, routeOrder $event)
    {
        $this->booking = $booking;
        $this->event = $event;
        $this->eventDate = Carbon::parse($event->date)->format('d.m.Y');
        $this->eventTime = Carbon::parse($event->date)->format('H:i');
    }

    public function build()
    {
        return $this->subject('Напоминание о записи на научно-образовательный маршрут')
                    ->view('emails.request_mail')
                    ->with([
                        'eventDate' => $this->eventDate,
                        'eventTime' => $this->eventTime,
                        'booking' => $this->booking,
                        'event' => $this->event,
                    ]);
    }
}