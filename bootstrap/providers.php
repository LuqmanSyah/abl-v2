<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\EmployeePanelProvider;
use App\Providers\Filament\HrPanelProvider;
use App\Providers\Filament\ManagerPanelProvider;

return [
    AppServiceProvider::class,
    EmployeePanelProvider::class,
    ManagerPanelProvider::class,
    HrPanelProvider::class,
];
