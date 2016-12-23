<?php

namespace Plusit\Api\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Request;
use Hyn\MultiTenant\Tenant\DatabaseConnection;
use Hyn\MultiTenant\Models\Website;
use Hyn\MultiTenant\Contracts\WebsiteRepositoryContract;
use Hyn\MultiTenant\Models\Hostname;
use Log;
use JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use App\Interfaces\EventRepositoryInterface;
use Hyn\MultiTenant\Models\Tenant;

class AuthController extends Controller
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
//        $this->middleware('tenant.database.connection');

    }


    /**
     * Display the home page
     *
     * @return \Illuminate\Http\Response
     */

    public function authenticate(Request $request)
    {
        // grab credentials from the request
        $credentials = Request::only('email', 'password');

        try {
            // attempt to verify the credentials and create a token for the user
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            // something went wrong whilst attempting to encode the token
            return response()->json(['error' => 'could_not_create_token'], 500);
        }


        // all good so return the token
        return response()->json(compact('token'));
    }

}
