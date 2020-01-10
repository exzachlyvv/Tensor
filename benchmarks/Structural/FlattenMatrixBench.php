<?php

namespace Tensor\Benchmarks\Structural;

use Tensor\Matrix;

class FlattenMatrixBench
{
    /**
     * @var \Tensor\Matrix
     */
    protected $a;

    public function setUp() : void
    {
        $this->a = Matrix::uniform(500, 500);
    }

    /**
     * @Subject
     * @Iterations(5)
     * @BeforeMethods({"setUp"})
     * @OutputTimeUnit("seconds", precision=3)
     */
    public function flatten() : void
    {
        $this->a->flatten();
    }
}
