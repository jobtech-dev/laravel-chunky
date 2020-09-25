<?php

namespace Jobtech\LaravelChunky\Concerns;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\OutputStyle;

trait ManagerHelpers
{
    /**
     * This method checks if the application is running in console and, if output style is not null, it creates a new
     * progress bar instance, otherwise returns null.
     *
     * @param \Symfony\Component\Console\Style\OutputStyle|null $output
     * @param int $count
     *
     * @return \Symfony\Component\Console\Helper\ProgressBar|null
     */
    public function hasProgressBar(?OutputStyle $output, int $count): ?ProgressBar
    {
        if ($output !== null && app()->runningInConsole()) {
            return $output->createProgressBar($count);
        }

        return null;
    }
}
