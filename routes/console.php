<?php

use App\Console\Commands\DownloadMaxMindDatabase;

// Schedule MaxMind GeoLite2-Country database download every week
Schedule::command(DownloadMaxMindDatabase::class)->weekly();
