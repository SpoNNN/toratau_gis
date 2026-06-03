<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\ApprovalRequest;
use App\Models\Point;

class VoteInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public $approvalRequest;
    public $point;
    public $token;

    public function __construct(ApprovalRequest $approvalRequest, Point $point, $token)
    {
        $this->approvalRequest = $approvalRequest;
        $this->point = $point;
        $this->token = $token;
    }

    public function build()
    {
        $url = url('/vote/' . $this->token);
        
        return $this->subject('Голосование за согласование маршрута')
                    ->view('emails.vote-invitation')
                    ->with([
                        'url' => $url,
                        'deadline' => $this->approvalRequest->deadline,
                        'routeTitle' => $this->approvalRequest->route->title,
                        'proposedDate' => $this->approvalRequest->proposed_date,
                        'pointName' => $this->point->name,
                    ]);
    }
}