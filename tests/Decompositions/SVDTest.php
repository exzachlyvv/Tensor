<?php

namespace Tensor\Tests\Decompositions;

use Tensor\Decompositions\SVD;
use Tensor\Tensor;
use Tensor\Vector;
use Tensor\Matrix;
use Tensor\ArrayLike;
use Tensor\Arithmetic;
use Tensor\Comparable;
use Tensor\Statistical;
use Tensor\ColumnVector;
use Tensor\Trigonometric;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Tensor\Decompositions\SVD
 */
class SVDTest extends TestCase
{
    public function testSvd()
    {
        // input matrix

        $matrix = Matrix::quick([
            [0.00, 0.00, 0.56, 0.56. 0.00, 0.00, 1.00],
            [0.49, 0.71, 0.00, 0.00. 0.00, 0.71, 0.00],
            [0.49, 0.71, 0.00, 0.00. 0.00, 0.71, 0.00],
            [0.72, 0.00, 0.00, 0.00. 1.00, 0.00, 0.00],
            [0.00, 0.00, 0.83, 0.83. 0.00, 0.00, 0.00],
            [0.49, 0.71, 0.00, 0.00. 0.00, 0.71, 0.00],
            [0.49, 0.71, 0.00, 0.00. 0.00, 0.71, 0.00],
            [0.72, 0.00, 0.00, 0.00. 1.00, 0.00, 0.00],
            [0.00, 0.00, 0.83, 0.83. 0.00, 0.00, 0.00],
        ]);

        /*
        $matrix = array(array('22', '10', '2', '3', '7'),
                        array('14', '7', '10', '0', '8'),
                        array('-1', '13', '-1', '-11', '3'),
                        array('-3', '-2', '13', '-2', '4'),
                        array('9', '8', '1', '-2', '4'),
                        array('9', '1', '-7', '5', '-1'),
                        array('2', '-6', '6', '5', '1'),
                        array('4', '5', '0', '-2', '2'));
        */
//$matrix = array(array('1', '-1'), array('0', '1'), array('1', '0'), array('-1', '1'));
//$matrix = array(array('4', '0'), array('3', '-5'));

        $svd = SVD::decompose($matrix);

//        $this->assertEquals($matrix->n(), $svd->v()->m());
//        $this->assertEquals($matrix->n(), $svd->v()->n());
//
//        $this->assertEquals($matrix->m(), $svd->u()->m());
//        $this->assertEquals($matrix->m(), $svd->u()->n());



        $recreatedInput = $svd->compose();

        $this->assertEquals($recreatedInput->n(), $matrix->n());
        $this->assertEquals($recreatedInput->m(), $matrix->m());
        $error = $matrix->subtractMatrix($recreatedInput)->sum()->sum();
        $this->assertEqualsWithDelta(0, $error, 1.0);

//        $matrix = Matrix::quick([
//            [6.23, -1, 0.03],
//            [0.01, 2.01, 1],
//            [1.1, 5, -5],
//        ]);
//
//        $decomposition = SVD::decompose($matrix);
//
//        $this->assertEquals(2, 2);
    }
}
