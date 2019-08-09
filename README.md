# ProcessHelper

This is a quick bit of sugar for working with Symfony Process.

```php
use \ProcessHelper\ProcessHelper as PH;
$p = PH::runOk(['ls -la @TGT', 'TGT' => '/home/myuser/Documents/Lots of Stuff']);
print_r(explode("\n", $p->getOutput()));
```

Bits of extra behavior:

* Set env var `DEBUG` to display information about any commands as they are executed. (This is loosely similar to using bash's `set -x`.)
    * `DEBUG=1` - Show basic summary information
    * `DEBUG=2` - Show full, real-time output
* The `run()` and `runOk()` helpers will execute the command while respecting the DEBUG option.
* The `run()` and `runOk()` helpers will automatically cast strings and arrays into `Process` objects.
  When constructing the `Process`, variables may be escaped and interpolated.
* The `runOk()` helper will assert that the command executed normally. If there's an error, it throws an exception.
  The resulting exception message will report more details about the failed subcommand.

This doesn't really seem like it should be a standalone project, except that
I've found these snippets useful in like 5+ projects...
