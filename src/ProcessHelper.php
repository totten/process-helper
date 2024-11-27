<?php
namespace ProcessHelper;

use Symfony\Component\Process\Process;

class ProcessHelper {

  /**
   * Run a command synchronously.
   *
   * @param \Symfony\Component\Process\Process|string|array $process
   *   Ex: 'echo "Hello world"'
   *   Ex: ['echo @MSG', 'MSG' => 'Hello world']
   * @return \Symfony\Component\Process\Process
   * @throws \RuntimeException
   * @see interpolate
   */
  public static function run($process) {
    $process = self::castToProcess($process);
    if (getenv('DEBUG') > 0) {
      printf("RUN: %s\n    (in %s)\n", $process->getCommandLine(), $process->getWorkingDirectory());
      // ob_flush();
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
   * Run a command synchronously. Assert successful execution.
   *
   * @param \Symfony\Component\Process\Process|string|array $process
   *   Ex: 'echo "Hello world"'
   *   Ex: ['echo @MSG', 'MSG' => 'Hello world']
   * @return \Symfony\Component\Process\Process
   * @throws \RuntimeException
   * @see self::interpolate()
   */
  public static function runOk($process) {
    $process = self::castToProcess($process);
    self::run($process);
    if (!$process->isSuccessful()) {
      throw new ProcessErrorException($process);
    }
    return $process;
  }

  /**
   * Print information about a command to screen.
   *
   * @param \Symfony\Component\Process\Process|string|array $process
   */
  public static function dump($process) {
    $process = self::castToProcess($process);
    echo self::createReport($process);
  }

  /**
   * Format a detailed summary about the process.
   *
   * @param \Symfony\Component\Process\Process|string|array $process
   * @return string
   */
  public static function createReport($process) {
    $process = self::castToProcess($process);
    return "[[ COMMAND: {$process->getCommandLine()} ]]
[[ CWD: {$process->getWorkingDirectory()} ]]
[[ EXIT CODE: {$process->getExitCode()} ]]
[[ STDOUT ]]
{$process->getOutput()}
[[ STDERR ]]
{$process->getErrorOutput()}

";
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
      return preg_replace_callback('/([#!@])([a-zA-Z0-9_]+)/', function ($m) use ($args) {
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
   * Convert from various formats to a Process object.
   *
   * @param string|array|Process $process
   * @return \Symfony\Component\Process\Process
   */
  public static function castToProcess($process) {
    if ($process instanceof Process) {
      return $process;
    }

    if (is_callable([Process::class, 'fromShellCommandline'])) {
      $newProcess = function ($cmd) {
        return Process::fromShellCommandline($cmd, NULL, NULL, NULL, NULL);
      };
    }
    else {
      $newProcess = function ($cmd) {
        return new Process($cmd, NULL, NULL, NULL, NULL);
      };
    }

    if (is_string($process)) {
      return $newProcess($process);
    }
    if (is_array($process)) {
      $cmd = $process[0];
      unset($process[0]);
      return $newProcess(self::interpolate($cmd, $process));
    }
    throw new \RuntimeException("Cannot cast item to process");
  }

}
