## [Post to the instagram]

## Install with composer 
```
composer require josh/instagram:dev-master
```

## Example

```php

use Instagram\Instagram;

require __DIR__.'/vendor/autoload.php';

$instagram = new Instagram();
$instagram->login([
    'username' => $_POST['username'],
    'password' => $_POST['password']
]);

$results = $instagram->upload([
    'caption' => $_POST['caption'],
    'tmp_image' => $_FILES['image']['tmp_name']
]);

var_dump($results);

```

## License
This repo is under the MIT license.
