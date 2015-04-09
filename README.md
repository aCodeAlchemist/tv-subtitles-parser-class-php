# tv-subtitles-parser-class-php
Parse and get your subtitles in json or xml format

### Usage

```php
  include 'PHPSubtitleParser.php';
  $obj = new PHPSubtitleParser($_FILES);
  echo $obj->get('json');
```
