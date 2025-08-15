<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Domain;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class AddCurrentDomain extends Widget
{
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.admin.widgets.add-current-domain';

    public bool $isVisible = true;

    public static function canView(): bool
    {
        return request()->user()->can('create domain') && ! Domain::exists();
    }

    public function create(): void
    {
        $request = request();
        $host = $request->getHttpHost();
        $protocol = $request->getScheme();
        $domain = Domain::firstOrCreate([
            'host' => $host,
            'protocol' => $protocol,
        ], [
            'is_active' => true,
            'is_admin_panel_active' => true,
        ]);

        $this->isVisible = false;

        Notification::make()
            ->title('Domain Added Successfully')
            ->body("The domain <strong>{$domain->__toString()}</strong> has been added and activated.")
            ->success()
            ->send();
    }
}
