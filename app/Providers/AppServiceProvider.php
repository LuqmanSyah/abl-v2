<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Action::configureUsing(fn (Action $action) => $action
            ->modalAlignment(Alignment::Start)
            ->modalFooterActionsAlignment(Alignment::End)
            ->modalWidth(Width::Large)
            ->stickyModalHeader()
            ->stickyModalFooter());

        CreateAction::configureUsing(fn (CreateAction $action) => $action->modalWidth(Width::TwoExtraLarge));
        EditAction::configureUsing(fn (EditAction $action) => $action->modalWidth(Width::TwoExtraLarge));
        DeleteAction::configureUsing(fn (DeleteAction $action) => $action->modalWidth(Width::Medium));
        DeleteBulkAction::configureUsing(fn (DeleteBulkAction $action) => $action->modalWidth(Width::Medium));
    }
}
