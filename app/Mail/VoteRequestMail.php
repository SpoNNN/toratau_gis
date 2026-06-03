<?php

namespace App\Mail;

use App\Models\RequestPoint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VoteRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public RequestPoint $requestPoint;

    public function __construct(RequestPoint $requestPoint)
    {
        $this->requestPoint = $requestPoint;
    }

    public function build()
    {
        return $this->subject('Запрос на согласование маршрута')
                    ->view('emails.vote_request');
    }
}