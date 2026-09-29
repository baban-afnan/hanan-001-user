<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\DataVariationController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\EnrollmentSyncController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ServicesController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Action\AirtimeController;
use App\Http\Controllers\Action\DataController;
use App\Http\Controllers\Action\EducationalController;
use App\Http\Controllers\Action\ElectricityController;
use App\Http\Controllers\Action\CableController;
use App\Http\Controllers\Verification\NINverificationController;
use App\Http\Controllers\Verification\NINDemoVerificationController;
use App\Http\Controllers\Verification\NINPhoneVerificationController;
use App\Http\Controllers\Agency\TinRegistrationController;
use App\Http\Controllers\Verification\BvnModificationController;
use App\Http\Controllers\Verification\BvnverificationController;
use App\Http\Controllers\Agency\NinValidationController;
use App\Http\Controllers\Agency\NinModificationController;
use App\Http\Controllers\Agency\IpeController;
use App\Http\Controllers\Action\SmeDataController;
use App\Http\Controllers\Admin\SmeDataController as AdminSmeDataController;
use App\Http\Controllers\Agency\BvnServicesController;
use App\Http\Controllers\Agency\BvncrmController;
use App\Http\Controllers\Agency\ManualSearchController;
use App\Http\Controllers\Admin\BvncrmController as AdminBvncrmController;
use App\Http\Controllers\Agency\NinPersonalisationController;
use App\Http\Controllers\Admin\NinPersonalisationController as AdminNinPersonalisationController;


// =========================================================================
// 1. PUBLIC & GLOBAL ROUTES
// =========================================================================
Route::get('/', function () {
    return view('welcome');
});

Route::post('/palmpay/webhook', [PaymentWebhookController::class, 'handlePalmPay']);
Route::post('/update-bvn-enrollment-status', [EnrollmentSyncController::class, 'updateStatus']);

