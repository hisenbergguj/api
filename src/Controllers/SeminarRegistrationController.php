<?php

namespace Plusit\Api\Controllers;
ini_set('max_execution_time', 0);
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Hyn\MultiTenant\Tenant\DatabaseConnection;
use Hyn\MultiTenant\Models\Website;
use Hyn\MultiTenant\Contracts\WebsiteRepositoryContract;
use Hyn\MultiTenant\Models\Hostname;
use App\Library\CustomFunction;
use Log;
use JWTAuth;
use App\Interfaces\EventRepositoryInterface;
use Hyn\MultiTenant\Models\Tenant;
use App\Models\Event;
use App\Models\EventAttendees;
use App\Models\Person;
use Illuminate\Support\Facades\Input;
use App\Models\participantActionLog;

class SeminarRegistrationController extends Controller
{

    protected $seminarRepository;

    /**
     * initializes variable with $seminarRepository
     * @param EventRepositoryInterface $seminarRepository
     */
    public function __construct(EventRepositoryInterface $seminarRepository)
    {
        $this->seminarRepository = $seminarRepository;
        // Set database connection to specific tenant
        //$this->middleware('tenant.database.connection');
        $this->middleware('jwt.auth', ['except' => ['authenticate']]);

    }

    /**
     * Make Seminar Registration
     * @param $eventId
     * @param $personId
     * @output Make registration for single person
     */

    public function seminarRegistration($eventId = null, $personId = null){
        try{
            // Took Event Details and Event participants count
            $eventDetails = Event::findOrFail($eventId);
            $totalParticipant = EventAttendees::where('event_id', $eventId)->where('ContactStatusID', '!=', '3')->count();

            if(count($eventDetails) > 0){

                // Check If participant is allow to register
                $today = Carbon::now()->format('Y-m-d');
                $startDate = date('Y-m-d', strtotime($eventDetails->event_startdate));
                $endDate = date('Y-m-d', strtotime($eventDetails->event_enddate));
                if($today > $endDate){
                    return CustomFunction::jsonResponse([], "error", CustomFunction::customTrans("eventRegistration.eventAlreadyCompleted"));
                }

                // check if event is register and still want to make registration
                if(Input::get("forceRegistration") != 1) {
                    if ($today > $startDate && $today < $endDate) {
                        return CustomFunction::jsonResponse([], "waring", CustomFunction::customTrans("eventRegistration.eventRunningWarningMessage"));
                    }
                }

                // Check if person already register for the event
                $checkDuplicateRegister = EventAttendees::where('event_id', $eventId)->where('person_id', $personId)->count();
                if ($checkDuplicateRegister > 0) {
                    return CustomFunction::jsonResponse([], "error", CustomFunction::customTrans("eventRegistration.alreadyRegisterForEvent"));
                }

                // check if person have already another events
                $checkConflictEvents = EventAttendees::leftjoin("events", function ($join) {
                    $join->on('event_attendees.event_id', '=', 'events.id');
                })->where('person_id', $personId)
                    ->whereBetween('events.event_startdate', [$eventDetails->event_startdate, $eventDetails->event_enddate])
                    ->count();

                if ($checkConflictEvents > 0) {
                    return CustomFunction::jsonResponse([], "error", CustomFunction::customTrans("eventRegistration.busyWithAnotherEvent"));
                }

                $totalParticipant = $totalParticipant + 1;

                // Check if max registration reached or not
                if ($totalParticipant > $eventDetails->max_registration) {
                    return CustomFunction::jsonResponse([], "error", CustomFunction::customTrans("eventRegistration.cross_max_registration_participants"));
                }

                // Make registration for that person
                $participant = new EventAttendees();
                $participant->event_id = $eventId;
                $participant->person_id = $personId;
                $participant->ContactStatusID = 1;
                $participant->is_confirmed = 1;
                $participant->registerBy = "Plugin : Participant itself";

                $participant->save();

                // Update the person tag to participant
                $personObj = Person::findOrFail($personId);
                $personObj->is_participant = 1;
                $personObj->save();

                // Add a log based on the status of the participant
                $status = "Register";
                $logText = "Participant itself : " . CustomFunction::customTrans("eventRegistration.addAsParticipantLogMessage") . $status . CustomFunction::customTrans("eventRegistration.forEventText") . $eventDetails->event_name ;
                $this->makeParticipantActionLog($personObj, $logText);

                return CustomFunction::jsonResponse([], "success", CustomFunction::customTrans("eventRegistration.addParticipantForEventSuccess"));
            }


        }catch (\Exception $e) {
            // something went wrong whilst attempting to encode the token
            return CustomFunction::jsonResponse($e, "error", "Something went wrong with your request");

        }

    }

    public function makeParticipantActionLog($personObj = null, $text = "")
    {
        $participantLog = new participantActionLog();
        $participantLog->personId = $personObj->PersonId;
        $participantLog->text = $text;
        $participantLog->actionById = $personObj->PersonId;
        $participantLog->actionByName = $personObj->FirstName . " " . $personObj->LastName;

        $participantLog->save();

        return true;
    }

}
