<?php

namespace App\Exceptions;

use RuntimeException;

/** Se lanza al intentar modificar o borrar un registro de solo inserción. */
class RegistroInmutableException extends RuntimeException {}
