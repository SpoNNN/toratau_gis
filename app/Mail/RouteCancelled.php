<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ApprovalRequest;
use App\Models\Point;

class RouteCancelled extends Mailable
{
    use Queueable, SerializesModels;

    public $approvalRequest;
    public $point;

    public function __construct(ApprovalRequest $approvalRequest, Point $point)
    {
        $this->approvalRequest = $approvalRequest;
        $this->point = $point;
    }

    public function build()
    {
        return $this->subject('Отмена согласования маршрута')
                    ->view('emails.route-cancelled')
                    ->with([
                        'routeTitle' => $this->approvalRequest->route->title,
                        'proposedDate' => $this->approvalRequest->proposed_date,
                        'pointName' => $this->point->name,
                    ]);
    }
}