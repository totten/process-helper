<?php
namespace ProcessHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ProcessHelper {

  /**
   * Helper which synchronously runs a command and verifies that it doesn't generate an error.
   *
   * @param \Symfony\Component\Process\Process|string|array $process
   *   Ex: 'echo "Hello world"'
   *   Ex: ['echo "@msg"', 'msg' => 'Hello world']
   * @return \Symfony\Component\Process\Process
   * @throws \RuntimeException
   */
  public static function runDebug($process) {
    $process = self::castToProcess($process);
    if (getenv('DEBUG') > 0) {
      var_dump(array(
        'Working Directory' => $process->getWorkingDirectory(),
        'Command' => $process->getCommandLine(),
      ));
      //      ob_flush();
    }

    $process->run(function ($type, $buffer) {
      if (getenv('DEBUG') > 1) {
        if (\Symfony\Component\Process\Process::ERR === $type) {
          echo 'STDERR > ' . $buffer;
        }
        else {
          echo 'STDOUT > ' . $buffer;
        }
        // ob_flush();
      }
    });

    return $process;
  }

  /**
   * Helper which synchronously runs a command and verifies that it doesn't generate an error.
   *
   * @param \Symfony\Component\Process\Process|string|array $process
   *   Ex: 'echo "Hello world"'
   *   Ex: ['echo "@msg"', 'msg' => 'Hello world']
   * @return \Symfony\Component\Process\Process
   * @throws \RuntimeException
   */
  public static function runOk($process) {
    $process = self::castToProcess($process);
    self::runDebug($process);
    if (!$process->isSuccessful()) {
      throw new ProcessErrorException($process);
    }
    return $process;
  }

  /**
   * @param \Symfony\Component\Process\Process|string|array $process
   */
  public static function dump($process) {
    $process = self::castToProcess($process);
    var_dump(array(
      'Working Directory' => $process->getWorkingDirectory(),
      'Command' => $process->getCommandLine(),
      'Exit Code' => $process->getExitCode(),
      'Output' => $process->getOutput(),
      'Error Output' => $process->getErrorOutput(),
    ));
  }

  /**
   * Evaluate a string, replacing variables with shell-escaped values.
   *
   * @param string $expr
   *   Ex: "ls @DIR | head -n #CNT !FINALE"
   *   Note: "@" indicates a value should be shell-escaped
   *   Note: "#" indicates a value should be numeric
   *   Note: "!" indicates a value should NOT be escaped or validated
   * @param array $args
   *   Ex: ['DIR' => '/home/foo/My Special Data', 'CNT' => 5, 'FINALE' => '>/dev/null']
   */
  public static function interpolate($expr, $args) {
    if ($args === NULL) {
      return $expr;
    }
    else {
      return preg_replace_callback('/([#!@])([a-zA-Z0-9_]+)/', function($m) use ($args) {
        if (isset($args[$m[2]])) {
          $values = $args[$m[2]];
        }
        else {
          // Unrecognized variables are ignored. Mitigate risk of accidents.
          return $m[0];
        }
        $values = is_array($values) ? $values : array($values);
        switch ($m[1]) {
          case '@':
            return implode(', ', array_map('escapeshellarg', $values));

          case '!':
            return implode(', ', $values);

          case '#':
            foreach ($values as $valueKey => $value) {
              if ($value === NULL) {
                $values[$valueKey] = 'NULL';
              }
              elseif (!is_numeric($value)) {
                //throw new API_Exception("Failed encoding non-numeric value" . var_export(array($m[0] => $values), TRUE));
                throw new \RuntimeException("Failed encoding non-numeric value (" . $m[0] . ")");
              }
            }
            return implode(', ', $values);

          default:
            throw new \RuntimeException("Unrecognized prefix");
        }
      }, $expr);
    }
  }

  /**
   * @param string|array|Process $process
   *   Ex: 'echo "Hello world"'
   *   Ex: ['echo "@msg"', 'msg' => 'Hello world']
   * @return \Symfony\Component\Process\Process
   */
  protected static function castToProcess($process) {
    if ($process instanceof Process) {
      return $process;
    }
    if (is_string($process)) {
      return new Process($process);
    }
    if (is_array($process)) {
      $cmd = $process[0];
      unset($process[0]);
      return new Process(self::interpolate($cmd, $process));
    }
    throw new \RuntimeException("Cannot cast item to process");
  }

}
