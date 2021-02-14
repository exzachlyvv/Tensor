<?php

namespace Tensor\Decompositions;

use Tensor\Matrix;

/**
 * SVD
 *
 * The LU decomposition is a factorization of a Matrix as the product of a
 * lower and upper triangular matrix as well as a permutation matrix.
 *
 * @category    Scientific Computing
 * @package     Rubix/Tensor
 * @author      Zach Vander Velden
 */
class SVD
{
    /**
     * The lower triangular matrix.
     *
     * @var \Tensor\Matrix
     */
    protected $u;

    /**
     * The upper triangular matrix.
     *
     * @var \Tensor\Matrix
     */
    protected $v;

    /**
     * The permutation matrix.
     *
     * @var \Tensor\Matrix
     */
    protected $s;

    /**
     * Factory method to decompose a matrix.
     *
     * @param \Tensor\Matrix $a
     * @return self
     */
    public static function decompose(Matrix $a) : self
    {
        $m = $a->m();
        $n = $a->n();

        $U  = Matrix::quick($a->asArray())->asArray();//$this->matrixConstruct($matrix, $m, $n);
        $V  = Matrix::zeros($n, $n)->asArray();//$this->matrixConstruct($matrix, $n, $n);
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                $V[$i][$j] = $a[$i][$j];
            }
        }

        $eps = 2.22045e-016;

        // Decompose Phase

        // Householder reduction to bidiagonal form.
        $g = $scale = $anorm = 0.0;
        for($i = 0; $i < $n; $i++){
            $l = $i + 2;
            $rv1[$i] = $scale * $g;
            $g = $s = $scale = 0.0;
            if($i < $m){
                for($k = $i; $k < $m; $k++) $scale += abs($U[$k][$i]);
                if($scale != 0.0) {
                    for($k = $i; $k < $m; $k++) {
                        $U[$k][$i] /= $scale;
                        $s += $U[$k][$i] * $U[$k][$i];
                    }
                    $f = $U[$i][$i];
                    $g = - static::sameSign(sqrt($s), $f);
                    $h = $f * $g - $s;
                    $U[$i][$i] = $f - $g;
                    for($j = $l - 1; $j < $n; $j++){
                        for($s = 0.0, $k = $i; $k < $m; $k++) $s += $U[$k][$i] * $U[$k][$j];
                        $f = $s / $h;
                        for($k = $i; $k < $m; $k++) $U[$k][$j] += $f * $U[$k][$i];
                    }
                    for($k = $i; $k < $m; $k++) $U[$k][$i] *= $scale;
                }
            }
            $W[$i] = $scale * $g;
            $g = $s = $scale = 0.0;
            if($i + 1 <= $m && $i + 1 != $n){
                for ($k= $l - 1; $k < $n; $k++) $scale += abs($U[$i][$k]);
                if($scale != 0.0){
                    for ($k= $l - 1; $k < $n; $k++){
                        $U[$i][$k] /= $scale;
                        $s += $U[$i][$k] * $U[$i][$k];
                    }
                    $f = $U[$i][$l - 1];
                    $g = - static::sameSign(sqrt($s), $f);
                    $h = $f * $g - $s;
                    $U[$i][$l - 1] = $f - $g;
                    for($k = $l - 1; $k < $n; $k++) $rv1[$k] = $U[$i][$k] / $h;
                    for($j = $l - 1; $j < $m; $j++){
                        for($s = 0.0, $k = $l - 1; $k < $n; $k++) $s += $U[$j][$k] * $U[$i][$k];
                        for($k = $l - 1; $k < $n; $k++) $U[$j][$k] += $s * $rv1[$k];
                    }
                    for($k= $l - 1; $k < $n; $k++) $U[$i][$k] *= $scale;
                }
            }
            $anorm = max($anorm, (abs($W[$i]) + abs($rv1[$i])));
        }

        // Accumulation of right-hand transformations.
        for($i = $n - 1; $i >= 0; $i--){
            if($i < $n - 1){
                if($g != 0.0){
                    for($j = $l; $j < $n; $j++) // Double division to avoid possible underflow.
                        $V[$j][$i] = ($U[$i][$j] / $U[$i][$l]) / $g;
                    for($j = $l; $j < $n; $j++){
                        for($s = 0.0, $k = $l; $k < $n; $k++) $s += ($U[$i][$k] * $V[$k][$j]);
                        for($k = $l; $k < $n; $k++) $V[$k][$j] += $s * $V[$k][$i];
                    }
                }
                for($j = $l; $j < $n; $j++) $V[$i][$j] = $V[$j][$i] = 0.0;
            }
            $V[$i][$i] = 1.0;
            $g = $rv1[$i];
            $l = $i;
        }

        // Accumulation of left-hand transformations.
        for($i = min($m, $n) - 1; $i >= 0; $i--){
            $l = $i + 1;
            $g = $W[$i];
            for($j = $l; $j < $n; $j++) $U[$i][$j] = 0.0;
            if($g != 0.0){
                $g = 1.0 / $g;
                for($j = $l; $j < $n; $j++){
                    for($s = 0.0, $k = $l; $k < $m; $k++) $s += $U[$k][$i] * $U[$k][$j];
                    $f = ($s / $U[$i][$i]) * $g;
                    for($k = $i; $k < $m; $k++) $U[$k][$j] += $f * $U[$k][$i];
                }
                for($j = $i; $j < $m; $j++) $U[$j][$i] *= $g;
            }else {
                for($j = $i; $j < $m; $j++) $U[$j][$i] = 0.0;
            }
            ++$U[$i][$i];
        }

        // Diagonalization of the bidiagonal form
        // Loop over singular values, and over allowed iterations.
        for($k = $n - 1; $k >= 0; $k--){
            for($its = 0; $its < 30; $its++){
                $flag = true;
                for($l = $k; $l >= 0; $l--){
                    $nm = $l - 1;
                    if( $l == 0 || abs($rv1[$l]) <= $eps*$anorm){
                        $flag = false;
                        break;
                    }
                    if(abs($W[$nm]) <= $eps*$anorm) break;
                }
                if($flag){
                    $c = 0.0;  // Cancellation of rv1[l], if l > 0.
                    $s = 1.0;
                    for($i = $l; $i < $k + 1; $i++){
                        $f = $s * $rv1[$i];
                        $rv1[$i] = $c * $rv1[$i];
                        if(abs($f) <= $eps*$anorm) break;
                        $g = $W[$i];
                        $h = static::hypo($f,$g);
                        $W[$i] = $h;
                        $h = 1.0 / $h;
                        $c = $g * $h;
                        $s = -$f * $h;
                        for($j = 0; $j < $m; $j++){
                            $y = $U[$j][$nm];
                            $z = $U[$j][$i];
                            $U[$j][$nm] = $y * $c + $z * $s;
                            $U[$j][$i] = $z * $c - $y * $s;
                        }
                    }
                }
                $z = $W[$k];
                if($l == $k){
                    if($z < 0.0){
                        $W[$k] = -$z; // Singular value is made nonnegative.
                        for($j = 0; $j < $n; $j++) $V[$j][$k] = -$V[$j][$k];
                    }
                    break;
                }
                if($its == 29) print("no convergence in 30 svd iterations");
                $x = $W[$l]; // Shift from bottom 2-by-2 minor.
                $nm = $k - 1;
                $y = $W[$nm];
                $g = $rv1[$nm];
                $h = $rv1[$k];
                $f = (($y - $z) * ($y + $z) + ($g - $h) * ($g + $h)) / (2.0 * $h * $y);
                $g = static::hypo($f,1.0);
                $f = (($x - $z) * ($x + $z) + $h * (($y / ($f + static::sameSign($g,$f))) - $h)) / $x;
                $c = $s = 1.0;
                for($j = $l; $j <= $nm; $j++){
                    $i = $j + 1;
                    $g = $rv1[$i];
                    $y = $W[$i];
                    $h = $s * $g;
                    $g = $c * $g;
                    $z = static::hypo($f,$h);
                    $rv1[$j] = $z;
                    $c = $f / $z;
                    $s = $h / $z;
                    $f = $x * $c + $g * $s;
                    $g = $g * $c - $x * $s;
                    $h = $y * $s;
                    $y *= $c;
                    for($jj = 0; $jj < $n; $jj++){
                        $x = $V[$jj][$j];
                        $z = $V[$jj][$i];
                        $V[$jj][$j] = $x * $c + $z * $s;
                        $V[$jj][$i] = $z * $c - $x * $s;
                    }
                    $z = static::hypo($f,$h);
                    $W[$j] = $z;  // Rotation can be arbitrary if z = 0.
                    if($z){
                        $z = 1.0 / $z;
                        $c = $f * $z;
                        $s = $h * $z;
                    }
                    $f = $c * $g + $s * $y;
                    $x = $c * $y - $s * $g;
                    for($jj = 0; $jj < $m; $jj++){
                        $y = $U[$jj][$j];
                        $z = $U[$jj][$i];
                        $U[$jj][$j] = $y * $c + $z * $s;
                        $U[$jj][$i] = $z * $c - $y * $s;
                    }
                }
                $rv1[$l] = 0.0;
                $rv1[$k] = $f;
                $W[$k] = $x;
            }
        }

        // Reorder Phase
        // Sort. The method is Shell's sort.
        // (The work is negligible as compared to that already done in decompose phase.)
        $inc = 1;
        do {
            $inc *= 3;
            $inc++;
        }   while($inc <= $n);

        do {
            $inc /= 3;
            for($i = $inc; $i < $n; $i++){
                $sw = $W[$i];
                for($k = 0; $k < $m; $k++) $su[$k] = $U[$k][$i];
                for($k = 0; $k < $n; $k++) $sv[$k] = $V[$k][$i];
                $j = $i;
                while($W[$j - $inc] < $sw){
                    $W[$j] = $W[$j - $inc];
                    for($k = 0; $k < $m; $k++) $U[$k][$j] = $U[$k][$j - $inc];
                    for($k = 0; $k < $n; $k++) $V[$k][$j] = $V[$k][$j - $inc];
                    $j -= $inc;
                    if($j < $inc) break;
                }
                $W[$j] = $sw;
                for($k = 0; $k < $m; $k++) $U[$k][$j] = $su[$k];
                for($k = 0; $k < $n; $k++) $V[$k][$j] = $sv[$k];
            }
        }  while($inc > 1);

        for($k = 0; $k < $n; $k++){
            $s = 0;
            for($i = 0; $i < $m; $i++) if ($U[$i][$k] < 0.0) $s++;
            for($j = 0; $j < $n; $j++) if ($V[$j][$k] < 0.0) $s++;
            if($s > ($m + $n)/2) {
                for($i = 0; $i < $m; $i++) $U[$i][$k] = - $U[$i][$k];
                for($j = 0; $j < $n; $j++) $V[$j][$k] = - $V[$j][$k];
            }
        }

        // calculate the rank
        $rank = 0;
        for($i = 0; $i < count($W); $i++){
            if(round($W[$i], 4) > 0){
                $rank += 1;
            }
        }

        // Low-Rank Approximation
