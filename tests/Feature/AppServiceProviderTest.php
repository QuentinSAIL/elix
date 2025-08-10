<?php

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\App;

test('euro blade directive works', function () {
    $result = Blade::compileString('@euro(1234.56)');
    $expected = "<?php echo number_format(1234.56, 2, ',', ' ') . ' €'; ?>";
    $this->assertEquals($expected, $result);
});

test('limit blade directive works with limit', function () {
    $result = Blade::compileString('@limit("hello world", 5)');
    $expected = '<?php echo e(Str::limit("hello world",  5)); ?>';
    $this->assertEquals($expected, $result);
});

test('limit blade directive works without limit', function () {
    $result = Blade::compileString('@limit("hello world")');
    $expected = '<?php echo e(Str::limit("hello world", 100)); ?>';
    $this->assertEquals($expected, $result);
});