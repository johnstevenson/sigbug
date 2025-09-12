# sigbug

Demonstrates a Ctrl-C issue in Symfony QuestionHelper when selecting input from a list of choices.

Using Ctrl-C can leave the terminal in a broken state on Unixy platforms under certain conditions:

* When a signal handler is installed and calls `exit`
* When xdebug-handler restarts a process

When calling other QuestionHelper functions, Ctrl-C works okay.

## Usage

```sh
# Installation
git clone https://github.com/johnstevenson/sigbug
composer update

# Demo with a signal handler
php sigbug handler

# Demo using a restart
php sigbug restart
```

## Commands

The following information is also available from the application's Help:

* `php sigbug handler -h`
* `php sigbug restart -h`

### handler

The <info>handler</info> command uses a signal handler to demonstrate a Ctrl-C issue in
Symfony QuestionHelper when selecting input from a list (ChoiceQuestion):

<info>php sigbug handler</info>

The handler reports receiving a Ctrl-C signal then exits, which unfortunately
leaves the terminal in a broken state. Use Ctrl-Break then Enter to restart it.

Ctrl-C will work normally if the signal handler does not call `exit` or when using the
other QuestionHelper methods:

* `php sigbug handler --no-exit`
* `php sigbug handler --info`
* `php sigbug handler --confirm`

The `--default` option uses a signal handler that explicitly sets SIGINT to its
default action (SIG_DFL). This mimics the behaviour of the child process in the
`restart `command, which is needed because the parent sets SIGINT to be ignored
(SIG_IGN) and the child process inherits this state.

Here it can be used to examine calling a new process from a parent with SIGINT
ignored, by using the `trap` command:

`trap "" SIGINT; php -r "passthru(PHP_BINARY.' sigbug handler --default');"; trap - SIGINT;`

Using Ctrl-C will break the terminal, but only if PHP is the parent process. If
the shell is the parent, Ctrl-C will work normally:

`trap "" SIGINT; php sigbug handler --default; trap - SIGINT;`

### restart

The restart command uses a process restart to demonstrate a Ctrl-C issue in
Symfony QuestionHelper when selecting input from a list (ChoiceQuestion):

`php sigbug restart`

Using Ctrl-C unfortunately leaves the terminal in a broken state. Use Ctrl-Break
then Enter to restart it.

Ctrl-C will work normally if there is no restart, or when using the other
QuestionHelper methods:

* `php sigbug restart --none`
* `php sigbug restart --info`
* `php sigbug restart --confirm`

The issue is triggered when SIGINT is set to be ignored (SIG_IGN) in the parent
process, then set to its default action (SIG_DFL) in the child process because
it inherited the ignored state.

When there is no restart, SIGINT remains in its initial state - which is the
default action.

xdebug-handler is used for the restart, regardless of whether Xdebug is loaded
or not.
