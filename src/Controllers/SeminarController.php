<?php

namespace Plusit\Api\Controllers;
ini_set('max_execution_time', 0);
use App\Http\Controllers\Controller;
use App\Models\Admin\OdooUser;
use App\Models\Admin\user_subscribe;
use App\Models\EventCategory;
use App\Models\Location;
use App\Models\Schedule;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Hyn\MultiTenant\Tenant\DatabaseConnection;
use Hyn\MultiTenant\Models\Website;
use Hyn\MultiTenant\Contracts\WebsiteRepositoryContract;
use Illuminate\Support\Facades\DB;
use Hyn\MultiTenant\Models\Hostname;
use App\Library\CustomFunction;
use Carbon\Carbon;
use Log;
use JWTAuth;
use App\Interfaces\EventRepositoryInterface;
use Hyn\MultiTenant\Models\Tenant;
use App\Models\Event;
use Mockery\CountValidator\Exception;

class SeminarController extends Controller
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
     * Get all seminar category
     * @param $id is optional
     * @output List of all seminar category / Single category details
     */

    public function getSeminarCategory($id = null){
        try{
            if($id == null)
                $seminarCategory = EventCategory::all();
            else
                $seminarCategory = EventCategory::find($id);

           return CustomFunction::jsonResponse($seminarCategory, "success", "");

        }catch (\Exception $e) {
            // something went wrong whilst attempting to encode the token
            return CustomFunction::jsonResponse($e, "error", "Something went wrong with your request");

        }

    }

    /**
     * Get all seminars
     * @param $id is optional
     * @output List of all seminar category / Single category details
     */

    public function searchSeminar($id = null){

        $parameters = Input::all();

        $event = Event::query();

        // Search By seminar Name
        if($parameters["q"] != ""){
            $event->Where(DB::Raw('LOWER(events.event_name)'), 'like', '%' . strtolower($parameters["q"]) . '%');
        }

        // Select seminar by category
        if(!empty($parameters["seminar_category"])){
            $seminarCategory = explode(",", $parameters["seminar_category"]);
            $event->whereIn('event_category_id', $seminarCategory);
        }

        // Select seminar by locations
        if(!empty($parameters["locations"])){
            $locations = explode(",", $parameters["locations"]);
            // fetch the seminars whoes schedules are on such locations
            $schedules = Schedule::whereIn('LocationID', $locations)
                        ->leftjoin("event_schedule", function($join){
                            $join->on('schedule.id', '=','event_schedule.schedule_id');
                        })
                        ->groupBy('event_schedule.event_id')
                        ->get([
                            DB::Raw('GROUP_CONCAT(event_schedule.event_id ) as events')
                        ])->first();
            if($schedules->events != "") {
                $eventIds = explode(",", $schedules->events);
                $event->whereIn('id', $eventIds);
            }
        }

        // Select seminars by future time ranges
        if(!empty($parameters["future_date_range"])){
            $date = explode("-", $parameters["future_date_range"]);
            //  echo Carbon::parse($date[0])->format('Y-m-d H:i:s');
            if(count($date) > 0)
                $event->whereBetween('date(event_startdate)', array(Carbon::parse($date[0])->format('Y-m-d'), Carbon::parse($date[1])->format('Y-m-d')));
        }else if(!empty($parameters["fix_time_period"])){
            if($parameters["fix_time_period"] == 'current_month'){
                $startDate = Carbon::now()->format("Y-m-d");
                $endDate = Carbon::now()->endOfMonth()->format("Y-m-d");
                $event->whereBetween('date(event_startdate)', array($startDate,$endDate));
            }elseif($parameters["fix_time_period"] == 'next_month'){
                $startDate = new Carbon('first day of next month');
                $endDate = new Carbon('last day of next month');
                $event->whereBetween('date(event_startdate)', array($startDate->format("Y-m-d"),$endDate->format("Y-m-d")));
            }elseif($parameters["fix_time_period"] == 'next_3_months'){
                $startDate = new Carbon('first day of next month');
                $endDate = new Carbon('last day of next month');
                $endDate->addMonth(2);
                $event->whereBetween('date(event_startdate)', array($startDate->format("Y-m-d"),$endDate->format("Y-m-d")));
            }
        }

        if($id == null)
            $seminars = $event->get();
        else
            $seminars = $event->find($id);


        if(count($seminars) > 0){
            return Response::json([
                "type"    => "success",
                "message" => "",
                "result" => $seminars
            ]);
        }else{
            return Response::json([
                "type"    => "error",
                "message" => CustomFunction::customTrans("general.error_message"),
                "result" => $seminars
            ]);
        }

    }


}
