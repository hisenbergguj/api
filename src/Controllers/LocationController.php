<?php

namespace Plusit\Api\Controllers;
ini_set('max_execution_time', 0);
use App\Http\Controllers\Controller;
use App\Models\Admin\OdooUser;
use App\Models\Admin\user_subscribe;
use App\Models\EventCategory;
use App\Models\Location;
use App\Models\User;
use App\Repositories\Admin\TenantAdminSettingsRepository;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;
use Hyn\MultiTenant\Tenant\DatabaseConnection;
use Illuminate\Support\Facades\Auth;
use Hyn\MultiTenant\Models\Website;
use Hyn\MultiTenant\Contracts\WebsiteRepositoryContract;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\DatabaseManager;
use Hyn\MultiTenant\Models\Hostname;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use App\Library\CustomFunction;
use Carbon\Carbon;
use Log;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Session;
use App\Interfaces\EventRepositoryInterface;
use Hyn\MultiTenant\Models\Tenant;
use App\Http\Controllers\Api\AuthController;

class LocationController extends Controller
{


    /**
     * initializes variable with $seminarRepository
     * @param EventRepositoryInterface $seminarRepository
     */
    public function __construct()
    {
        // Set database connection to specific tenant
        //$this->middleware('tenant.database.connection');
        $this->middleware('jwt.auth', ['except' => ['authenticate']]);

    }


    // Get all location list
    public function getLocations($id = null){
        if($id == null)
            $locations = Location::all();
        else
            $locations = Location::find($id);

        if(count($locations) > 0){
            return Response::json([
                "type"    => "success",
                "message" => "",
                "result" => $locations
            ]);
        }else{
            return Response::json([
                "type"    => "error",
                "message" => CustomFunction::customTrans("general.error_message"),
                "result" => $locations
            ]);
        }
    }

}
