<?php

return [
    'missing_punch' => [
        'enabled' => (bool) env('MISSING_PUNCH_REMINDER_ENABLED', true),
        'schedule_time' => env('MISSING_PUNCH_REMINDER_TIME', '09:00'),
        'default_workdays' => [1, 2, 3, 4, 5],
        'workdays_by_email' => [
            'diegojimenez291995@gmail.com' => [7, 1, 2, 3],
        ],
        'message_template' => env(
            'MISSING_PUNCH_REMINDER_TEMPLATE',
            'Hola {nombre}, ayer ({fecha}) no aparece ningun fichaje tuyo. Si corresponde, revisalo en la app. fichajes.babyplant.es'
        ),

        // Dias festivos fijos (YYYY-MM-DD, separados por coma).
        'holidays' => env('MISSING_PUNCH_HOLIDAYS', ''),

        // Deteccion opcional de festivos en tabla externa.
        'holiday_connection' => env('MISSING_PUNCH_HOLIDAY_CONNECTION', 'mysql_polifonia'),
        'holiday_tables' => array_values(array_filter(array_map('trim', explode(',', env('MISSING_PUNCH_HOLIDAY_TABLES', 'festivos'))))),
        'holiday_date_columns' => array_values(array_filter(array_map('trim', explode(',', env('MISSING_PUNCH_HOLIDAY_DATE_COLUMNS', 'fecha,date,dia'))))),
        'omit_emails' => array_values(array_filter(array_map('trim', explode(',', env('MISSING_PUNCH_OMIT_EMAILS', 'c.anton@babyplant.es,d.anton@babyplant.es,a.garcia@babyplant.es,m.anton@babyplant.es,tecnico@babyplant.es,casablanca21@gmail.com,chalaouiloukmane@gmail.com,bodacostel@gmail.com,elhadaouihassan2000@gmail.com,raquelbabyplant@gmail.com'))))),
    ],
];



