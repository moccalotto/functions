<?php

namespace Moccalotto\Functions;

interface CallInterface
{
    /**
     * Get the default instance used when this class is used statically.
     *
     * @return CallInterface
     */
    public static function defaultInstance() : CallInterface;

    /**
     * Call exec() on the default instance
     */
    public static function __callStatic(callable $function, array $args);

    /**
     * Execute a call
     *
     * @param callable $function The function name
     * @param array $args The arg list for the function call
     * @return mixed
     */
    public function exec(callable $function, array $args);

    /**
     * Add or create an Expectation
     *
     * @param Expectation $expectation If this parameter is set, it is added to the list, and returned.
     * If it is not set, a new Expectation is created.
     *
     * @return Expectation
     */
    public static function expects(Expectation $expectation = null) : Expectation;

    /**
     * Return the expecations
     *
     * @return ExpectationBag
     */
    public static function expectations() : ExpectationBag;
}
