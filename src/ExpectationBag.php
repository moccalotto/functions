<?php

namespace Moccalotto\Functions;

use Countable;
use ArrayIterator;
use IteratorAggregate;

class ExpectationBag implements IteratorAggregate, Countable
{
    /**
     * @var Expectation[]
     */
    protected $bag = [];

    /**
     * Add new expectation
     *
     * @param Expectation $expectation
     *
     * @return $this
     */
    public function add(Expectation $expectation)
    {
        $this->bag[] = $expectation;

        return $this;
    }

    /**
     * Iterate over the Expectation objects in bag.
     *
     * For IteratorAggregate
     */
    public function getIterator() : ArrayIterator
    {
        return new ArrayIterator($this->bag);
    }

    /**
     * Get number of Expectation objects in bag.
     *
     * For Countable
     */
    public function count() : int
    {
        return count($this->bag);
    }

    /**
     * Have all expectations been met/satisfied?
     */
    public function check() : bool
    {
        foreach ($this->bag as $expectation) {
            if (!$expectation->satisfied()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Remove all expectations from the bag.
     */
    public function clear()
    {
        $this->bag = [];
    }

    /**
     * Get all unsatisfied Expectations
     *
     * @return Expectation[]
     */
    public function unsatisfied() : array
    {
        return array_filter($this->bag, function($expectation) {
            return !$expectation->satisfied();
        });
    }

    /**
     * Get all satisfied Expectations
     *
     * @return Expectation[]
     */
    public function satisfied() : array
    {
        return array_filter($this->bag, function($expectation) {
            return $expectation->satisfied();
        });
    }
}
