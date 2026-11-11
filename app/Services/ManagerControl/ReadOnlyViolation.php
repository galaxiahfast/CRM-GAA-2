<?php

namespace App\Services\ManagerControl;

/** Se intentó ejecutar algo que no es una consulta SELECT simple. */
class ReadOnlyViolation extends ManagerControlException {}
