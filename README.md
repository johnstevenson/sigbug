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
composer install

# Demo with a signal handler
php sigbug handler

# Demo using a restart
php sigbug restart
```

## Commands

### handler

The handler reports receiving a Ctrl-C signal then exits, which unfortunately
leaves the terminal in a broken state. Use Ctrl-Break then Enter to restart it.

Ctrl-C will work normally if the signal handler does not call `exit` or when using the
other QuestionHelper methods:

* `php sigbug handler --no-exit`
* `php sigbug handler --info`
* `php sigbug handler --confirm`

The `--default` option is included to complement the `restart `command, where the
issue is triggered when SIGINT is set to be ignored (SIG_IGN) in the parent
process then set to its default action (SIG_DFL) in the restarted (child) process
because it inherited the ignored state.

Here it can be used to examine calling a new process from a parent with SIGINT
ignored, by using the `trap` command:

`trap "" SIGINT; php -r "passthru(PHP_BINARY.' sigbug handler --default');"; trap - SIGINT;`

Using Ctrl-C will break the terminal, but only if PHP is the parent process. For
example, Ctrl-C will work normally when using:

`trap "" SIGINT; php sigbug handler --default; trap - SIGINT;`

### restart

The restart command uses a process restart to demonstrate a Ctrl-C issue in
Symfony QuestionHelper when selecting input from a list:

`php sigbug restart`

Using Ctrl-C unfortunately leaves the terminal in a broken state. Use Ctrl-Break
then Enter to restart it.

Ctrl-C will work normally if there is no restart, or when using the other
QuestionHelper methods:

* `php sigbug restart --none`
* `php sigbug restart --info`
* `php sigbug restart --confirm`

The issue is triggered when SIGINT is set to be ignored (SIG_IGN) in the parent
process then set to its default action (SIG_DFL) in the restarted (child)
process because it inherited the ignored state.

When there is no restart, SIGINT remains in its initial state (which would
normally be the default action).

xdebug-handler is used for the restart, regardless of whether Xdebug is loaded
or not.
