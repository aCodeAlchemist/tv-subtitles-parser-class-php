# tv-subtitles-parser-class-php
Parse and get your subtitles in json or xml format

### Usage

Supports `srt`, `rar`, `zip`. Add $_FILES['file] as a key.

```php
  include 'PHPSubtitleParser.php';
  $obj = new PHPSubtitleParser($_FILES);
  echo $obj->get('json');
```
