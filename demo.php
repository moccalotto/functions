<?php

use Moccalotto\Functions\Call;

require 'vendor/autoload.php';

/*
 * If you really want to be able to test your function calls,
 * you can make those critical function calls via the Call class.
 * The caller intercepts those calls and allow you to mock and make assertions.
 *
 * This code is for illustration purposes only!
 */

Call::expects()->callTo('sprintf')
    ->withArgs('foo %s %s', 'bar', 'baz')
    ->withMockedResult('NOT THE USUAL RESULT')

    ->then()->callTo('sprintf')
    ->withArgs('foo')
    ->withResult('foo')

    ->then()->callTo('sprintf')
    ->withArgs('done!')

    ->then()->callTo('sprintf')

    ->then()->callTo('vsprintf')
    ->withArgs('klap %s!', ['hesten'])

    ->then()->callTo('file_get_contents')
    ->withArgs('https://www.example.com')
    ->withMockedResult('Example Domain')
    ->withSideEffect(function ($args, $result) {
        echo 'SIDE EFFECT>>>>'.PHP_EOL;
        var_dump($args, $result);
        echo '<<<<'.PHP_EOL;

    })

    ->then()->callTo('header')
    ->whereArgMatches(0, '/location/Ai')
    ->withMockedResult(null)
    ->withSideEffect(function ($args, $result) {
        echo 'CALLED header()'.PHP_EOL;
    });

// method not actually called, we mock the result
var_dump(Call::sprintf('foo %s %s', 'bar', 'baz'));

// method called, and we check that the result is foo
var_dump(Call::sprintf('foo'));

// method called, but we only make assertions about the arguments
var_dump(Call::sprintf('done!'));

// method called. We don't care about args or result. We just want to see that it is called.
var_dump(Call::sprintf('tante og fjaser!'));

// We can do more thant just printf
var_dump(Call::vsprintf('klap %s!', ['hesten']));

// We can always make an un-expected call.
// Only the expected calls are intercepted, checked and/or mocked.
var_dump(Call::intval('555'));

// Make a call where we mock the result and avoid the side effects.
var_dump(Call::file_get_contents('https://www.example.com'));

// Make (and intercept) a call to header()
var_dump(Call::header('location: https://www.example.org'));

// check that all expectations are met.
var_dump(Call::expectations()->check());
