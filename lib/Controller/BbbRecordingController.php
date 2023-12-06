<?php
declare(strict_types=1);

namespace OCA\Talk\Controller;

use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PublicPage;



use OCP\IRequest;
use OCA\Talk\Service\BbbService;

use OCA\BigBlueButton\Service\RecordingReadyService;





class BbbRecordingController extends Controller {


	/**@var BbbService**/
    private $bbb_service;

	/**@var RecordingReadyService**/
    private $recording_service;




    public function __construct(
		string $appName,
		IRequest $request,
        BbbService $bbb_service,
		RecordingReadyService $recording_service,


	) {
		parent::__construct($appName, $request);
        $this->bbb_service = $bbb_service;
		$this->recording_service = $recording_service;

	}


	#[PublicPage]
	#[NoCSRFRequired]
	public function recordingReady(): void {
        $recording_params = $this->request->post['signed_parameters'];

		$moderator_mail = $this->bbb_service->findModeratorMail($recording_params);
		if(!empty($moderator_mail))
		{
			$this->recording_service->downloadRecording($recording_params, $moderator_mail);
		}

	}
}
