<?php

namespace App\Mail;

use App\Models\RequestPoint;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Carbon\Carbon;

class VoteRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public RequestPoint $requestPoint;
    public $visitTime; // ← добавили свойство для времени посещения

    public function __construct(RequestPoint $requestPoint)
    {
        $this->requestPoint = $requestPoint;
        $this->visitTime = $this->calculateVisitTime(); // ← вычисляем время
    }

    /**
     * Вычисляем время посещения для конкретной точки
     */
    protected function calculateVisitTime()
    {
        $approvalRequest = $this->requestPoint->request;
        
        // Время старта маршрута (дата + время начала)
        $startDateTime = Carbon::parse($approvalRequest->proposed_date)->setTimeFromTimeString($approvalRequest->start_time);
        
        // Получаем ВСЕ точки маршрута в том же порядке, что и на фронте
        // Сортируем по ID (если порядок не сохраняется, можно добавить поле sort_order)
        $points = $approvalRequest->route->point->sortBy('id');
        
        $cumulativeMinutes = 0;
        
        foreach ($points as $point) {
            if ($point->id == $this->requestPoint->point_id) {
                break; // дошли до нужной точки — дальше не считаем
            }
            // Добавляем длительность предыдущих точек (по умолчанию 30 минут)
            $cumulativeMinutes += $point->duration_minutes ?? 30;
        }
        
        // Итоговое время = время старта + сумма длительностей предыдущих точек
        $visitDateTime = $startDateTime->copy()->addMinutes($cumulativeMinutes);
        
        return $visitDateTime->format('H:i');
    }

    public function build()
    {
        return $this->subject('Запрос на согласование маршрута')
                    ->view('emails.vote_request')
                    ->with([
                        'visitTime' => $this->visitTime, // ← передаём время в шаблон
                    ]);
    }
}