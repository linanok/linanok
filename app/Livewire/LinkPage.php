<?php

namespace App\Livewire;

use App\Models\Link;
use App\Services\VisitService;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Livewire\Component;

class LinkPage extends Component implements HasForms
{
    use InteractsWithForms, WithRateLimiting;

    public Link $link;

    /** @var array<string, string> */
    public array $data = [];

    public function mount(?string $short_path): Redirector|RedirectResponse|null
    {
        $this->link = Link::available()
            ->where('short_path', $short_path)
            ->forCurrentDomain()
            ->firstOrFail();

        if (! $this->link->has_password) {
            return VisitService::redirectToOriginalUrl($this->link);
        }

        return null;
    }

    public function render(): mixed
    {
        if (! $this->link->has_password) {
            return null;
        }

        return view('livewire.password-protected-link-page')
            ->layoutData([
                'title' => 'Password Protected Link',
            ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('password')
                    ->label('Please enter the password to access this link')
                    ->placeholder('******')
                    ->password()
                    ->revealable(),
            ])
            ->statePath('data');
    }

    public function submit(): RedirectResponse|null|Redirector
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $e) {
            Notification::make()
                ->title('Too many attempts.')
                ->body(array_key_exists('body', __('filament-panels::pages/auth/password-reset/reset-password.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/password-reset/reset-password.notifications.throttled.body', [
                    'seconds' => $e->secondsUntilAvailable,
                    'minutes' => $e->minutesUntilAvailable,
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $password = $this->data['password'] ?? null;

        if (empty($password)) {
            Notification::make()
                ->title('Password is required')
                ->warning()
                ->send();

            return null;
        }

        if ($password !== $this->link->password) {
            Notification::make()
                ->title('Password is wrong')
                ->danger()
                ->send();

            return null;
        }

        return VisitService::redirectToOriginalUrl($this->link);
    }
}
