<?php

declare(strict_types=1);

namespace App;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class BaseCommand extends Command
{
    public const MODE_SELECT = 0;
    public const MODE_INFO = 1;
    public const MODE_CONFIRM = 2;

    abstract protected function getDefinitions(): array;

    protected function getCommonDefinitions(): array
    {
        return [
            new InputOption(
                'info',
                null,
                InputOption::VALUE_NONE,
                'Ask for information instead. Ctrl-C will not break the terminal.'
            ),
            new InputOption(
                'confirm',
                null,
                InputOption::VALUE_NONE,
                'Ask for confirmation instead . Ctrl-C will not break the terminal.'
            ),
        ];
    }

    protected function setDefinitions(): self
    {
        $definitions = array_merge($this->getDefinitions(), $this->getCommonDefinitions());
        $this->setDefinition(new InputDefinition($definitions));

        return $this;
    }

    protected function getMode(InputInterface $input): int
    {
        if ($input->getOption('info')) {
            return self::MODE_INFO;
        }

        if ($input->getOption('confirm')) {
            return self::MODE_CONFIRM;
        }

        return self::MODE_SELECT;
    }

    protected function formatMessage(?string $message): string
    {
        if ($message !== null) {
            $message .= ' Ctrl-C will not break the terminal.';
            $style = 'info';
        } else {
            $message = 'Your terminal will break if you enter Ctrl-C. To restart it, use Ctrl-Break then Enter.';
            $style = 'comment';
        }

        return sprintf('<%s>%s</%s>', $style, $message, $style);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mode = $this->getMode($input);

        switch ($mode) {
            case self::MODE_INFO:
                $message = $this->getInfo($input, $output);
                break;
            case self::MODE_CONFIRM:
                $message = $this->getConfirmation($input, $output);
                break;
            default:
                $message = $this->getChoice($input, $output);
                break;
        }

        $output->writeln('You entered: '.$message);
        return Command::SUCCESS;
    }

    private function getInfo(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');
        $question = new Question('Enter something or Ctrl-C: ');

        return (string) $helper->ask($input, $output, $question);
    }

    private function getConfirmation(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Confirm something or Ctrl-C [y|n]: ');
        $answer = $helper->ask($input, $output, $question);

        return $answer ? 'yes' : 'no';
    }

    private function getChoice(InputInterface $input, OutputInterface $output): string
    {
        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion('Select your favorite color', ['red', 'blue', 'yellow'], 0);
        $question->setErrorMessage('Color %s is not valid.');

        return $helper->ask($input, $output, $question);
    }
}
