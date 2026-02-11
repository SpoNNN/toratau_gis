<?php

namespace App\Http\Controllers;

use App\Models\routeOrder;
use App\Models\Route;
use App\Models\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RouteOrderController extends Controller
{

    public function getEventsByRoute($routeId)
    {
        try {
            $events = routeOrder::where('route_id', $routeId)
                ->where('date', '>=', now()->startOfDay())
                ->orderBy('date', 'asc')
                ->with(['route', 'route.point'])
                ->get()
                ->map(function ($event) {
                    $route = $event->route;
                  
                    $firstPoint = $route ? $route->point()
                        ->orderBy('pointName', 'asc')
                        ->first() : null;
                    
                    $location = $firstPoint && $firstPoint->address 
                        ? $firstPoint->address 
                        : '';
                   $desc = $firstPoint && $firstPoint->name 
                        ? $firstPoint->name 
                        : '';
                    
                    return [
                        'id' => $event->id,
                        'date' => Carbon::parse($event->date)->format('Y-m-d'),
                        'title' => $route ? $route->title : 'Маршрут',
                        'startTime' => Carbon::parse($event->date)->format('H:i'),
                        'bookedSeats' => $event->booked_users ?? 0,
                        'totalSeats' => $event->max_users,
                        'location' => $location,
                        'description' => $desc ,
                        'routeId' => $event->route_id
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения событий: ' . $e->getMessage()
            ], 500);
        }
    }


    public function createBooking(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'eventId' => 'required|exists:routeOrder,id',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'seats' => 'required|integer|min:1|max:10',
            'phone' => 'required|string|regex:/^[0-9+\-\s()]+$/',
            'email' => 'required|email|max:255',
        ], [
            'eventId.required' => 'Не указано событие',
            'eventId.exists' => 'Событие не найдено',
            'firstName.required' => 'Введите имя',
            'lastName.required' => 'Введите фамилию',
            'seats.required' => 'Укажите количество мест',
            'seats.min' => 'Минимум 1 место',
            'seats.max' => 'Максимум 10 мест',
            'phone.required' => 'Введите телефон',
            'phone.regex' => 'Некорректный формат телефона',
            'email.required' => 'Введите email',
            'email.email' => 'Некорректный email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $event = routeOrder::findOrFail($request->eventId);
            
            // Проверка доступности мест
            $currentBooked = $event->booked_users ?? 0;
            $availableSeats = $event->max_users - $currentBooked;
            
            if ($request->seats > $availableSeats) {
                return response()->json([
                    'success' => false,
                    'message' => "Недостаточно свободных мест. Доступно: {$availableSeats}"
                ], 400);
            }

           
            $event->booked_users = $currentBooked + $request->seats;
            $event->save();

            DB::commit();

            // Здесь можно отправить email уведомление
            // Mail::to($request->email)->send(new BookingConfirmation([
            //     'firstName' => $request->firstName,
            //     'lastName' => $request->lastName,
            //     'seats' => $request->seats,
            //     'eventDate' => $event->date,
            // ]));

            return response()->json([
                'success' => true,
                'message' => 'Бронирование успешно создано',
                'data' => [
                    'eventId' => $event->id,
                    'bookedSeats' => $event->booked_users,
                    'totalSeats' => $event->max_users
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка создания бронирования: ' . $e->getMessage()
            ], 500);
        }
    }


    public function getEvent($eventId)
    {
        try {
            $event = routeOrder::with(['route', 'route.point'])->findOrFail($eventId);
            $route = $event->route;

          
            $firstPoint = $route ? $route->point()
                ->orderBy('pointName', 'asc')
                ->first() : null;
            
            $location = $firstPoint && $firstPoint->address 
                ? $firstPoint->address 
                : '';

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $event->id,
                    'date' => Carbon::parse($event->date)->format('Y-m-d'),
                    'title' => $route ? $route->title : 'Маршрут',
                    'startTime' => Carbon::parse($event->date)->format('H:i'),
                    'bookedSeats' => $event->booked_users ?? 0,
                    'totalSeats' => $event->max_users,
                    'location' => $location,
                    'description' => 'Кампус Евразийского МОЦ#6',
                    'routeId' => $event->route_id
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Событие не найдено'
            ], 404);
        }
    }
}