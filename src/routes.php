<?php
/**
 * Created by PhpStorm.
 * User: Yash
 * Date: 12/23/2016
 * Time: 3:46 PM
 */
use Plusit\Api\Facade\Api;

Route::group(['prefix' => (env('APP_ENV') === 'testing' ? 'en' : LaravelLocalization::setLocale()), 'middleware' => ['localeSessionRedirect', 'localizationRedirect']], function () {
    Route::group(['middleware' => 'force.ssl'], function () {
        Route::group(['prefix' => 'v1'], function () {
            Route::group(['namespace' => 'Plusit\Api\Controllers','prefix' => 'api'], function () {
                Route::post('authenticate', 'AuthController@authenticate');
                Route::get('locations/{id?}', 'LocationController@getLocations');
                Route::get('seminarCategory/{id?}', 'SeminarController@getSeminarCategory');
                Route::get('seminars/{id?}', 'SeminarController@searchSeminar');
                Route::resource('contact', 'ContactController');
                Route::get('seminarsRegistration/{seminarId}/{participantId}', 'SeminarRegistrationController@seminarRegistration');
            });
        });
    });
});