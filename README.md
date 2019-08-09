# ProcessHelper

This is a quick bit of sugar for working with Symfony Process.

```php
$p = ProcessHelper::runOk(['ls -la @TGT', 'TGT' => '/home/myuser/Documents/Lots of Stuff'])
$files = explode("\n", $p->getOutput());
```

Bits of extra behavior:

* Set env var `DEBUG=1` to display a summary of any commands that are executed.
* Set env var `DEBUG=2` to display detailed output of any commands that are executed.
* The `runDebug()` and `runOk()` helpers will execute the command while respecting the DEBUG option.
* The `runDebug()` and `runOk()` helpers will automatically cast strings and arrays into `Process` objects.
  When constructing the `Process`, variables may be escaped and interpolated.
* The `runOk()` helper will assert that the command executed normally. If there's an error, it throws an exception.
  The resulting exception message will report more details about the failed subcommand.
