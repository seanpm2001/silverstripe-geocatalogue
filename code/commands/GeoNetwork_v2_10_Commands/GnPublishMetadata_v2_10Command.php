<?php
/**
 * @author Rainer Spittel (rainer at silverstripe dot com)
 * @package geocatalog
 * @subpackage commands
 */

/**
 * Perform a insert request to a GeoNetwork node.
 */
class GnPublishMetadata_v2_10Command extends GnAuthenticationCommand {

	public function get_api_url() {
		return 'srv/eng/metadata.admin';
	}

	/**
	 * @throws GnPublishMetadataCommand_Exception
	 */
	public function execute() {
		$data       = $this->getParameters();
		$gnID 		= $data['gnID'];

		$this->restfulService = new RestfulService($this->getController()->getGeoNetworkBaseURL(),0);
		if ($this->getUsername() ) {
			$this->restfulService->basicAuth($this->getUsername(), $this->getPassword());
		}

		$controller = $this->getController();
		$page = $controller->data();

		$privilegeString = $page->Privilege;
		$privilegeList = explode(',',$privilegeString);

		$groupID = $page->GeonetworkGroupID;

		if ($groupID == '' || $groupID == null) {
			throw new GnPublishMetadataCommand_Exception('Group for record publishing not set correctly. Please contact the system administrator.');
		}

		if ($privilegeString == '' || $privilegeString == null) {
			throw new GnPublishMetadataCommand_Exception('Privileges for publishing not set correctly. Please contact the system administrator.');
		}

		$data = array();
		foreach($privilegeList as $privilege) {
			$data['_1_'.$privilege] = "on";                  // default user (public)
			$data['_'.$groupID.'_'.$privilege] = "on";
		}
		ksort($data);
		$data['id']       = $gnID;

		$params = GnCreateInsertCommand::implode_with_keys($data);

		$response    = $this->restfulService->request($this->get_api_url()."?".$params,'GET');
		$responseXML = $response->getBody();

        // read GeoNetwork ID from the response-XML document
        $doc  = new DOMDocument();
        $doc->loadXML($responseXML);
		$xpath = new DOMXPath($doc);

        $idList = $xpath->query('/response/id');
		$response_gnID = null;
		if ($idList->length > 0) {
			$response_gnID = $idList->item(0)->nodeValue;
		}

		if (!isset($response_gnID)) {
			throw new GnPublishMetadataCommand_Exception('GeoNetwork ID for the new dataset has not been created.');
		}
		if ($gnID != $response_gnID) {
			throw new GnPublishMetadataCommand_Exception('GeoNetwork publication has failed.');
		}
		return $gnID;
	}

}