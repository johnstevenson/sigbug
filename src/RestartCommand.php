<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RestartCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('restart')
             ->setDescription('Use a process restart to demonstrate a Symfony Ctrl-C handling issue.')
            ->setDefinitions()
            ->setHelp(
                <<<EOT
The <info>restart</info> command uses a process restart to demonstrate a Ctrl-C issue in
Symfony QuestionHelper when selecting input from a list (ChoiceQuestion):

<info>php sigbug restart</info>

Using Ctrl-C unfortunately leaves the terminal in a broken state. Use Ctrl-Break
then Enter to restart it.

Ctrl-C will work normally if there is no restart, or when using the other
QuestionHelper methods:

<info>php sigbug restart --none</info>
<info>php sigbug restart --info</info>
<info>php sigbug restart --confirm</info>

The issue is triggered when SIGINT is set to be ignored (SIG_IGN) in the parent
process, then set to its default action (SIG_DFL) in the child process because
it inherited the ignored state.

When there is no restart, SIGINT remains in its initial state - which is the
default action.

xdebug-handler is used for the restart, regardless of whether Xdebug is loaded
or not.

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln($this->getMessage($input, $this->getMode($input)));

        return parent::execute($input, $output);
    }

    protected function getDefinitions(): array
    {
        return [
            new InputOption(
                'none',
                null,
                InputOption::VALUE_NONE,
                'Do not restart the process. Ctrl-C will not break the terminal.'
            ),
            new InputOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Show restart status messages if restarting.'
            ),
        ];
    }

    private function getMessage(InputInterface $input, int $mode): string
    {
        $message = null;

        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $message = 'This bug does not occur on Windows.';
            return $this->formatMessage($message);
        }

        if ($mode === self::MODE_SELECT) {
            if ($input->getOption('none')) {
                $message = 'This bug only occurs in a restart.';
            }
        } else {
            $message = 'This bug only occurs when selecting input from a list.';
        }

        return $this->formatMessage($message);
    }
}
