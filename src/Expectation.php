<?php

namespace Moccalotto\Functions;

use LogicException;

/**
 * Handle expectations to functon calls.
 */
class Expectation
{
    /**
     * @var Call
     */
    protected $call;

    /**
     * @var string
     */
    protected $function;

    /**
     * @var bool
     */
    protected $expectsArgs = false;

    /**
     * @var array
     */
    protected $expectedArgs = [];

    /**
     * @var array
     */
    protected $argMatchers = [];

    /**
     * @var bool
     */
    protected $expectsResult = false;

    /**
     * @var mixed
     */
    protected $expectedResult;

    /**
     * @var mixed
     */
    protected $return;

    /**
     * @var bool
     */
    protected $mocksResult = false;

    /**
     * @var bool
     */
    protected $executed = false;

    /**
     * @var bool
     */
    protected $satisfied = false;

    /**
     * @var string
     */
    protected $reason;

    /**
     * A side effect to be executed if result is mocked.
     *
     * @var callable
     *
     * The prototype of this callable is:
     *
     * @param array $args The arguments that this function was called with
     * @param mixed $mockedResult The value of the mocked result.
     * @return void
     */
    protected $sideEffect;

    public function __construct(Call $call)
    {
        $this->call = $call;

        $this->reset('Function name not yet set');
    }

    public function  __debugInfo()
    {
        return [
            'function' => $this->function,
            'executed' => $this->executed,
            'satisfied' => $this->satisfied,
            'reason' => $this->reason,
            'expectsArgs' => $this->expectsArgs,
            'expectedArgs' => $this->expectedArgs,
            'expectsResult' => $this->expectsResult,
            'expectedResult' => $this->expectedResult,
        ];
    }

    /**
     * Set the state to finalized, set the final reason message, and return a given value
     *
     * @param bool $satisfied
     * @param string $reason
     * @param mixed $return
     *
     * @return mixed
     */
    protected function finalize(bool $satisfied, string $reason, $result)
    {
        $this->executed = true;
        $this->satisfied = $satisfied;
        $this->reason = $reason;

        return $result;
    }

    protected function reset(string $reason)
    {
        $this->executed = false;
        $this->satisfied = false;
        $this->reason = $reason;

        $this->expectsResult = false;
        $this->expectedResult = null;

        $this->expectsArgs = false;
        $this->expectedArgs = null;

        $this->mocksResult = false;
        $this->mockedResult = null;

        return $this;
    }

    /**
     * Run through all arg matchers and check that they all match the given args
     *
     * @param array $args
     *
     * @return bool
     */
    protected function checkArgsMatch(array $args) : bool
    {
        foreach ($this->argMatchers as $index => $matcher) {
            if (!isset($args[$index])) {
                return false;
            }

            $argToCheck = $args[$index];

            if (is_string($matcher)) {
                $argMatches = preg_match($matcher, $argToCheck);
            } elseif (is_callable($matcher)) {
                $argMatches = $matcher($argToCheck);
            } else {
                throw new LogicException('An argument matcher must be a regex or a callable');
            }

            if (!$argMatches) {
                return false;
            }
        }

        return true;
    }

    /**
     * Ensure that we are in a given state in order to avoid logic errors
     *
     * @param array $properties
     * @param string $errorMessage
     *
     * @return $this
     */
    protected function ensureState(array $properties, string $errorMessage)
    {
        foreach ($properties as $key => $value) {
            if ($this->$key !== $value) {
                throw new LogicException($errorMessage);
            }
        }

        return $this;
    }

    /**
     * Start a new expectation
     */
    public function then()
    {
        return $this->call->expects();
    }

    /**
     * Expect a call to a given function.
     *
     * @param calable $function
     *
     * @return $this;
     */
    public function callTo(callable $function)
    {
        $this->ensureState([
            'function' => null,
        ], 'You already set the function to expect');

        $this->function = $function;

        return $this->reset(sprintf('Call to %s not yet executed in the specified order', $function));
    }

    /**
     * Expect the called function to return a given value.
     *
     * @param mixed $result
     *
     * @return $this
     */
    public function withResult($result)
    {
        $this->ensureState([
            'expectsResult' => false,
            'mocksResult' => false,
        ], 'You have already made assertions about the result');

        $this->expectsResult = true;
        $this->expectedResult = $result;
        $this->mocksResult = false;
        $this->mockedResult = null;

        return $this;
    }

    /**
     * Mock the return value of the function call.
     *
     * Then we don't need to call the actual function.
     *
     * @param mixed $result
     *
     * @return $this
     */
    public function withMockedResult($mockedResult)
    {
        $this->ensureState([
            'expectsResult' => false,
            'mocksResult' => false,
        ], 'You have already made assertions about the result');

        $this->expectsResult = false;
        $this->expectedResult = null;
        $this->mocksResult = true;
        $this->mockedResult = $mockedResult;

        return $this;
    }

    /**
     * Run this piece of code after mocking the result
     *
     * @param Callable
     *
     * @return $this;
     */
    public function withSideEffect(callable $sideEffect)
    {
        $this->ensureState([
            'mocksResult' => true,
        ], 'You can only add a side effect if you mock the result');

        $this->sideEffect = $sideEffect;
        return $this;
    }

    public function whereArgMatches($argIndex, $pattern)
    {
        $this->ensureState([
            'expectsArgs' => false,
        ], 'You already made assertions about the arguments');

        $this->argMatchers[$argIndex] = $pattern;

        return $this;
    }

    /**
     * When the given function is called, expect that these args are there.
     *
     * @param mixed $expectedArgs...
     *
     * @return $this
     */
    public function withArgs(...$expectedArgs)
    {
        $this->ensureState([
            'expectsArgs' => false,
            'argMatchers' => [],
        ], 'You already made assertions about the arguments');

        $this->expectsArgs = true;
        $this->expectedArgs = $expectedArgs;

        return $this;
    }

    /**
     * Does this expectation listen for the given function name
     *
     * @param string $function
     *
     * @return bool
     */
    public function captures(callable $function) : bool
    {
        return $this->function === $function;
    }

    /**
     * Has this expectation been met/satisfied.
     *
     * @return bool
     */
    public function satisfied() : bool
    {
        return $this->satisfied;
    }

    /**
     * Has this expectation been executed.
     */
    public function executed() : bool
    {
        return $this->executed;
    }

    /**
     * The reason why the expectation was not met.
     *
     * @return string
     */
    public function reason() : string
    {
        return $this->reason;
    }

    /**
     * Handle the function call
     *
     * @param array $args
     *
     * @return mixed
     */
    public function execute(array $args)
    {
        if (!$this->function) {
            throw new LogicException('You have not yet defined a function for this expectation');
        }

        if ($this->expectedArgs && $this->expectedArgs !== $args) {
            return $this->finalize(false, 'Unexpected Arguments', null);
        }

        if ($this->argMatchers && !$this->checkArgsMatch($args)) {
            return $this->finalize(false, 'Arguments did not match the specified criteria', null);
        }

        if ($this->sideEffect) {
            call_user_func($this->sideEffect, $args, $this->mockedResult);
        }

        if ($this->mocksResult) {
            return $this->finalize(true, 'Mocked Result', $this->mockedResult);
        }

        $result = call_user_func_array($this->function, $args);

        if ($this->expectsResult && $this->expectedResult !== $result) {
            return $this->finalize(false, 'Unexpected Result', $result);
        }

        return $this->finalize(true, 'Success', $result);
    }
}
