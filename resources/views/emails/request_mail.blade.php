<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 6px; 
               text-decoration: none; font-weight: bold; margin: 8px; }
        .btn-confirm { background: #22c55e; color: white; }
        .btn-reject  { background: #ef4444; color: white; }
        .info { background: #f3f4f6; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
        .visit-time { color: #22c55e; font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Напоминание о записи на маршрут</h2>

    <div class="info">
        <p><strong>Вы зарегестрированы на маршрут:</strong> {{ $event->route->title }}</p>
        <p><strong>Дата:</strong> {{ $eventDate  }}</p>
        <p><strong>Время начала маршрута:</strong> {{ $eventTime }}</p>
        <p><strong>Встреча группы состоится по адресу:</strong> {{  $event->route->point->first()->address }}</p>
        <p>Отменить запись можно в Личном кабинете пользователя в системе Научно-образовательных маршрутов по г. Уфе</p>
    </div>

  
</body>
</html>