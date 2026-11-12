<?php

namespace App\Services\ManagerControl;

/** La escritura en ManagerControl está apagada (MANAGERCONTROL_WRITE_ENABLED=false o sin cuenta de escritura). */
class WriteDisabled extends ManagerControlException {}
