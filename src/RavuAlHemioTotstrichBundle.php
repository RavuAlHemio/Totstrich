<?php

namespace RavuAlHemio\TotstrichBundle;

use RavuAlHemio\TotstrichBundle\DependencyInjection\TotstrichExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RavuAlHemioTotstrichBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new TotstrichExtension();
    }
}
