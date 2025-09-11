<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class HandlerCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('handler')
            ->setDescription('Use a signal handler to demonstrate a Symfony Ctrl-C handling issue.')
            ->setDefinitions()
            ->setHelp(
                <<<EOT
The <info>handler</info> command uses a signal handler to demonstrate a Ctrl-C issue in
Symfony QuestionHelper when selecting input from a list:

<info>php sigbug handler</info>

The handler reports receiving a Ctrl-C signal then exits, which unfortunately
leaves the terminal in a broken state (use Ctrl-Break then Enter to restart it).

Ctrl-C will work normally if the signal handler does not call exit, so the
program continues, or when using the other QuestionHelper methods:

<info>php sigbug handler --no-exit</info>
<info>php sigbug handler --info</info>
<info>php sigbug handler --confirm</info>

The <info>--default</info> option is included to complement the <info>restart</info> command, where the
issue is triggered when SIGINT is set to be ignored (SIG_IGN) in the parent
process then set to its default action (SIG_DFL), which is exiting, in the
restarted (child) process because it inherited the ignored state.

Here it can be used to examine calling a new process from a parent with SIGINT
ignored, by using the `trap` command:

<info>trap "" SIGINT; php -r "passthru(PHP_BINARY.' sigbug handler --default');"; trap - SIGINT;</info>`

Using Ctrl-C will break the terminal, but only if PHP is the parent process. For
example, Ctrl-C will work normally when using:

<info>trap "" SIGINT; php sigbug handler --default; trap - SIGINT;</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->hasSignalSupport()) {
            $output->writeln('<error>Signal handling not supported</error>');
            return Command::FAILURE;
        }

        if ($input->getOption('default') && $input->getOption('no-exit')) {
            $output->writeln('<error>Option --default cannot be used with --no-exit</error>');
            return Command::FAILURE;
        }

        if ($input->getOption('default')) {
             $this->setDefaultHandler();
        } else  {
            $this->setHandler($input, $output);
        }

        $output->writeln($this->getMessage($input, $this->getMode($input)));

        return parent::execute($input, $output);
    }

    protected function getDefinitions(): array
    {
        return [
            new InputOption(
                'no-exit',
                null,
                InputOption::VALUE_NONE,
                'Do not call exit from the handler. The terminal remains intact.'
            ),
            new InputOption(
                'default',
                null,
                InputOption::VALUE_NONE,
                'Set the default handler. Ctrl-C might break the terminal. See below for details.'
            ),
        ];
    }

    private function setHandler(InputInterface $input, OutputInterface $output): void
    {
        $noexit = (bool) $input->getOption('no-exit');

        $handler = function($signal) use ($output, $noexit) {
            $name = defined('PHP_WINDOWS_VERSION_BUILD') ? 'CTRL' : 'CTRL-C';
            $message = $name.' EVENT RECEIVED';

            if ($noexit) {
                $output->writeln($message.' - continuing');
            } else {
                $output->writeln($message);
                $output->writeln('Exiting with code: 2');
                exit(2);
            }
        };

        if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, $handler);
            $output->writeln('Installed SIGINT signal handler');
        }

        if (function_exists('sapi_windows_set_ctrl_handler')) {
            sapi_windows_set_ctrl_handler(function ($event) use ($handler) {
                $handler($event);
            });

            $output->writeln('Installed Ctrl events signal handler');
        }
    }

    private function hasSignalSupport(): bool
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return function_exists('sapi_windows_set_ctrl_handler');
        }

        return function_exists('pcntl_async_signals') && function_exists('pcntl_signal');
    }

    private function setDefaultHandler(): void
    {
        if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, SIG_DFL);
        }
    }

    private function getMessage(InputInterface $input, int $mode): string
    {
        $message = null;

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $message = 'This bug does not occur on Windows.';
            return $this->formatMessage($message);
        }

        if ($mode === self::MODE_SELECT) {
            if ($input->getOption('no-exit')) {
                $message = 'This bug does not occur when the handler does not exit.';
            }
            if ($input->getOption('default')) {
                $message = 'Depending how this command has been called, Ctrl-C may break the terminal';
                return sprintf('<info>%s</info>', $message);
            }

        } else {
            $message = 'This bug only occurs when selecting input from a list.';
        }

        return $this->formatMessage($message);
    }
}
