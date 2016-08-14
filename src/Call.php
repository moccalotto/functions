<?php

namespace Moccalotto\Functions;

class Call implements CallInterface
{

    /**
     * @var Call
     */
    protected static $instance;

    /**
     * Expectations
     *
     * @var ExpectationBag
     */
    protected $expectations;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->expectations = new ExpectationBag;
    }

    /**
     * Get the default instance used when this class is used statically.
     */
    public static function defaultInstance() : CallInterface
    {
        if (!static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public static function __callStatic(callable $function, array $args)
    {
        return static::defaultInstance()->exec($function, $args);
    }

    public function exec(callable $function, array $args)
    {
        foreach ($this->expectations as $expectation) {

            if ($expectation->executed()) {
                continue;
            }

            if (!$expectation->captures($function)) {
                break;
            }

            return $expectation->execute($args);
        }

        return call_user_func_array($function, $args);
    }

    /**
     * Add or create an Expectation
     *
     * @param Expectation $expectation If this parameter is set, it is added to the list, and returned. If it is not set, a new Expectation is created.
     *
     * @return Expectation
     */
    public static function expects(Expectation $expectation = null) : Expectation
    {
        $instance = static::defaultInstance();

        if (!$expectation) {
            $expectation = new Expectation($instance);
        }

        $instance->expectations->add($expectation);

        return $expectation;
    }

    /**
     * Return the expecations
     */
    public static function expectations() : ExpectationBag
    {
        return static::defaultInstance()->expectations;
    }
}
