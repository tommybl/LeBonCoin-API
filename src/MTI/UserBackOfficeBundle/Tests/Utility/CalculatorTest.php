<?php

namespace MTI\UserBackOfficeBundle\Tests\Utility;

use MTI\UserBackOfficeBundle\Utility\Calculator;

class CalculatorTest extends \PHPUnit_Framework_TestCase
{
    public function testAdd()
    {
        $calc = new Calculator();
        $result = $calc->add(30, 12);

        // vérifie que votre classe a correctement calculé!
        $this->assertEquals(42, $result);
    }
}