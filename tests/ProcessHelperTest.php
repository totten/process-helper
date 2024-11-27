<?php

namespace ProcessHelper\Tests;

use ProcessHelper\ProcessErrorException;
use ProcessHelper\ProcessHelper as PH;

class ProcessHelperTest extends \PHPUnit\Framework\TestCase {

  public function testRunOk_arrayInput(): void {
    $p = PH::runOk(['echo BEGIN @MSG END', 'MSG' => 'This -n and -t that.']);
    $lines = explode("\n", $p->getOutput());
    $this->assertEquals('BEGIN This -n and -t that. END', $lines[0]);

    $report = PH::createReport($p);
    $this->assertRegex(';COMMAND: echo BEGIN;', $report);
    $cwd = preg_quote(getcwd(), ';');
    $this->assertRegex(";CWD: $cwd;", $report);
    $this->assertRegex(";EXIT CODE: 0;", $report);
  }

  public function testRunOk_stringInput(): void {
    $p = PH::runOk('echo "Hello world"');
    $this->assertTrue((bool) preg_match(';Hello world;', $p->getOutput()));
  }

  public function testRunOk_Fail_arrayInput(): void {
    try {
      PH::runOk(['echo Bad stuff ; exit @BADCODE', 'BADCODE' => 99]);
    }
    catch (ProcessErrorException $e) {
      $this->assertEquals($e->getProcess()->getExitCode(), 99, "Exception should report exit code");
      $this->assertRegex(';Bad stuff;', $e->getProcess()->getOutput());
    }
  }

  public function testRunOk_Fail_stringInput(): void {
    try {
      PH::runOk('echo Bad stuff ; exit 98');
    }
    catch (ProcessErrorException $e) {
      $this->assertEquals($e->getProcess()->getExitCode(), 98, "Exception should report exit code");
      $this->assertRegex(';Bad stuff;', $e->getProcess()->getOutput());
    }
  }

  public function testRun_Fail(): void {
    $p = PH::run('echo Bad stuff ; exit 97');
    $this->assertEquals($p->getExitCode(), 97, "Exception should report exit code");
    $this->assertRegex(';Bad stuff;', $p->getOutput());
  }

  public function getInterpolateOK() {
    $result = [];
    $result[] = ['echo ABC', [], 'echo ABC'];
    $result[] = ['echo @MSG', ['MSG' => 'Hello world'], 'echo \'Hello world\''];
    $result[] = ['echo !MSG', ['MSG' => 'Hello world'], 'echo Hello world'];
    $result[] = ['echo @MISSING', ['FOO' => 'bar'], 'echo @MISSING'];
    return $result;
  }

  /**
   * @param string $expr
   * @param array $args
   * @param string $expectOutput
   * @return void
   * @dataProvider getInterpolateOK
   */
  public function testInterpolateOK(string $expr, array $args, string $expectOutput) {
    $output = PH::interpolate($expr, $args);
    $this->assertEquals($expectOutput, $output, "Exception should report output");
  }

  public function getInterpolateFails() {
    $result = [];
    $result[] = ['echo #NUM', ['NUM' => 'abc'], ';Failed encoding non-numeric;'];
    return $result;
  }

  /**
   * @param string $expr
   * @param array $args
   * @param string $expectRegex
   * @return void
   * @dataProvider getInterpolateFails
   */
  public function testInterpolateFails(string $expr, array $args, string $expectRegex) {
    try {
      $executed = FALSE;
      PH::interpolate($expr, $args);
      $executed = TRUE;
    }
    catch (\RuntimeException $e) {
      $this->assertRegex($expectRegex, $e->getMessage());
    }
    $this->assertFalse($executed, "Should have raised exception like: $expectRegex");

  }

  protected function assertRegex(string $regex, $value): void {
    $msg = sprintf("Value (%s) should match regex (%s)", json_encode($value), json_encode($regex));
    $this->assertTrue((bool) preg_match($regex, $value), $msg);
  }

}
