<?php
declare(strict_types=1);

namespace OCA\Talk\Controller;

use OCA\Talk\Middleware\Attribute\RequireCallEnabled;
use OCA\Talk\Middleware\Attribute\RequireModeratorOrNoLobby;
use OCA\Talk\Middleware\Attribute\RequireParticipant;
use OCA\Talk\Middleware\Attribute\RequireReadWriteConversation;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCA\Talk\Service\BbbService;

use OCP\App\IAppManager;


class BbbController extends AEnvironmentAwareController {

    /**@var BbbService**/
    private $bbb_service;
	/**@var IAppManager**/
	private $appManager;

    public function __construct(
		string $appName,
		IRequest $request,
        BbbService $bbb_service,
		IAppManager $appManager
	) {
		parent::__construct($appName, $request);
        $this->bbb_service = $bbb_service;
		$this->appManager = $appManager;
	}

	#[PublicPage]
	#[RequireCallEnabled]
	#[RequireModeratorOrNoLobby]
	#[RequireParticipant]
	#[RequireReadWriteConversation]
	public function joinCall(?int $flags = null): DataResponse
    {
		$session = $this->participant->getSession();
		if ($session->id === '0') {
			return new DataResponse([], Http::STATUS_NOT_FOUND);
		}
		try
		{
			$creationDate = $this->bbb_service->createMeeting($this->room);
			$bbb_url = $this->bbb_service->getJoinUrl($this->participant,$this->room,$flags,$creationDate);
			return new DataResponse($bbb_url);
		}
		catch (\Exception $e)
		{
            new DataResponse(['error' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
    }

	#[PublicPage]
	public function getBbbStatus(): DataResponse
	{
        return new DataResponse($this->appManager->isInstalled('bbb'));
	}


}
