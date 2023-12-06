<?php

declare(strict_types=1);


namespace OCA\Talk\Service;

if ((@include_once __DIR__ . '/../../../bbb/vendor/autoload.php')===false) {
	throw new \Exception('Cannot execute talk-bbb integration');
}

use BigBlueButton\BigBlueButton;
use BigBlueButton\Parameters\CreateMeetingParameters;
use BigBlueButton\Parameters\JoinMeetingParameters;
use BigBlueButton\Parameters\EndMeetingParameters;
use BigBlueButton\Parameters\GetMeetingInfoParameters;
use OCA\Talk\Participant;

use OCA\BigBlueButton\Crypto;
use OCP\IURLGenerator;

use OCP\IConfig;

use OCP\IUserManager;

use OCA\Talk\Model\AttendeeMapper;


class BbbService{
	/** @var IConfig */
	private $config;
	/** @var BigBlueButton|null */
	private $server;
	/** @var ParticipantService */
	private $participantService;
	/** @var Crypto */
	private $crypto;
	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUserManager */
	private $userManager;

	/**@var AttendeeMapper**/
	private $attendeeMapper;


	public function __construct(
		IConfig $config, ParticipantService $participantService,Crypto $crypto,
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		AttendeeMapper $attendeeMapper
	)
	{
		$this->config = $config;
		$this->participantService = $participantService;
		$this->crypto = $crypto;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->attendeeMapper = $attendeeMapper;

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
		$token = $room->getToken()."_".$room->getId();
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
			foreach($participant_in_call as $participant)
			{
				$room_name.=$participant->getAttendee()->getDisplayName()."_";
			}
			$pos_last_guion = strrpos($room_name,"_");
			$room_name = substr($room_name,0,$pos_last_guion);
		}
		else{
			$room_name = $room->getName();
		}
		$createMeetingParams = new CreateMeetingParameters($room->getToken()."_".$room->getId(),$room_name);
		$createMeetingParams->setAttendeePW('IAmAnAttendee');
		$createMeetingParams->setModeratorPW('IAmAModerator');
		// set recording
		$createMeetingParams->setRecord(true);
		$createMeetingParams->setAllowStartStopRecording(true);
		// set recording
		//set recording ready url
		$mac = $this->crypto->calculateHMAC($room->getToken());
		$recordingReadyUrl = $this->urlGenerator->linkToRouteAbsolute('spreed.BbbRecording.recordingReady', ['token' => $room->getToken(),'mac' => $mac]);
		$createMeetingParams->setRecordingReadyCallbackUrl($recordingReadyUrl);
		//set recording ready url
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

	public function endCallAll($room) {
		$bbb = $this->getServer();
		$moderator_secret = 'IAmAModerator';
		$endMeetingParams = new EndMeetingParameters($room->getToken()."_".$room->getId(),$moderator_secret);
		$bbb->endMeeting($endMeetingParams);
	}

	public function leaveCall($room)
	{

		$bbb = $this->getServer();
		$meetingParams = new GetMeetingInfoParameters($room->getToken()."_".$room->getId());
		$meeting_info = $bbb->getMeetingInfo($meetingParams);
		$participant_count = $meeting_info->getMeeting()->getParticipantCount();
		if ($participant_count == 1) {
			$this->endCallAll($room);
		}
	}

	public function findModeratorMail($params)
	{
		//decode jwt to get meeting_id and record_id
		$decoded = json_decode(base64_decode(explode(".",$params)[1]),true);
		$room_id = $decoded["meeting_id"];

		$room_id_number = explode("_",$room_id);

		$room_moderator = $this->getRoomModerator(intval($room_id_number[1]));

		$mail = "";

		$user = $this->userManager->get($room_moderator);

		if(!empty($user))
		{
			$mail = $user->getEMailAddress();
		}
		return $mail;
	}

	public function getRoomModerator($room_id) {
		try {

			$roomModerators = $this->attendeeMapper->getActorsByParticipantTypes($room_id, [Participant::MODERATOR]);
			if (!empty($roomOwners)) {
				foreach ($roomModerators as $moderator) {
					if ($moderator->getActorType() === 'users') {
						return $moderator->getActorId();
					}
				}
			}
			$roomOwners = $this->attendeeMapper->getActorsByParticipantTypes($room_id, [Participant::OWNER]);

			if (!empty($roomOwners)) {
				foreach ($roomOwners as $owner) {
					if ($owner->getActorType() === 'users') {
						return $owner->getActorId();
					}
				}
			}
		} catch (\Exception $e) {
		}
		return null;
	}

}
