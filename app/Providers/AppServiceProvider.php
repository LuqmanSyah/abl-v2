<?php

namespace App\Providers;

use App\Channels\WhatsAppChannel;
use App\Exceptions\BusinessRuleException;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use NotificationChannels\WebPush\WebPushChannel;
use Throwable;

use function Livewire\on;

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
        $this->app->make(ChannelManager::class)->extend('webpush', fn ($app): WebPushChannel => $app->make(WebPushChannel::class));
        $this->app->make(ChannelManager::class)->extend('wa', fn ($app): WhatsAppChannel => $app->make(WhatsAppChannel::class));

        on('exception', function (mixed $component, Throwable $exception, Closure $stopPropagation): void {
            if (! $component instanceof Page || ! $exception instanceof BusinessRuleException) {
                return;
            }

            Notification::make()
                ->title('Tindakan tidak dapat diproses')
                ->body($exception->getMessage())
                ->warning()
                ->send();

            $stopPropagation();
        });

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
