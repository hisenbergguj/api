<?php

namespace Plusit\Api\Controllers;
ini_set('max_execution_time', 0);
use App\Http\Controllers\Controller;
use App\Models\EventCategory;
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
use App\Interfaces\PersonRepositoryInterface;
use Hyn\MultiTenant\Models\Tenant;
use App\Models\Person;

class ContactController extends Controller
{

    protected $contactRepository;

    /**
     * initializes variable with $seminarRepository
     * @param PersonRepositoryInterface $contactRepository
     */
    public function __construct(PersonRepositoryInterface $contactRepository)
    {
        $this->contactRepository = $contactRepository;
        // Set database connection to specific tenant
        //$this->middleware('tenant.database.connection');
        $this->middleware('jwt.auth', ['except' => ['authenticate']]);
    }

    /**
     * Get Contact
     * @output List of all participants
     */

    public function index()
    {
        $person = Person::query();

        // Fetch all participant
        $person->where('is_participant',1);

        try{
            $participantList = $person->get([
                'Email',
                'FirstName',
                'LastName',
            ]);
            return CustomFunction::jsonResponse($participantList, "success", "");

        }catch (\Exception $e) {
            // something went wrong whilst attempting to encode the token
            return CustomFunction::jsonResponse($e, "error", "Something went wrong with your request");

        }

    }


    public function store()
    {
        $personObj =  new Person();

        $customer_fields = [
            'FirstName',
            'LastName',
            'PhoneNumber',
            'Email',
        ];
        try{
            $personObj->fill(Input::only($customer_fields));
            $personObj->is_participant = 1;

            if (!$personObj->save()) {
                return CustomFunction::jsonResponse([], "error", "Something went wrong");
            } else {
                $contact = $personObj->get(['PersonId','FirstName','LastName','Email','PhoneNumber']);
                dd($contact);
                return CustomFunction::jsonResponse($contact, "success", "");
            }
        }catch(\Exception $e){
            return CustomFunction::jsonResponse($e, "error", "Something went wrong with your request");
        }


    }


}
