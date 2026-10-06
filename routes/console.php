<?php

use Illuminate\Support\Facades\Schedule;

/*
| Hébergement mutualisé (alwaysdata…) : sans processus permanent, une seule
| tâche planifiée « php artisan schedule:run » chaque minute suffit à vider la
| file d'attente (emails de livraison). Sur un serveur avec Supervisor, on peut
| à la place lancer « php artisan queue:work » en continu.
*/
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

// Purge des jobs échoués de plus de 30 jours.
Schedule::command('queue:prune-failed --hours=720')->daily();
