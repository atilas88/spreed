<?php

declare(strict_types=1);


namespace OCA\Talk\Service;

if ((@include_once __DIR__ . '/../../../bbb/vendor/autoload.php')===false) {
	throw new \Exception('Cannot execute talk-bbb integration');
}

use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\JoinMeetingParameters;
use OCA\Talk\Participant;
use OCA\Talk\Service\ParticipantService;
use OCP\IConfig;

class BbbService{
    /** @var IConfig */
	private $config;
    /** @var BigBlueButton|null */
	private $server;
	 /** @var ParticipantService */
	private $participantService;

    public function __construct(
		IConfig $config, ParticipantService $participantService
        )
        {
		    $this->config = $config;
		    $this->participantService = $participantService;

        }
    private function getServer(): BigBlueButton {
            if (!$this->server) {
                $apiUrl = $this->config->getAppValue('bbb', 'api.url');
                $secret = $this->config->getAppValue('bbb', 'api.secret');
				if(!empty($apiUrl) && !empty($secret))
				{
				   $this->server = new BigBlueButton($apiUrl, $secret);
				}
				else
				{
                    throw new \Exception('Bbb is not configured');
				}

            }
            return $this->server;
    }

    public function getJoinUrl($participant,$room,$flags,$creationTime): string {

    $url = '';
	$token = $room->getToken();
	$password = ($participant->getAttendee()->getParticipantType() <= 2) ? 'IAmAModerator' : 'IAmAnAttendee';
    $displayname = $participant->getAttendee()->getDisplayName();
    $joinMeetingParams = new JoinMeetingParameters($token, $displayname , $password);

	// ensure that float is not converted to a string in scientific notation
	$joinMeetingParams->setCreateTime(sprintf("%.0f", $creationTime));
	$joinMeetingParams->setJoinViaHtml5(true);
	$joinMeetingParams->setRedirect(true);

	$url = $this->getServer()->getJoinMeetingURL($joinMeetingParams);
    // we only change the flags after we successfully obtained the BBB url
    if ($flags === null) {
		// Default flags: user is in room with audio/video.
	$flags = Participant::FLAG_IN_CALL | Participant::FLAG_WITH_AUDIO | Participant::FLAG_WITH_VIDEO;
	}
	$this->participantService->changeInCall($room, $participant, $flags);
    return $url;
    }

	private function buildMeetingParams($room): CreateMeetingParameters {
		$participant_in_call = $this->participantService->getParticipantsForRoom($room);
		$room_name = "";
		if($room->getType() == 1)
		{
			$room_name.="[ ";
			foreach($participant_in_call as $participant)
			{
				$room_name.=$participant->getAttendee()->getDisplayName()." , ";
			}
			$room_name.=" ]";
			// remove last comma
			$pos_last_comma = strrpos($room_name,",");
			$room_name = substr_replace($room_name," ]",$pos_last_comma,strlen($room_name));
		}
		else{
			$room_name = $room->getName();
		}
		$createMeetingParams = new CreateMeetingParameters($room->getToken(),$room_name);
		$createMeetingParams->setAttendeePW('IAmAnAttendee');
		$createMeetingParams->setModeratorPW('IAmAModerator');
		// set recording
		$createMeetingParams->setRecord(true);
		$createMeetingParams->setAllowStartStopRecording(true);
		// set recording
		return $createMeetingParams;
	}

	public function createMeeting($room) {
		$bbb = $this->getServer();
		$meetingParams = $this->buildMeetingParams($room);

		try {
			$response = $bbb->createMeeting($meetingParams);
		} catch (\Exception $e) {
			throw new \Exception('Can not process create request: ' . $bbb->getCreateMeetingUrl($meetingParams));
		}

		if (!$response->success()) {
			throw new \Exception('Can not create meeting: ' . $response->getMessage());
		}
		return $response->getCreationTime();
	}

}