//        $q = 0.9;
//        $k = 0;
//        $frobA = 0;
//        $frobAk = 0;
//        for($i = 0; $i < $rank; $i++) $frobA += $W[$i];
//        do{
//            for($i = 0; $i <= $k; $i++) $frobAk += $W[$i];
//            $clt = $frobAk / $frobA;
//            $k++;
//        }   while($clt < $q);

        // prepare S matrix as n*n daigonal matrix of singular values
        for($i = 0; $i < $n; $i++){
            for($j = 0; $j < $n; $j++){
                $S[$i][$j] = 0;
                $S[$i][$i] = $W[$i];
            }
        }

        $matrices['U'] = $U;
        $matrices['S'] = $S;
        $matrices['W'] = $W;
        $matrices['V'] = $V;
        $matrices['Rank'] = $rank;
//        $matrices['K'] = $k;

        return new self(Matrix::quick($U), Matrix::quick($S), Matrix::quick($V)->transpose());
    }

    /**
     * sameSign
     *
     * @param integer $a
     * @param integer $b
     * @return integer
     */
    private static function sameSign($a, $b){

        if($b >= 0){
            $result = abs($a);
        }else {
            $result = - abs($a);
        }
        return $result;
    }

    /**
     *  Pythagorean Theorem:
     *
     *  a = 3
     *  b = 4
     *  r = sqrt(square(a) + square(b))
     *  r = 5
     *
     *  r = sqrt(a^2 + b^2) without under/overflow.
     * @param mixed $a
     * @param mixed $b
     */
    static function hypo($a, $b)
    {
        $aHat = abs($a);
        $bHat = abs($b);

        if ($aHat > $bHat) {
            $r = $aHat * sqrt(1 + ($b / $a) ** 2);
        } elseif ($b != 0) {
            $r = $bHat * sqrt(1 + ($a / $b) ** 2);
        } else {
            $r = 0.0;
        }
        return $r;
    }

    /**
     * @param \Tensor\Matrix $l
     * @param \Tensor\Matrix $u
     * @param \Tensor\Matrix $p
     */
    public function __construct(Matrix $u, Matrix $s, Matrix $v)
    {
        $this->u = $u;
        $this->s = $s;
        $this->v = $v;
    }

    /**
     * Return the lower triangular matrix.
     *
     * @return \Tensor\Matrix
     */
    public function u() : Matrix
    {
        return $this->u;
    }

    /**
     * Return the upper triangular matrix.
     *
     * @return \Tensor\Matrix
     */
    public function s() : Matrix
    {
        return $this->s;
    }

    /**
     * Return the permutation matrix.
     *
     * @return \Tensor\Matrix
     */
    public function v() : Matrix
    {
        return $this->v;
    }

    /**
     * Compose the decomposed matrices
     *
     * @return Matrix
     */
    public function compose(): Matrix
    {
        return $this->u->matmul($this->s)->matmul($this->v->transpose());
    }
}