// =========================================================================
// 2. AUTHENTICATION ROUTES (GUEST)
// =========================================================================
Route::group(['as' => 'auth.', 'prefix' => 'auth', 'middleware' => 'guest'], function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:5,1');

    Route::get('forgot-password', [AuthController::class, 'showForgotPasswordForm'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');

    Route::get('reset-password/{token}', [AuthController::class, 'showResetPasswordForm'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');
});

// =========================================================================
// 3. AUTHENTICATED USER ROUTES
// =========================================================================
Route::middleware(['auth', 'user.active'])->group(function () {
    
    // User Section Group (Prefix: user. / URL: /user)
    Route::group(['as' => 'user.', 'prefix' => 'user'], function () {

        // --- Core Dashboard & Wallet ---
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::post('/kyc-submit', [DashboardController::class, 'submitKyc'])->name('kyc.submit');
        Route::get('/wallet', [WalletController::class, 'index'])->name('wallet');
        Route::get('claim-bonus/{id}', [WalletController::class, 'claimBonus'])->name('claim-bonus');
        Route::get('/receipt/{referenceId}', [TransactionController::class, 'reciept'])->name('reciept');

        // --- Utility Bill Payments ---
        Route::group([], function () {
            // Airtime
            Route::get('/airtime', [AirtimeController::class, 'airtime'])->name('airtime');
            Route::post('/buy-airtime', [AirtimeController::class, 'buyAirtime'])->name('buyairtime');

            // Data (Regular)
            Route::get('/data', [DataController::class, 'data'])->name('buy-data');
            Route::post('/buy-data', [DataController::class, 'buydata'])->name('buydata');
            Route::get('/fetch-data-bundles', [DataController::class, 'fetchBundles'])->name('fetch.bundles');
            Route::get('/fetch-data-bundles-price', [DataController::class, 'fetchBundlePrice'])->name('fetch.bundle.price');
            Route::post('/verify-pin', [DataController::class, 'verifyPin'])->name('verify.pin');

            // Data (SME)
            Route::prefix('buy-sme-data')->group(function () {
                Route::get('/', [SmeDataController::class, 'index'])->name('buy-sme-data');
                Route::post('/buy', [SmeDataController::class, 'buySMEdata'])->name('buy-sme-data.submit');
                Route::get('/fetch-type', [SmeDataController::class, 'fetchDataType'])->name('sme.fetch.type');
                Route::get('/fetch-plan', [SmeDataController::class, 'fetchDataPlan'])->name('sme.fetch.plan');
                Route::get('/fetch-price', [SmeDataController::class, 'fetchSmeBundlePrice'])->name('sme.fetch.price');
            });

            // Education (WAEC/NECO/JAMB)
            Route::get('/education', [EducationalController::class, 'pin'])->name("education");
            Route::post('/buy-pin', [EducationalController::class, 'buypin'])->name('buypin');
            Route::get('/education/receipt/{transaction}', [EducationalController::class, 'receipt'])->name('education.receipt');
            Route::get('/get-variation', [EducationalController::class, 'getVariation'])->name('get-variation');
            Route::get('/jamb', [EducationalController::class, 'jamb'])->name('jamb');
            Route::post('/verify-jamb', [EducationalController::class, 'verifyJamb'])->name('verify.jamb');
            Route::post('/buy-jamb', [EducationalController::class, 'buyJamb'])->name('buyjamb');

            // Electricity
            Route::get('/electricity', [ElectricityController::class, 'index'])->name('electricity');
            Route::post('/verify-electricity', [ElectricityController::class, 'verifyMeter'])->name('verify.electricity');
            Route::post('/buy-electricity', [ElectricityController::class, 'purchase'])->name('buy.electricity');

            // Cable TV
            Route::get('/cable', [CableController::class, 'index'])->name('cable');
            Route::get('/cable/variations', [CableController::class, 'getVariations'])->name('cable.variations');
            Route::post('/cable/verify', [CableController::class, 'verifyIuc'])->name('verify.cable');
            Route::post('/cable/buy', [CableController::class, 'purchase'])->name('buy.cable');

            Route::get('/thankyou', function () {
                return view('user.thankyou');
            })->name('thankyou');
        });

        // --- Identity & Verification Services ---
        Route::group([], function () {
            // NIN Services
            Route::get('/nin-services', [ServicesController::class, 'ninServices'])->name('nin.services');
            Route::post('/nin-services/request', [ServicesController::class, 'requestNinService'])->name('nin.services.request');
            Route::get('/nin-mod', [ServicesController::class, 'ninModification'])->name('nin.mod');
            Route::post('/nin-services/mod', [ServicesController::class, 'requestModification'])->name('nin.services.mod');

            // NIN Verification (Standard/Premium/VNIN)
            Route::prefix('nin-verification')->as('nin.verification.')->group(function () {
                Route::get('/', [NINverificationController::class, 'index'])->name('index');
                Route::post('/', [NINverificationController::class, 'store'])->name('store');
                Route::post('/{id}/status', [NINverificationController::class, 'updateStatus'])->name('status');
                Route::get('/standardSlip/{id}', [NINverificationController::class, 'standardSlip'])->name('standard');
                Route::get('/premiumSlip/{id}', [NINverificationController::class, 'premiumSlip'])->name('premium');
                Route::get('/regularSlip/{id}', [NINverificationController::class, 'regularSlip'])->name('regular');
                Route::get('/basicSlip/{id}', [NINverificationController::class, 'basicSlip'])->name('basic');
                Route::get('/vninSlip/{id}', [NINverificationController::class, 'vninSlip'])->name('vnin');
            });

            // NIN Demographic
            Route::prefix('nin-demo-verification')->as('nin.demo.')->group(function () {
                Route::get('/', [NINDemoVerificationController::class, 'index'])->name('index');
                Route::post('/', [NINDemoVerificationController::class, 'store'])->name('store');
                Route::get('/freeSlip/{id}', [NINDemoVerificationController::class, 'freeSlip'])->name('free');
                Route::get('/regularSlip/{id}', [NINDemoVerificationController::class, 'regularSlip'])->name('regular');
                Route::get('/basicSlip/{id}', [NINDemoVerificationController::class, 'basicSlip'])->name('basic');
                Route::get('/standardSlip/{id}', [NINDemoVerificationController::class, 'standardSlip'])->name('standard');
                Route::get('/premiumSlip/{id}', [NINDemoVerificationController::class, 'premiumSlip'])->name('premium');
                Route::get('/vninSlip/{id}', [NINDemoVerificationController::class, 'vninSlip'])->name('vnin');
            });

            // NIN Phone Verification
            Route::prefix('nin-phone-verification')->as('nin.phone.')->group(function () {
                Route::get('/', [NINPhoneVerificationController::class, 'index'])->name('index');
                Route::post('/', [NINPhoneVerificationController::class, 'store'])->name('store');
                Route::get('/regularSlip/{id}', [NINPhoneVerificationController::class, 'regularSlip'])->name('regular');
                Route::get('/basicSlip/{id}', [NINPhoneVerificationController::class, 'basicSlip'])->name('basic');
                Route::get('/standardSlip/{id}', [NINPhoneVerificationController::class, 'standardSlip'])->name('standard');
                Route::get('/premiumSlip/{id}', [NINPhoneVerificationController::class, 'premiumSlip'])->name('premium');
                Route::get('/vninSlip/{id}', [NINPhoneVerificationController::class, 'vninSlip'])->name('vnin');
            });

            // NIN Validation
            Route::prefix('nin-validation')->as('nin.validation.')->group(function () {
                Route::get('/', [NinValidationController::class, 'index'])->name('index');
                Route::post('/', [NinValidationController::class, 'store'])->name('store');
                Route::get('/check/{id}', [NinValidationController::class, 'checkStatus'])->name('check');
            });

            // NIN Modification
            Route::prefix('nin-modification')->as('nin.modification.')->group(function () {
                Route::get('/', [NinModificationController::class, 'index'])->name('index');
                Route::post('/', [NinModificationController::class, 'store'])->name('store');
                Route::get('/check/{id}', [NinModificationController::class, 'checkStatus'])->name('check');
            });

            // NIN Personalisation
            Route::prefix('nin-personalisation')->as('nin.personalisation.')->group(function () {
                Route::get('/', [NinPersonalisationController::class, 'index'])->name('index');
                Route::post('/', [NinPersonalisationController::class, 'store'])->name('store');
                Route::get('/check/{id}', [NinPersonalisationController::class, 'checkStatus'])->name('check');
            });

            // BVN Verification
            Route::prefix('bvn-verification')->group(function () {
                Route::get('/', [BvnverificationController::class, 'index'])->name('bvn-verification');
                Route::post('/', [BvnverificationController::class, 'store'])->name('bvn.verification.store');
                Route::get('/standardBVN/{id}', [BvnverificationController::class, 'standardBVN'])->name("standardBVN");
                Route::get('/premiumBVN/{id}', [BvnverificationController::class, 'premiumBVN'])->name("premiumBVN");
                Route::get('/plasticBVN/{id}', [BvnverificationController::class, 'plasticBVN'])->name("plasticBVN");
                Route::get('/vninSlip/{id}', [NINverificationController::class, 'vninSlip'])->name('vninSlip');
            });

            // BVN Modification & CRM
            Route::get('/modification', [BvnModificationController::class, 'index'])->name('modification');
            Route::post('/modification', [BvnModificationController::class, 'store'])->name('modification.store');
            Route::get('/modification/check/{id}', [BvnModificationController::class, 'checkStatus'])->name('modification.check');
            Route::get('/modification-fields/{serviceId}', [BvnModificationController::class, 'getServiceFields'])->name('modification.fields');
            
            Route::get('/bvn-crm', [BvncrmController::class, 'index'])->name('bvn-crm');
            Route::post('/bvn-crm', [BvncrmController::class, 'store'])->name('crm.store');
            Route::get('/bvn-crm/check/{id}', [BvncrmController::class, 'checkStatus'])->name('crm.check');

            // BVN Phone Search (Manual Search)
            Route::prefix('phone-search')->as('phone.search.')->group(function () {
                Route::get('/', [ManualSearchController::class, 'index'])->name('index');
                Route::post('/', [ManualSearchController::class, 'store'])->name('store');
                Route::get('/check/{id}', [ManualSearchController::class, 'checkStatus'])->name('check');
                Route::post('/price', [ManualSearchController::class, 'getFieldPrice'])->name('price');
            });

            // TIN Registration
            Route::prefix('tin-reg')->group(function () {
                Route::get('/', [TinRegistrationController::class, 'index'])->name('tin.index');
                Route::post('/validate', [TinRegistrationController::class, 'validateTin'])->name('tin.validate');
                Route::post('/download', [TinRegistrationController::class, 'downloadSlip'])->name('tin.download');
            });

            // IPE Clearance
            Route::prefix('ipe')->as('ipe.')->group(function () {
                Route::get('/', [IpeController::class, 'index'])->name('index');
                Route::post('/', [IpeController::class, 'store'])->name('store');
                Route::get('/check/{id}', [IpeController::class, 'checkStatus'])->name('check');
                Route::get('/{id}/details', [IpeController::class, 'details'])->name('details');
                Route::post('/batch-check', [IpeController::class, 'batchCheck'])->name('batch-check');
            });
        });

        // --- Account, Profile & Support ---
        Route::get('/profile', function () {
            return view('user.profile');
        })->name('profile');
        Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

        Route::get('/support', function () {
            $phoneNumber = env('phoneNumber');
            $message = urlencode(env('message'));
            $url = env('API_URL') . "{$phoneNumber}&text={$message}";
            return redirect($url);
        })->name('support');
    });

    // Logout Route
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

// =========================================================================
// 4. ADMIN PANEL ROUTES
// =========================================================================
Route::group(['prefix' => 'admin', 'as' => 'admin.', 'middleware' => ['auth', 'user.active', 'user.admin']], function () {

    Route::get('/dashboard', [App\Http\Controllers\Admin\AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/receipt/{referenceId}', [TransactionController::class, 'recieptAdmin'])->name('reciept');
    Route::get('/transactions', [TransactionController::class, 'transactions'])->name('transactions');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [UserController::class, 'show'])->name('user.show');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('user.update');
    Route::patch('/users/{user}/activate', [UserController::class, 'activate'])->name('user.activate');

    // Service Management (Prices & Fields)
    Route::prefix('services')->name('services.')->group(function () {
        Route::get('/', [ServiceController::class, 'index'])->name('index');
        Route::post('/', [ServiceController::class, 'store'])->name('store');
        
        // BVN CRM Management
        Route::get('/bvn-crm', [AdminBvncrmController::class, 'index'])->name('bvn-crm.index');
        Route::get('/bvn-crm/check/{id}', [AdminBvncrmController::class, 'checkStatus'])->name('bvn-crm.check');
        Route::post('/bvn-crm/batch-check', [AdminBvncrmController::class, 'batchCheck'])->name('bvn-crm.batch-check');
        Route::post('/bvn-crm/update-status/{id}', [AdminBvncrmController::class, 'updateStatus'])->name('bvn-crm.update-status');

        // NIN Personalisation Management
        Route::prefix('nin-personalisation')->name('nin-personalisation.')->group(function () {
            Route::get('/', [AdminNinPersonalisationController::class, 'index'])->name('index');
            Route::get('/check/{id}', [AdminNinPersonalisationController::class, 'checkStatus'])->name('check');
            Route::post('/batch-check', [AdminNinPersonalisationController::class, 'batchCheck'])->name('batch-check');
            Route::post('/update-status/{id}', [AdminNinPersonalisationController::class, 'updateStatus'])->name('update-status');
        });

        Route::get('/{service}', [ServiceController::class, 'show'])->name('show');
        Route::put('/{service}', [ServiceController::class, 'update'])->name('update');
        Route::delete('/{service}', [ServiceController::class, 'destroy'])->name('destroy');

        Route::post('/{service}/fields', [ServiceController::class, 'storeField'])->name('fields.store');
        Route::put('/fields/{field}', [ServiceController::class, 'updateField'])->name('fields.update');
        Route::delete('/fields/{field}', [ServiceController::class, 'destroyField'])->name('fields.destroy');
        Route::post('/{service}/prices', [ServiceController::class, 'storePrice'])->name('prices.store');
        Route::put('/prices/{price}', [ServiceController::class, 'updatePrice'])->name('prices.update');
        Route::delete('/prices/{price}', [ServiceController::class, 'destroyPrice'])->name('prices.destroy');
    });

    // Data Variation Management
    Route::prefix('data-variations')->name('data-variations.')->group(function () {
        Route::get('/', [DataVariationController::class, 'index'])->name('index');
        Route::get('/{service}', [DataVariationController::class, 'show'])->name('show');
        Route::post('/', [DataVariationController::class, 'store'])->name('store');
        Route::put('/{dataVariation}', [DataVariationController::class, 'update'])->name('update');
        Route::delete('/{dataVariation}', [DataVariationController::class, 'destroy'])->name('destroy');
    });

    // SME Data Management
    Route::prefix('sme-data')->name('sme-data.')->group(function () {
        Route::get('/', [AdminSmeDataController::class, 'index'])->name('index');
        Route::get('/sync', [AdminSmeDataController::class, 'sync'])->name('sync');
        Route::put('/{smeData}/update', [AdminSmeDataController::class, 'update'])->name('update');
    });
});

