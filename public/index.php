<?php

/*
|--------------------------------------------------------------------------
| Puente al punto de entrada real
|--------------------------------------------------------------------------
|
| El index.php de verdad está en la raíz del proyecto (ver el comentario de
| ese archivo). Este solo lo llama, para que los entornos que sirven public/
| —como Herd en la máquina de desarrollo— sigan funcionando sin cambios.
|
| Dentro del archivo incluido __DIR__ apunta a la raíz, así que todas sus
| rutas resuelven igual sin importar por dónde se haya entrado.
|
*/

require __DIR__.'/../index.php';
