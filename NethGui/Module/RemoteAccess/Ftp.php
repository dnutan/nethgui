<?php
/**
 * NethGui
 *
 * @package Modules
 */

/**
 * TODO: describe class
 *
 * @package Modules
 * @subpackage RemoteAccess
 */
final class NethGui_Module_RemoteAccess_Ftp extends NethGui_Core_Module_Standard
{

    public function initialize()
    {
        parent::initialize();
        $this->declareParameter('ftpPassword', '/.*/');
        $this->declareParameter('allowFtp', '/.*/');

        $this->constants['allowFtpOptions'] = array(
            'normal' => 'Consenti accesso da qualsiasi rete',
            'private' => 'Consenti accesso da reti locali',
            'off' => 'Disabilitato'
        );
    }
}