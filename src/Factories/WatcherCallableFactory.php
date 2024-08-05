<?php

declare(strict_types=1);

namespace Ngmy\LaravelAop\Factories;

use Illuminate\Console\Command;
use Illuminate\Console\View\Components\Factory;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\Watcher\Watch;

/**
 * @phpstan-type WatchEventType Watch::EVENT_TYPE_FILE_CREATED|Watch::EVENT_TYPE_FILE_DELETED|Watch::EVENT_TYPE_FILE_UPDATED
 */
final class WatcherCallableFactory
{
    /**
     * The watcher callable type for handling any change event.
     *
     * @var string
     */
    public const WATCHER_CALLABLE_TYPE_ON_ANY_CHANGE = 'onAnyChange';

    /**
     * The watcher callable type for checking if the watcher should continue.
     *
     * @var string
     */
    public const WATCHER_CALLABLE_TYPE_SHOULD_CONTINUE = 'shouldContinue';

    /**
     * The number of times to run the watcher. This is used only for testing.
     */
    private int $i = 0;

    /**
     * Create a new instance.
     *
     * @param ExceptionHandler $exceptionHandler The exception handler
     * @param Command          $command          The command
     * @param Factory          $viewFactory      The view factory
     */
    public function __construct(
        private readonly ExceptionHandler $exceptionHandler,
        private readonly Command $command,
        private readonly Factory $viewFactory,
    ) {}

    /**
     * Create a watcher callable from the type.
     *
     * @param self::WATCHER_CALLABLE_TYPE_* $type The watcher callable type
     */
    public function fromType(string $type): \Closure
    {
        return match ($type) {
            self::WATCHER_CALLABLE_TYPE_ON_ANY_CHANGE => $this->onAnyChangeCallable(...),
            self::WATCHER_CALLABLE_TYPE_SHOULD_CONTINUE => $this->shouldContinueCallable(...),
        };
    }

    /**
     * Handle any change event.
     *
     * @param Watch::EVENT_TYPE_* $type The event type
     * @param string              $path The file path
     */
    private function onAnyChangeCallable(string $type, string $path): void
    {
        try {
            if (!\in_array($type, [
                Watch::EVENT_TYPE_FILE_CREATED,
                Watch::EVENT_TYPE_FILE_DELETED,
                Watch::EVENT_TYPE_FILE_UPDATED,
            ], true)) {
                return;
            }

            if (App::runningUnitTests()) {
                Log::info($this->getMessageForEvent($type, $path));
            }

            $this->viewFactory->info($this->getMessageForEvent($type, $path));
            $this->viewFactory->info('Running the dump-autoload Composer command and compiling the AOP classes...');

            Artisan::call('aop:compile');

            $this->viewFactory->info('Ran the dump-autoload Composer command and compiled the AOP classes.');

            if (App::runningUnitTests()) {
                match ($type) {
                    Watch::EVENT_TYPE_FILE_UPDATED => throw new \Exception('Test exception'),
                    default => null,
                };
            }
        } catch (\Throwable $e) {
            $this->exceptionHandler->report($e);
            $this->exceptionHandler->renderForConsole($this->command->getOutput(), $e);
        }
    }

    /**
     * Check if the watcher should continue.
     */
    private function shouldContinueCallable(): bool
    {
        if (App::runningUnitTests()) {
            $result = match ($this->i) {
                5 => File::put(app_path('test.php'), '<?php'),
                10 => File::put(app_path('test.php'), '<?php echo "test";'),
                15 => File::delete(app_path('test.php')),
                20 => File::makeDirectory(app_path('test')),
                25 => File::deleteDirectory(app_path('test')),
                30 => false,
                default => true,
            };

            if (!$result) {
                return false;
            }

            ++$this->i;
        }

        return true;
    }

    /**
     * Get the message for the event.
     *
     * @param WatchEventType $type The event type
     * @param string         $path The file path
     *
     * @return string The message
     */
    private function getMessageForEvent(string $type, string $path): string
    {
        $message = match ($type) {
            Watch::EVENT_TYPE_FILE_CREATED => 'File created',
            Watch::EVENT_TYPE_FILE_DELETED => 'File deleted',
            Watch::EVENT_TYPE_FILE_UPDATED => 'File updated',
        };

        return \sprintf('%s [%s].', $message, $path);
    }
}
