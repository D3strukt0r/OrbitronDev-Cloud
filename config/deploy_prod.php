<?php

use EasyCorp\Bundle\EasyDeployBundle\Deployer\DefaultDeployer;

return new class extends DefaultDeployer
{
    public function configure()
    {
        return $this->getConfigBuilder()
            ->server('user@hostname')
            ->deployDir('/var/www/service-cloud')
            ->repositoryUrl('git@github.com:OrbitronDev/service-cloud.git')
            ->repositoryBranch('master')
            ;
    }
};
